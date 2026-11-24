/* Editor site plan — kanvas 2D, satuan meter, sumbu-y ke bawah. */
(function () {
  'use strict';

  var C = window.SP_CONFIG;
  var P = C.lahan.panjang, LH = C.lahan.lebar;
  var cv = document.getElementById('cv');
  var stage = document.getElementById('stage');
  var ctx = cv.getContext('2d');

  function $(id) { return document.getElementById(id); }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function r2(v) { return Math.round(v * 100) / 100; }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function fmt(n, d) {
    d = d || 0;
    return Number(n).toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
  }
  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  }

  /* ---------- State ---------- */
  var tipeById = {};
  C.tipe.forEach(function (t) { tipeById[t.id] = t; });

  var objs = (C.objs || []).map(function (o) {
    return { id: String(o.id), type: o.type, x: +o.x, y: +o.y, w: +o.w, h: +o.h,
             label: o.label || '', tipe_id: o.tipe_id || '', jenis: o.jenis || '' };
  });
  var sel = {};
  var tool = 'select';
  var snapStep = 1;
  var view = { s: 1, ox: 0, oy: 0 };
  var undoS = [], redoS = [];
  var dirty = false, savedAt = '', saving = false;
  var drag = null, spaceDown = false;
  var overlapIds = {};
  var cw = 0, ch = 0, fitted = false;
  var dpr = window.devicePixelRatio || 1;
  var cursor = { x: 0, y: 0, inside: false };
  var idc = 0;
  function newId() { return 'n' + Date.now().toString(36) + (idc++).toString(36); }
  function byId(id) { for (var i = 0; i < objs.length; i++) if (objs[i].id === id) return objs[i]; return null; }
  function selIds() { return Object.keys(sel).filter(function (id) { return !!byId(id); }); }
  function pruneSel() { Object.keys(sel).forEach(function (id) { if (!byId(id)) delete sel[id]; }); }

  /* ---------- Warna ---------- */
  function colorOf(o) {
    if (o.type === 'road') return '#9aa0a8';
    if (o.type === 'fasum') return (C.fasum[o.jenis] || {}).warna || '#e0b48a';
    var t = tipeById[o.tipe_id];
    return t ? t.warna : '#d9d4c4';
  }

  /* ---------- Geometri ---------- */
  function overlapArea(a, b) {
    var ox = Math.min(a.x + a.w, b.x + b.w) - Math.max(a.x, b.x);
    var oy = Math.min(a.y + a.h, b.y + b.h) - Math.max(a.y, b.y);
    return (ox > 0 && oy > 0) ? ox * oy : 0;
  }
  // Luas gabungan persegi panjang (persimpangan tidak dihitung dua kali) — sama dengan versi PHP.
  function unionArea(rects) {
    if (!rects.length) return 0;
    var xs = [];
    rects.forEach(function (r) { xs.push(r2(r.x), r2(r.x + r.w)); });
    xs = xs.filter(function (v, i, a) { return a.indexOf(v) === i; }).sort(function (a, b) { return a - b; });
    var area = 0;
    for (var i = 0; i < xs.length - 1; i++) {
      var x1 = xs[i], x2 = xs[i + 1];
      if (x2 - x1 < 1e-9) continue;
      var iv = [];
      rects.forEach(function (r) {
        if (r.x <= x1 + 1e-6 && r.x + r.w >= x2 - 1e-6) iv.push([r.y, r.y + r.h]);
      });
      if (!iv.length) continue;
      iv.sort(function (a, b) { return a[0] - b[0]; });
      var len = 0, cs = iv[0][0], ce = iv[0][1];
      for (var k = 1; k < iv.length; k++) {
        if (iv[k][0] <= ce) ce = Math.max(ce, iv[k][1]);
        else { len += ce - cs; cs = iv[k][0]; ce = iv[k][1]; }
      }
      len += ce - cs;
      area += (x2 - x1) * len;
    }
    return area;
  }

  /* ---------- Transformasi ---------- */
  function toW(p) { return { x: (p.px - view.ox) / view.s, y: (p.py - view.oy) / view.s }; }
  function pt(e) { var r = cv.getBoundingClientRect(); return { px: e.clientX - r.left, py: e.clientY - r.top }; }
  function snapv(v) { return Math.round(v / snapStep) * snapStep; }

  // Snap ke grid, lalu ke sisi objek lain bila lebih dekat (dalam 8 px layar).
  function snapCoord(v, axis, excl) {
    var best = snapv(v), bd = Math.abs(v - best), th = 8 / view.s;
    objs.forEach(function (o) {
      if (excl && excl(o)) return;
      var c = axis === 'x' ? [o.x, o.x + o.w] : [o.y, o.y + o.h];
      c.forEach(function (e) {
        var d = Math.abs(e - v);
        if (d < th && d < bd) { bd = d; best = e; }
      });
    });
    return best;
  }
  function snapMove(po, dx, dy, excl) {
    function axis(pos, size, ax, delta) {
      var a = pos + delta, b = a + size;
      var sa = snapCoord(a, ax, excl), sb = snapCoord(b, ax, excl);
      return Math.abs(sa - a) <= Math.abs(sb - b) ? sa : sb - size;
    }
    return [axis(po.x, po.w, 'x', dx), axis(po.y, po.h, 'y', dy)];
  }

  /* ---------- Riwayat ---------- */
  function pushHist() {
    undoS.push(JSON.stringify(objs));
    if (undoS.length > 100) undoS.shift();
    redoS = [];
    syncButtons();
  }
  function undo() {
    if (!undoS.length) return;
    redoS.push(JSON.stringify(objs));
    objs = JSON.parse(undoS.pop());
    pruneSel(); markDirty(); onSelChange();
  }
  function redo() {
    if (!redoS.length) return;
    undoS.push(JSON.stringify(objs));
    objs = JSON.parse(redoS.pop());
    pruneSel(); markDirty(); onSelChange();
  }
  function syncButtons() {
    $('bUndo').disabled = !undoS.length;
    $('bRedo').disabled = !redoS.length;
  }
  function markDirty() { dirty = true; updateStatus(); schedule(); syncButtons(); }
  function updateStatus() {
    var s = $('status');
    s.textContent = saving ? 'Menyimpan…' : dirty ? 'Belum disimpan' : (savedAt ? 'Tersimpan ' + savedAt : '');
    s.className = 'status' + (dirty ? ' dirty' : '');
  }

  /* ---------- Render ---------- */
  var raf = 0;
  function schedule() {
    if (raf) return;
    raf = requestAnimationFrame(function () { raf = 0; recompute(); draw(); });
  }

  function draw() {
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    render(ctx, cw, ch, view, {});
  }

  function textFit(g, txt, maxW) { return g.measureText(txt).width <= maxW; }

  function drawObj(g, o, v, opt) {
    var x = o.x * v.s + v.ox, y = o.y * v.s + v.oy, w = o.w * v.s, h = o.h * v.s;
    g.fillStyle = colorOf(o);
    g.fillRect(x, y, w, h);

    if (o.type === 'road') {
      if (!opt.forExport || true) {
        g.save();
        g.strokeStyle = 'rgba(255,255,255,.7)'; g.lineWidth = 1; g.setLineDash([6, 6]);
        g.beginPath();
        if (o.w >= o.h) { g.moveTo(x + 4, y + h / 2); g.lineTo(x + w - 4, y + h / 2); }
        else { g.moveTo(x + w / 2, y + 4); g.lineTo(x + w / 2, y + h - 4); }
        g.stroke(); g.restore();
      }
    } else {
      g.strokeStyle = o.type === 'lot' ? '#ffffff' : '#6b7a6d';
      g.lineWidth = 1;
      g.strokeRect(x + .5, y + .5, w - 1, h - 1);
      if (o.type === 'lot' && !tipeById[o.tipe_id]) {
        g.save(); g.strokeStyle = '#b5532d'; g.setLineDash([4, 3]);
        g.strokeRect(x + 2.5, y + 2.5, w - 5, h - 5); g.restore();
      }
    }

    // Label
    var lab = o.label || '';
    if (lab && w > 14 && h > 12) {
      var fs = clamp(Math.min(w / 5, h / 2.2), 8, 13);
      g.font = fs + 'px system-ui, sans-serif';
      g.textAlign = 'center'; g.textBaseline = 'middle';
      g.fillStyle = o.type === 'road' ? '#fff' : 'rgba(20,28,24,.88)';
      var vertical = o.type === 'road' && o.h > o.w * 1.5;
      if (vertical) {
        if (textFit(g, lab, h - 8)) { g.save(); g.translate(x + w / 2, y + h / 2); g.rotate(-Math.PI / 2); g.fillText(lab, 0, 0); g.restore(); }
      } else if (textFit(g, lab, w - 4)) {
        var t = o.type === 'lot' ? tipeById[o.tipe_id] : null;
        if (t && h > 30) {
          g.fillText(lab, x + w / 2, y + h / 2 - fs * .55);
          g.font = (fs - 2) + 'px system-ui, sans-serif';
          g.fillStyle = 'rgba(20,28,24,.65)';
          var tn = t.nama.replace(/^Tipe\s*/i, 'T');
          if (textFit(g, tn, w - 4)) g.fillText(tn, x + w / 2, y + h / 2 + fs * .75);
        } else g.fillText(lab, x + w / 2, y + h / 2);
      }
    }
    if (!opt.forExport && overlapIds[o.id]) {
      g.strokeStyle = '#d6291f'; g.lineWidth = 2; g.strokeRect(x + 1, y + 1, w - 2, h - 2);
    }
  }

  function handlesOf(o) {
    var x0 = o.x * view.s + view.ox, y0 = o.y * view.s + view.oy;
    var x1 = x0 + o.w * view.s, y1 = y0 + o.h * view.s, mx = (x0 + x1) / 2, my = (y0 + y1) / 2;
    return [
      { k: 'nw', x: x0, y: y0 }, { k: 'n', x: mx, y: y0 }, { k: 'ne', x: x1, y: y0 },
      { k: 'e', x: x1, y: my }, { k: 'se', x: x1, y: y1 }, { k: 's', x: mx, y: y1 },
      { k: 'sw', x: x0, y: y1 }, { k: 'w', x: x0, y: my }
    ];
  }

  function dimLabel(g, x, y, txt) {
    g.font = '11px system-ui, sans-serif';
    var w = g.measureText(txt).width + 10;
    g.fillStyle = 'rgba(20,28,24,.85)';
    g.fillRect(x - w / 2, y - 18, w, 17);
    g.fillStyle = '#fff'; g.textAlign = 'center'; g.textBaseline = 'middle';
    g.fillText(txt, x, y - 9.5);
  }

  function render(g, W, H, v, opt) {
    g.fillStyle = '#e6e2d6';
    g.fillRect(0, 0, W, H);
    var X = function (x) { return x * v.s + v.ox; }, Y = function (y) { return y * v.s + v.oy; };
    g.fillStyle = '#f8f6ef';
    g.fillRect(X(0), Y(0), P * v.s, LH * v.s);

    // Grid
    var steps = [1, 2, 5, 10, 20, 50, 100], gs = 100;
    for (var i = 0; i < steps.length; i++) if (steps[i] * v.s >= 14) { gs = steps[i]; break; }
    g.strokeStyle = 'rgba(60,50,30,.07)'; g.lineWidth = 1; g.beginPath();
    for (var gx = 0; gx <= P + 1e-9; gx += gs) { var px = Math.round(X(gx)) + .5; g.moveTo(px, Y(0)); g.lineTo(px, Y(LH)); }
    for (var gy = 0; gy <= LH + 1e-9; gy += gs) { var py = Math.round(Y(gy)) + .5; g.moveTo(X(0), py); g.lineTo(X(P), py); }
    g.stroke();

    ['road', 'fasum', 'lot'].forEach(function (layer) {
      objs.forEach(function (o) { if (o.type === layer) drawObj(g, o, v, opt); });
    });

    // Batas lahan
    g.save();
    g.strokeStyle = '#4f4a3c'; g.lineWidth = 2; g.setLineDash([9, 5]);
    g.strokeRect(X(0), Y(0), P * v.s, LH * v.s);
    g.restore();
    g.fillStyle = '#4f4a3c'; g.font = '11px system-ui, sans-serif'; g.textBaseline = 'alphabetic';
    g.textAlign = 'center';
    g.fillText(fmt(P, 1) + ' m', X(P / 2), Y(0) - 6);
    g.save(); g.translate(X(0) - 8, Y(LH / 2)); g.rotate(-Math.PI / 2); g.fillText(fmt(LH, 1) + ' m', 0, 0); g.restore();

    if (opt.forExport) return;

    // Seleksi
    var ids = selIds();
    ids.forEach(function (id) {
      var o = byId(id);
      var x = X(o.x), y = Y(o.y), w = o.w * v.s, h = o.h * v.s;
      g.strokeStyle = '#1f6fe5'; g.lineWidth = 2; g.setLineDash([]);
      g.strokeRect(x, y, w, h);
    });
    if (ids.length === 1) {
      var so = byId(ids[0]);
      handlesOf(so).forEach(function (hd) {
        g.fillStyle = '#fff'; g.strokeStyle = '#1f6fe5'; g.lineWidth = 1.5;
        g.fillRect(hd.x - 4, hd.y - 4, 8, 8); g.strokeRect(hd.x - 4, hd.y - 4, 8, 8);
      });
      dimLabel(g, X(so.x + so.w / 2), Y(so.y) - 4, fmt(so.w, 1) + ' × ' + fmt(so.h, 1) + ' m');
    }

    // Draft gambar
    if (drag && drag.mode === 'draw' && drag.moved) {
      var a = drag.a, b = drag.b;
      var dx = Math.min(a.x, b.x), dy = Math.min(a.y, b.y), dw = Math.abs(b.x - a.x), dh = Math.abs(b.y - a.y);
      var col = drag.type === 'road' ? '#9aa0a8' : drag.type === 'fasum' ? ((C.fasum[$('selJenis').value] || {}).warna || '#e0b48a') : (tipeById[$('selTipe').value] ? tipeById[$('selTipe').value].warna : '#d9d4c4');
      g.globalAlpha = .55; g.fillStyle = col; g.fillRect(X(dx), Y(dy), dw * v.s, dh * v.s); g.globalAlpha = 1;
      g.strokeStyle = '#1f6fe5'; g.lineWidth = 1.5; g.setLineDash([6, 4]);
      g.strokeRect(X(dx), Y(dy), dw * v.s, dh * v.s); g.setLineDash([]);
      var txt = fmt(dw, 1) + ' × ' + fmt(dh, 1) + ' m';
      if (drag.type === 'lot') {
        var t = tipeById[$('selTipe').value];
        if (t) {
          var cols = Math.floor(dw / t.lebar + 1e-6), rows = Math.floor(dh / t.panjang + 1e-6);
          if (cols >= 1 && rows >= 1) {
            g.strokeStyle = 'rgba(31,111,229,.6)'; g.lineWidth = 1; g.beginPath();
            for (var cx = 1; cx < cols; cx++) { g.moveTo(X(dx + cx * t.lebar), Y(dy)); g.lineTo(X(dx + cx * t.lebar), Y(dy + rows * t.panjang)); }
            for (var cy = 1; cy < rows; cy++) { g.moveTo(X(dx), Y(dy + cy * t.panjang)); g.lineTo(X(dx + cols * t.lebar), Y(dy + cy * t.panjang)); }
            g.stroke();
            txt = cols * rows + ' kavling (' + cols + '×' + rows + ')';
          }
        }
      }
      dimLabel(g, X(dx + dw / 2), Y(dy) - 2, txt);
    }
    if (drag && drag.mode === 'marquee') {
      var m1 = drag.a, m2 = drag.b;
      g.fillStyle = 'rgba(31,111,229,.10)'; g.strokeStyle = '#1f6fe5'; g.lineWidth = 1; g.setLineDash([5, 4]);
      var mx = Math.min(m1.x, m2.x), my = Math.min(m1.y, m2.y);
      g.fillRect(X(mx), Y(my), Math.abs(m2.x - m1.x) * v.s, Math.abs(m2.y - m1.y) * v.s);
      g.strokeRect(X(mx), Y(my), Math.abs(m2.x - m1.x) * v.s, Math.abs(m2.y - m1.y) * v.s);
      g.setLineDash([]);
    }
  }

  /* ---------- Statistik ---------- */
  function recompute() {
    var roads = [], lots = [], fasum = [];
    objs.forEach(function (o) { (o.type === 'road' ? roads : o.type === 'lot' ? lots : fasum).push(o); });
    var luasLahan = P * LH, luasJalan = unionArea(roads);
    var luasKav = 0, per = {}, tanpa = 0, luasFasum = 0;
    lots.forEach(function (l) {
      var a = l.w * l.h; luasKav += a;
      if (!tipeById[l.tipe_id]) { tanpa++; return; }
      var p = per[l.tipe_id] || (per[l.tipe_id] = { n: 0, a: 0 });
      p.n++; p.a += a;
    });
    fasum.forEach(function (f) { luasFasum += f.w * f.h; });

    overlapIds = {};
    var tumpang = 0;
    if (objs.length <= 600) {
      var all = lots.concat(fasum, roads);
      for (var i = 0; i < all.length; i++) {
        for (var j = i + 1; j < all.length; j++) {
          if (all[i].type === 'road' && all[j].type === 'road') continue;
          if (overlapArea(all[i], all[j]) > 0.05) { overlapIds[all[i].id] = overlapIds[all[j].id] = true; tumpang++; }
        }
      }
    }
    var luar = 0;
    objs.forEach(function (o) { if (o.x < -0.01 || o.y < -0.01 || o.x + o.w > P + 0.01 || o.y + o.h > LH + 0.01) luar++; });

    var rasio = luasLahan > 0 ? luasKav / luasLahan * 100 : 0;
    var warn = [];
    if (rasio > C.rasioMax) warn.push('Rasio kavling ' + fmt(rasio, 1) + '% melebihi batas ' + fmt(C.rasioMax, 0) + '%.');
    if (tanpa > 0) warn.push(tanpa + ' kavling belum diberi tipe — tidak masuk RAB.');
    if (tumpang > 0) warn.push(tumpang + ' pasang objek saling tumpang tindih (garis merah).');
    if (luar > 0) warn.push(luar + ' objek di luar batas lahan.');
    Object.keys(per).forEach(function (id) {
      var t = tipeById[id], rata = per[id].a / per[id].n;
      if (rata + 0.01 < t.luas_tanah) warn.push(t.nama + ': kavling rata-rata ' + fmt(rata, 1) + ' m² < luas tanah tipe ' + fmt(t.luas_tanah, 0) + ' m².');
    });

    var box = $('stats'); box.textContent = '';
    function row(lbl, val, color) {
      var r = el('div', 'srow'), l = el('span', 'sl');
      if (color) { var d = el('i'); d.style.background = color; l.appendChild(d); }
      l.appendChild(document.createTextNode(lbl));
      r.appendChild(l); r.appendChild(el('strong', '', val)); box.appendChild(r);
    }
    row('Luas lahan', fmt(luasLahan, 0) + ' m²');
    row('Kavling', lots.length + ' unit · ' + fmt(rasio, 1) + '%');
    row('Jalan', fmt(luasJalan, 0) + ' m² · ' + fmt(luasLahan ? luasJalan / luasLahan * 100 : 0, 1) + '%');
    row('Fasum', fmt(luasFasum, 0) + ' m² · ' + fmt(luasLahan ? luasFasum / luasLahan * 100 : 0, 1) + '%');
    C.tipe.forEach(function (t) { if (per[t.id]) row(t.nama, per[t.id].n + ' unit', t.warna); });
    if (tanpa) row('Tanpa tipe', tanpa + ' unit', '#d9d4c4');

    var wb = $('warns'); wb.textContent = '';
    warn.forEach(function (w) { wb.appendChild(el('div', 'warnrow', '⚠ ' + w)); });
  }

  /* ---------- Panel properti ---------- */
  function field(label, input) {
    var l = el('label', 'fld'); l.appendChild(el('span', '', label)); l.appendChild(input); return l;
  }
  function numInput(val, cb, min) {
    var i = el('input'); i.type = 'number'; i.step = 'any'; i.value = r2(val);
    i.addEventListener('change', function () {
      var v = parseFloat(i.value);
      if (!isFinite(v) || (min != null && v < min)) { i.value = r2(val); return; }
      pushHist(); cb(v); markDirty(); renderProps();
    });
    return i;
  }
  function tipeSelect(cur, cb, withNone) {
    var s = el('select');
    var none = el('option', '', '(Tanpa tipe)'); none.value = ''; s.appendChild(none);
    C.tipe.forEach(function (t) { var o = el('option', '', t.nama); o.value = t.id; s.appendChild(o); });
    s.value = cur || '';
    s.addEventListener('change', function () { pushHist(); cb(s.value); markDirty(); renderProps(); });
    return s;
  }

  function renderProps() {
    var box = $('props');
    if (box.contains(document.activeElement) && document.activeElement.tagName === 'INPUT' && document.activeElement.type === 'text') return;
    box.textContent = '';
    var ids = selIds();
    if (!ids.length) { box.appendChild(el('p', 'muted small', 'Pilih objek di kanvas untuk mengubah ukuran, tipe, atau labelnya.')); return; }

    if (ids.length === 1) {
      var o = byId(ids[0]);
      var kind = o.type === 'road' ? 'Jalan' : o.type === 'lot' ? 'Kavling' : 'Fasum';
      box.appendChild(el('div', 'ptitle', kind + ' · ' + fmt(o.w * o.h, 1) + ' m²'));
      var lab = el('input'); lab.type = 'text'; lab.maxLength = 40; lab.value = o.label;
      lab.addEventListener('change', function () { pushHist(); o.label = lab.value.slice(0, 40); markDirty(); });
      box.appendChild(field('Label', lab));
      var g = el('div', 'form-grid two tight');
      g.appendChild(field('X (m)', numInput(o.x, function (v) { o.x = r2(v); })));
      g.appendChild(field('Y (m)', numInput(o.y, function (v) { o.y = r2(v); })));
      g.appendChild(field('Lebar (m)', numInput(o.w, function (v) { o.w = r2(v); }, 0.5)));
      g.appendChild(field('Panjang (m)', numInput(o.h, function (v) { o.h = r2(v); }, 0.5)));
      box.appendChild(g);
      if (o.type === 'lot') {
        box.appendChild(field('Tipe rumah', tipeSelect(o.tipe_id, function (v) { o.tipe_id = v; })));
        if (tipeById[o.tipe_id]) {
          var t = tipeById[o.tipe_id];
          var b = el('button', 'btn small', 'Samakan ukuran: ' + fmt(t.lebar, 1) + ' × ' + fmt(t.panjang, 1) + ' m');
          b.type = 'button';
          b.addEventListener('click', function () { pushHist(); o.w = t.lebar; o.h = t.panjang; markDirty(); renderProps(); });
          box.appendChild(b);
        }
      }
      if (o.type === 'fasum') {
        var js = el('select');
        Object.keys(C.fasum).forEach(function (k) { var op = el('option', '', C.fasum[k].label); op.value = k; js.appendChild(op); });
        js.value = o.jenis || 'lainnya';
        js.addEventListener('change', function () {
          pushHist(); o.jenis = js.value;
          if (!o.label || Object.keys(C.fasum).some(function (k) { return C.fasum[k].label === o.label; })) o.label = C.fasum[js.value].label;
          markDirty(); renderProps();
        });
        box.appendChild(field('Jenis fasum', js));
      }
    } else {
      box.appendChild(el('div', 'ptitle', ids.length + ' objek dipilih'));
      var lots = ids.filter(function (id) { return byId(id).type === 'lot'; });
      if (lots.length) {
        box.appendChild(field('Tipe untuk ' + lots.length + ' kavling', tipeSelect('', function (v) {
          lots.forEach(function (id) { byId(id).tipe_id = v; });
        })));
      }
    }
    var acts = el('div', 'row-actions');
    var bd = el('button', 'btn small', 'Gandakan'); bd.type = 'button'; bd.addEventListener('click', duplicateSel);
    var bx = el('button', 'btn small danger', 'Hapus'); bx.type = 'button'; bx.addEventListener('click', deleteSel);
    acts.appendChild(bd); acts.appendChild(bx); box.appendChild(acts);
  }

  function onSelChange() { renderProps(); schedule(); }

  /* ---------- Operasi ---------- */
  function deleteSel() {
    var ids = selIds(); if (!ids.length) return;
    pushHist();
    objs = objs.filter(function (o) { return !sel[o.id]; });
    sel = {}; markDirty(); onSelChange();
  }
  function duplicateSel() {
    var ids = selIds(); if (!ids.length) return;
    pushHist();
    var off = Math.max(snapStep, 1), ns = {};
    ids.forEach(function (id) {
      var c = clone(byId(id)); c.id = newId(); c.x = r2(c.x + off); c.y = r2(c.y + off);
      if (c.type === 'lot') c.label = '';
      objs.push(c); ns[c.id] = true;
    });
    sel = ns; markDirty(); onSelChange();
  }
  function rotateSel() {
    var ids = selIds(); if (!ids.length) return;
    pushHist();
    ids.forEach(function (id) {
      var o = byId(id), cx = o.x + o.w / 2, cy = o.y + o.h / 2, w = o.w;
      o.w = o.h; o.h = w; o.x = r2(cx - o.w / 2); o.y = r2(cy - o.h / 2);
    });
    markDirty(); onSelChange();
  }
  function nudge(dx, dy) {
    var ids = selIds(); if (!ids.length) return;
    pushHist();
    ids.forEach(function (id) { var o = byId(id); o.x = r2(o.x + dx); o.y = r2(o.y + dy); });
    markDirty(); renderProps();
  }
  function nextLotNo() {
    var m = 0;
    objs.forEach(function (o) { var r = /^K-(\d+)$/.exec(o.label || ''); if (r) m = Math.max(m, +r[1]); });
    return m + 1;
  }

  function finishDraw(d) {
    var a = d.a, b = d.b;
    var x = Math.min(a.x, b.x), y = Math.min(a.y, b.y), w = Math.abs(b.x - a.x), h = Math.abs(b.y - a.y);
    var made = [];
    if (d.type === 'road') {
      if (w < 0.5 || h < 0.5) return;
      made.push({ type: 'road', x: x, y: y, w: w, h: h, label: 'Jalan', tipe_id: '', jenis: '' });
    } else if (d.type === 'fasum') {
      if (w < 0.5 || h < 0.5) return;
      var jn = $('selJenis').value || 'lainnya';
      made.push({ type: 'fasum', x: x, y: y, w: w, h: h, label: (C.fasum[jn] || {}).label || 'Fasum', tipe_id: '', jenis: jn });
    } else if (d.type === 'lot') {
      var t = tipeById[$('selTipe').value], lw = t ? t.lebar : 6, ld = t ? t.panjang : 12, tid = t ? t.id : '';
      var no = nextLotNo();
      function lot(px, py, pw, ph) {
        return { type: 'lot', x: px, y: py, w: pw, h: ph, label: 'K-' + String(no++).padStart(2, '0'), tipe_id: tid, jenis: '' };
      }
      if (!d.moved || (w < 0.5 && h < 0.5)) {
        made.push(lot(clamp(snapv(a.x - lw / 2), 0, Math.max(0, P - lw)), clamp(snapv(a.y - ld / 2), 0, Math.max(0, LH - ld)), lw, ld));
      } else {
        var cols = Math.floor(w / lw + 1e-6), rows = Math.floor(h / ld + 1e-6);
        if (cols < 1 || rows < 1) {
          if (w >= 0.5 && h >= 0.5) made.push(lot(x, y, w, h));
        } else {
          for (var rr = 0; rr < rows; rr++) for (var cc = 0; cc < cols; cc++) made.push(lot(x + cc * lw, y + rr * ld, lw, ld));
        }
      }
    }
    if (!made.length) return;
    pushHist();
    sel = {};
    made.forEach(function (m) {
      m.id = newId(); m.x = r2(m.x); m.y = r2(m.y); m.w = r2(m.w); m.h = r2(m.h);
      objs.push(m); sel[m.id] = true;
    });
    markDirty(); onSelChange();
  }

  function finishMarquee(d) {
    var x1 = Math.min(d.a.x, d.b.x), y1 = Math.min(d.a.y, d.b.y), x2 = Math.max(d.a.x, d.b.x), y2 = Math.max(d.a.y, d.b.y);
    if ((x2 - x1) * view.s < 4 && (y2 - y1) * view.s < 4) return;
    var ns = d.add ? clone(d.base) : {};
    objs.forEach(function (o) {
      if (o.x >= x1 - 1e-6 && o.y >= y1 - 1e-6 && o.x + o.w <= x2 + 1e-6 && o.y + o.h <= y2 + 1e-6) ns[o.id] = true;
    });
    sel = ns;
  }

  /* ---------- Hit test ---------- */
  function hitObj(w) {
    var order = ['lot', 'fasum', 'road'];
    for (var k = 0; k < order.length; k++) {
      for (var i = objs.length - 1; i >= 0; i--) {
        var o = objs[i];
        if (o.type === order[k] && w.x >= o.x && w.x <= o.x + o.w && w.y >= o.y && w.y <= o.y + o.h) return o;
      }
    }
    return null;
  }
  function hitHandle(p) {
    var ids = selIds(); if (ids.length !== 1) return null;
    var o = byId(ids[0]), hs = handlesOf(o);
    for (var i = 0; i < hs.length; i++) if (Math.abs(p.px - hs[i].x) <= 7 && Math.abs(p.py - hs[i].y) <= 7) return { h: hs[i].k, o: o };
    return null;
  }
  var CURSORS = { nw: 'nwse-resize', se: 'nwse-resize', ne: 'nesw-resize', sw: 'nesw-resize', n: 'ns-resize', s: 'ns-resize', e: 'ew-resize', w: 'ew-resize' };
  function setCursor(c) { cv.style.cursor = c; }
  function baseCursor() { return (tool === 'pan' || spaceDown) ? 'grab' : tool === 'select' ? 'default' : 'crosshair'; }

  /* ---------- Pointer ---------- */
  cv.addEventListener('contextmenu', function (e) { e.preventDefault(); });

  cv.addEventListener('pointerdown', function (e) {
    cv.focus();
    var p = pt(e), w = toW(p);
    if (e.button === 1 || e.button === 2 || tool === 'pan' || spaceDown) {
      drag = { mode: 'pan', px: p.px, py: p.py, ox: view.ox, oy: view.oy };
      cv.setPointerCapture(e.pointerId); setCursor('grabbing'); e.preventDefault(); return;
    }
    if (e.button !== 0) return;

    if (tool === 'select') {
      var hh = hitHandle(p);
      if (hh) {
        drag = { mode: 'resize', h: hh.h, id: hh.o.id, orig: clone(hh.o), pushed: false };
      } else {
        var o = hitObj(w);
        if (o) {
          if (e.shiftKey) { if (sel[o.id]) delete sel[o.id]; else sel[o.id] = true; }
          else if (!sel[o.id]) { sel = {}; sel[o.id] = true; }
          if (sel[o.id]) {
            var origs = {};
            selIds().forEach(function (id) { origs[id] = clone(byId(id)); });
            drag = { mode: 'move', start: w, primary: o.id, origs: origs, pushed: false };
          }
        } else {
          if (!e.shiftKey) sel = {};
          drag = { mode: 'marquee', a: w, b: w, add: e.shiftKey, base: clone(sel) };
        }
      }
      onSelChange();
    } else {
      var a = { x: clamp(snapCoord(w.x, 'x'), 0, P), y: clamp(snapCoord(w.y, 'y'), 0, LH) };
      drag = { mode: 'draw', type: tool, a: a, b: a, moved: false };
    }
    if (drag) cv.setPointerCapture(e.pointerId);
    schedule();
  });

  cv.addEventListener('pointermove', function (e) {
    var p = pt(e), w = toW(p);
    cursor = { x: w.x, y: w.y, inside: true };
    updateCoord();
    if (!drag) {
      var hh = tool === 'select' ? hitHandle(p) : null;
      if (hh) setCursor(CURSORS[hh.h]);
      else if (tool === 'select' && hitObj(w)) setCursor('move');
      else setCursor(baseCursor());
      return;
    }
    if (drag.mode === 'pan') {
      view.ox = drag.ox + (p.px - drag.px); view.oy = drag.oy + (p.py - drag.py);
    } else if (drag.mode === 'move') {
      var dx = w.x - drag.start.x, dy = w.y - drag.start.y;
      if (!drag.pushed) {
        if (Math.abs(dx) * view.s < 3 && Math.abs(dy) * view.s < 3) return;
        pushHist(); drag.pushed = true;
      }
      var po = drag.origs[drag.primary];
      var n = snapMove(po, dx, dy, function (o) { return !!sel[o.id]; });
      var ddx = n[0] - po.x, ddy = n[1] - po.y;
      Object.keys(drag.origs).forEach(function (id) {
        var o = byId(id), og = drag.origs[id];
        if (o) { o.x = r2(og.x + ddx); o.y = r2(og.y + ddy); }
      });
      markDirty();
    } else if (drag.mode === 'resize') {
      if (!drag.pushed) { pushHist(); drag.pushed = true; }
      var o2 = byId(drag.id), g = drag.orig, k = drag.h;
      var x1 = g.x, y1 = g.y, x2 = g.x + g.w, y2 = g.y + g.h;
      var ex = function (q) { return q.id === drag.id; };
      if (k.indexOf('w') >= 0) x1 = snapCoord(w.x, 'x', ex);
      if (k.indexOf('e') >= 0) x2 = snapCoord(w.x, 'x', ex);
      if (k.indexOf('n') >= 0) y1 = snapCoord(w.y, 'y', ex);
      if (k.indexOf('s') >= 0) y2 = snapCoord(w.y, 'y', ex);
      if (x2 - x1 < 0.5) { if (k.indexOf('w') >= 0) x1 = x2 - 0.5; else x2 = x1 + 0.5; }
      if (y2 - y1 < 0.5) { if (k.indexOf('n') >= 0) y1 = y2 - 0.5; else y2 = y1 + 0.5; }
      o2.x = r2(x1); o2.y = r2(y1); o2.w = r2(x2 - x1); o2.h = r2(y2 - y1);
      markDirty();
    } else if (drag.mode === 'draw') {
      drag.b = { x: clamp(snapCoord(w.x, 'x'), 0, P), y: clamp(snapCoord(w.y, 'y'), 0, LH) };
      if (Math.abs(drag.b.x - drag.a.x) * view.s > 3 || Math.abs(drag.b.y - drag.a.y) * view.s > 3) drag.moved = true;
    } else if (drag.mode === 'marquee') {
      drag.b = w;
    }
    schedule();
  });

  function endDrag(e) {
    if (!drag) return;
    var d = drag; drag = null;
    try { cv.releasePointerCapture(e.pointerId); } catch (_) { /* abaikan */ }
    if (d.mode === 'draw') finishDraw(d);
    else if (d.mode === 'marquee') { finishMarquee(d); onSelChange(); }
    else if (d.mode === 'move' || d.mode === 'resize') renderProps();
    setCursor(baseCursor());
    schedule();
  }
  cv.addEventListener('pointerup', endDrag);
  cv.addEventListener('pointercancel', endDrag);
  cv.addEventListener('pointerleave', function () { cursor.inside = false; updateCoord(); });

  cv.addEventListener('wheel', function (e) {
    e.preventDefault();
    var p = pt(e);
    zoomAt(p.px, p.py, Math.exp(-e.deltaY * 0.0015));
  }, { passive: false });

  function zoomAt(px, py, f) {
    var minS = fitScale() * 0.3, maxS = 80;
    var ns = clamp(view.s * f, minS, maxS); f = ns / view.s;
    view.ox = px - (px - view.ox) * f; view.oy = py - (py - view.oy) * f; view.s = ns;
    schedule(); updateCoord();
  }
  function fitScale() { return Math.min((cw - 70) / P, (ch - 70) / LH); }
  function fit() {
    var s = Math.max(0.2, fitScale());
    view.s = s; view.ox = (cw - P * s) / 2; view.oy = (ch - LH * s) / 2 + 6;
    schedule(); updateCoord();
  }
  function updateCoord() {
    $('coord').textContent = (cursor.inside ? 'x ' + fmt(cursor.x, 1) + ' m · y ' + fmt(cursor.y, 1) + ' m · ' : '') + 'zoom ' + fmt(view.s, 1) + ' px/m';
  }

  /* ---------- Keyboard ---------- */
  function typing(t) { return t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT'); }
  document.addEventListener('keydown', function (e) {
    var mod = e.ctrlKey || e.metaKey, k = e.key.toLowerCase();
    if (mod && k === 's') { e.preventDefault(); save(); return; }
    if (typing(e.target)) return;
    if (e.code === 'Space') { if (!spaceDown) { spaceDown = true; setCursor('grab'); } e.preventDefault(); return; }
    if (mod && k === 'z') { e.preventDefault(); if (e.shiftKey) redo(); else undo(); return; }
    if (mod && k === 'y') { e.preventDefault(); redo(); return; }
    if (mod && k === 'd') { e.preventDefault(); duplicateSel(); return; }
    if (mod && k === 'a') { e.preventDefault(); sel = {}; objs.forEach(function (o) { sel[o.id] = true; }); onSelChange(); return; }
    if (mod) return;
    if (k === 'delete' || k === 'backspace') { e.preventDefault(); deleteSel(); return; }
    if (k === 'escape') { if (drag && drag.mode === 'draw') drag = null; sel = {}; setTool('select'); onSelChange(); return; }
    var step = snapStep * (e.shiftKey ? 5 : 1);
    if (k === 'arrowleft') { e.preventDefault(); nudge(-step, 0); return; }
    if (k === 'arrowright') { e.preventDefault(); nudge(step, 0); return; }
    if (k === 'arrowup') { e.preventDefault(); nudge(0, -step); return; }
    if (k === 'arrowdown') { e.preventDefault(); nudge(0, step); return; }
    var map = { v: 'select', j: 'road', k: 'lot', f: 'fasum', h: 'pan' };
    if (map[k]) { setTool(map[k]); return; }
    if (k === 'r') { rotateSel(); return; }
  });
  document.addEventListener('keyup', function (e) {
    if (e.code === 'Space') { spaceDown = false; setCursor(baseCursor()); }
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });

  /* ---------- Toolbar ---------- */
  function setTool(t) {
    tool = t;
    document.querySelectorAll('.tool').forEach(function (b) { b.classList.toggle('on', b.dataset.tool === t); });
    setCursor(baseCursor());
  }
  document.querySelectorAll('.tool').forEach(function (b) {
    b.addEventListener('click', function () { setTool(b.dataset.tool); cv.focus(); });
  });

  C.tipe.forEach(function (t) {
    ['selTipe', 'gTipe'].forEach(function (id) {
      var o = el('option', '', t.nama + ' (' + fmt(t.lebar, 1) + '×' + fmt(t.panjang, 1) + ')'); o.value = t.id; $(id).appendChild(o);
    });
  });
  Object.keys(C.fasum).forEach(function (k) {
    var o = el('option', '', C.fasum[k].label); o.value = k; $('selJenis').appendChild(o);
  });
  $('selSnap').addEventListener('change', function () { snapStep = parseFloat(this.value) || 1; });
  $('gRasio').value = C.rasioMax;

  $('bUndo').addEventListener('click', undo);
  $('bRedo').addEventListener('click', redo);
  $('bFit').addEventListener('click', fit);
  $('bZoomIn').addEventListener('click', function () { zoomAt(cw / 2, ch / 2, 1.25); });
  $('bZoomOut').addEventListener('click', function () { zoomAt(cw / 2, ch / 2, 0.8); });
  $('bSave').addEventListener('click', save);

  /* ---------- Simpan ---------- */
  function save() {
    if (saving) return;
    saving = true; updateStatus();
    fetch(C.urls.save, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.csrf },
      body: JSON.stringify({ objects: objs })
    }).then(function (r) { return r.json(); }).then(function (j) {
      saving = false;
      if (j && j.ok) { dirty = false; savedAt = j.tersimpan; } else { alert((j && j.pesan) || 'Gagal menyimpan.'); }
      updateStatus();
    }).catch(function () {
      saving = false; updateStatus();
      alert('Gagal menyimpan. Periksa koneksi atau muat ulang halaman (sesi mungkin berakhir).');
    });
  }

  /* ---------- Layout otomatis ---------- */
  $('bGen').addEventListener('click', function () {
    if (!$('gTipe').value) { alert('Tambahkan tipe rumah terlebih dahulu di menu Tipe Rumah.'); return; }
    if (objs.length && !confirm('Layout otomatis akan MENGGANTI seluruh isi site plan saat ini (' + objs.length + ' objek). Lanjutkan? Anda masih bisa mengurungkan dengan Ctrl+Z.')) return;
    var btn = $('bGen'); btn.disabled = true;
    fetch(C.urls.generate, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.csrf },
      body: JSON.stringify({
        tipe_id: $('gTipe').value,
        lebar_jalan_utama: $('gJalanUtama').value, lebar_jalan: $('gJalan').value, rasio_kavling_max: $('gRasio').value
      })
    }).then(function (r) { return r.json(); }).then(function (j) {
      btn.disabled = false;
      if (!j.ok) { alert(j.pesan || 'Gagal membuat layout.'); return; }
      pushHist(); objs = j.objects.map(function (o) { return o; }); sel = {};
      markDirty(); onSelChange(); fit();
    }).catch(function () { btn.disabled = false; alert('Gagal membuat layout.'); });
  });

  /* ---------- PNG ---------- */
  $('bPng').addEventListener('click', function () {
    var W = 1800, s = (W - 100) / P, top = 90, bottom = 70, H = Math.round(LH * s + top + bottom);
    var oc = document.createElement('canvas'); oc.width = W; oc.height = H;
    var g = oc.getContext('2d');
    render(g, W, H, { s: s, ox: 50, oy: top }, { forExport: true });
    g.fillStyle = '#1b2a22'; g.textAlign = 'left'; g.textBaseline = 'alphabetic';
    g.font = 'bold 28px system-ui, sans-serif'; g.fillText('Site Plan — ' + C.nama, 50, 44);
    g.font = '15px system-ui, sans-serif'; g.fillStyle = '#4f4a3c';
    g.fillText('Lahan ' + fmt(P, 1) + ' × ' + fmt(LH, 1) + ' m · ' + objs.filter(function (o) { return o.type === 'lot'; }).length + ' kavling', 50, 68);
    var lx = 50, ly = top + LH * s + 40;
    var items = C.tipe.map(function (t) { return [t.warna, t.nama]; }).concat([['#9aa0a8', 'Jalan']]);
    Object.keys(C.fasum).forEach(function (k) { items.push([C.fasum[k].warna, C.fasum[k].label]); });
    g.font = '14px system-ui, sans-serif';
    items.forEach(function (it) {
      var wlab = g.measureText(it[1]).width;
      if (lx + wlab + 40 > W - 40) { lx = 50; ly += 22; }
      g.fillStyle = it[0]; g.fillRect(lx, ly - 11, 14, 14);
      g.fillStyle = '#1b2a22'; g.fillText(it[1], lx + 20, ly);
      lx += wlab + 44;
    });
    oc.toBlob(function (blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'siteplan-' + (C.nama || 'proyek').replace(/[^\w-]+/g, '_') + '.png';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 2000);
    });
  });

  /* ---------- Ukuran kanvas ---------- */
  function resize() {
    var r = stage.getBoundingClientRect();
    cw = Math.max(240, Math.floor(r.width)); ch = Math.max(320, Math.floor(r.height));
    dpr = window.devicePixelRatio || 1;
    cv.width = Math.round(cw * dpr); cv.height = Math.round(ch * dpr);
    cv.style.width = cw + 'px'; cv.style.height = ch + 'px';
    if (!fitted) { fitted = true; fit(); } else schedule();
  }
  if (window.ResizeObserver) new ResizeObserver(resize).observe(stage);
  window.addEventListener('resize', resize);

  /* ---------- Mulai ---------- */
  syncButtons(); updateStatus(); resize(); recompute(); renderProps(); draw();
})();
