<?php
/** @var array $proj */
$tipeCfg = [];
foreach ($proj['tipe'] as $i => $t) {
    $tipeCfg[] = [
        'id' => $t['id'], 'nama' => $t['nama'], 'warna' => WARNA_TIPE[$i % count(WARNA_TIPE)],
        'luas_tanah' => (float)$t['luas_tanah'],
        'lebar' => (float)$t['lebar_kavling'], 'panjang' => (float)$t['panjang_kavling'],
    ];
}
$fasumCfg = [];
foreach (FASUM_JENIS as $k => [$lbl, , $warna]) $fasumCfg[$k] = ['label' => $lbl, 'warna' => $warna];

$cfg = [
    'objs' => $proj['siteplan']['objects'],
    'lahan' => ['panjang' => (float)$proj['lahan']['panjang'], 'lebar' => (float)$proj['lahan']['lebar']],
    'tipe' => $tipeCfg,
    'fasum' => $fasumCfg,
    'rasioMax' => (float)$proj['params']['rasio_kavling_max'],
    'csrf' => csrf_token(),
    'urls' => [
        'save' => url('siteplan_save', ['id' => $proj['id']]),
        'generate' => url('siteplan_generate', ['id' => $proj['id']]),
        'rab' => url('rab', ['id' => $proj['id']]),
        'tipe' => url('tipe', ['id' => $proj['id']]),
    ],
    'nama' => $proj['nama'],
];
$json = json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<div class="sp" id="sp">
  <div class="sp-bar no-print" role="toolbar" aria-label="Alat gambar">
    <div class="grp" role="group" aria-label="Alat">
      <button type="button" class="tool on" data-tool="select" title="Pilih & geser (V)">↖ Pilih</button>
      <button type="button" class="tool" data-tool="road" title="Gambar jalan (J)">▬ Jalan</button>
      <button type="button" class="tool" data-tool="lot" title="Gambar/tempel kavling (K). Klik saja untuk menaruh satu kavling ukuran tipe." >▢ Kavling</button>
      <button type="button" class="tool" data-tool="fasum" title="Gambar fasilitas umum (F)">❖ Fasum</button>
      <button type="button" class="tool" data-tool="pan" title="Geser kanvas (H / tahan Spasi)">✥ Geser</button>
    </div>
    <div class="grp">
      <label class="mini">Tipe <select id="selTipe" aria-label="Tipe untuk kavling baru"></select></label>
      <label class="mini">Fasum <select id="selJenis" aria-label="Jenis fasum baru"></select></label>
      <label class="mini">Snap <select id="selSnap" aria-label="Snap grid">
        <option value="0.5">0,5 m</option><option value="1" selected>1 m</option><option value="2">2 m</option><option value="5">5 m</option>
      </select></label>
    </div>
    <div class="grp">
      <button type="button" id="bUndo" title="Urungkan (Ctrl+Z)">↶</button>
      <button type="button" id="bRedo" title="Ulangi (Ctrl+Y)">↷</button>
      <button type="button" id="bZoomOut" title="Perkecil">−</button>
      <button type="button" id="bFit" title="Pas ke layar">⤢</button>
      <button type="button" id="bZoomIn" title="Perbesar">+</button>
    </div>
    <div class="grp right">
      <span id="status" class="status" aria-live="polite"></span>
      <button type="button" id="bPng" title="Unduh gambar PNG">PNG</button>
      <button type="button" id="bSave" class="primary" title="Simpan (Ctrl+S)">💾 Simpan</button>
    </div>
  </div>

  <div class="sp-main">
    <div class="stage" id="stage">
      <canvas id="cv" tabindex="0" aria-label="Kanvas site plan"></canvas>
      <div class="coord" id="coord">—</div>
      <div class="help no-print">Geser: tahan <kbd>Spasi</kbd> atau alat Geser · Zoom: roda mouse · Banyak objek: <kbd>Shift</kbd>+klik / seret kotak · <kbd>Del</kbd> hapus · <kbd>Ctrl+D</kbd> gandakan · <kbd>R</kbd> putar 90°</div>
    </div>

    <aside class="side no-print">
      <section class="panel" id="panelProps">
        <h3>Properti</h3>
        <div id="props" class="props"><p class="muted small">Pilih objek di kanvas untuk mengubah ukuran, tipe, atau labelnya.</p></div>
      </section>

      <section class="panel">
        <h3>Layout otomatis</h3>
        <p class="muted small">Membuat jalan, kavling, dan fasum sekaligus dalam lahan <?= h(angka((float)$proj['lahan']['panjang'], 0)) ?> × <?= h(angka((float)$proj['lahan']['lebar'], 0)) ?> m. Mengganti isi site plan saat ini.</p>
        <div class="form-grid two tight">
          <label class="fld"><span>Tipe kavling</span><select id="gTipe"></select></label>
          <label class="fld"><span>Rasio kavling maks (%)</span><input type="number" id="gRasio" step="1" min="10" max="95"></label>
          <label class="fld"><span>Jalan utama (m)</span><input type="number" id="gJalanUtama" value="8" step="0.5" min="3" max="30"></label>
          <label class="fld"><span>Jalan lingkungan (m)</span><input type="number" id="gJalan" value="6" step="0.5" min="3" max="20"></label>
        </div>
        <button type="button" class="btn small block-btn" id="bGen">⚡ Buat layout</button>
      </section>

      <section class="panel">
        <h3>Statistik</h3>
        <div id="stats" class="stats-mini"></div>
        <div id="warns" class="warns"></div>
        <p class="small"><a href="<?= h(url('rab', ['id' => $proj['id']])) ?>">Lihat RAB →</a> <span class="muted">(simpan dulu agar RAB mengikuti gambar)</span></p>
      </section>
    </aside>
  </div>
</div>

<script>window.SP_CONFIG = <?= $json ?>;</script>
<script src="assets/siteplan.js"></script>
