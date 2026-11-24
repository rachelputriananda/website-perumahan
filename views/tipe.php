<?php
/** @var array $proj @var array $R @var array|null $edit */
$S = $R['stat'];
$opsi = spec_opsi();
$labelGrup = ['atap' => 'Penutup atap', 'dinding' => 'Dinding', 'lantai' => 'Lantai utama', 'plafon' => 'Plafon', 'sanitair' => 'Closet'];
$e = $edit ?? [
    'id' => '', 'nama' => '', 'luas_bangunan' => 36, 'luas_tanah' => 72, 'lebar_kavling' => 6, 'panjang_kavling' => 12,
    'lantai' => 1, 'kamar_tidur' => 2, 'kamar_mandi' => 1, 'harga_jual' => 0,
    'spec' => ['atap' => 'metal', 'dinding' => 'bata_ringan', 'lantai' => 'keramik40', 'plafon' => 'pvc', 'sanitair' => 'jongkok'],
];
?>
<div class="page-head">
  <div>
    <h1>Tipe rumah</h1>
    <p class="muted">Volume pekerjaan dihitung otomatis dari luas bangunan, jumlah ruang, dan spesifikasi yang dipilih.</p>
  </div>
</div>

<div class="two-col wide-left">
  <section class="card">
    <h2>Daftar tipe</h2>
    <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Tipe</th><th class="num">LB/LT</th><th>Ruang</th><th class="num">Di site plan</th><th class="num">Biaya/unit</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($proj['tipe'] as $i => $t):
          $d = $R['rumah'][$t['id']] ?? ['total' => 0]; ?>
        <tr>
          <td><i class="dot" style="background:<?= h(WARNA_TIPE[$i % count(WARNA_TIPE)]) ?>"></i><strong><?= h($t['nama']) ?></strong>
              <small class="muted block"><?= (int)$t['lantai'] ?> lantai · kavling <?= angka((float)$t['lebar_kavling'], 1) ?>×<?= angka((float)$t['panjang_kavling'], 1) ?> m</small></td>
          <td class="num"><?= angka((float)$t['luas_bangunan'], 0) ?>/<?= angka((float)$t['luas_tanah'], 0) ?></td>
          <td><?= (int)$t['kamar_tidur'] ?> KT · <?= (int)$t['kamar_mandi'] ?> KM</td>
          <td class="num"><?= (int)($S['per_tipe'][$t['id']]['jumlah'] ?? 0) ?> unit</td>
          <td class="num"><?= h(rp($d['total'])) ?><small class="muted block"><?= h(rp($d['total'] / max(1, (float)$t['luas_bangunan']))) ?>/m²</small></td>
          <td class="row-actions">
            <a class="btn small" href="<?= h(url('tipe', ['id' => $proj['id'], 'edit' => $t['id']])) ?>">Ubah</a>
            <form method="post" action="<?= h(url('tipe_delete', ['id' => $proj['id']])) ?>" class="inline"
                  onsubmit="return confirm('Hapus tipe ini? Kavling yang memakainya menjadi tanpa tipe.')">
              <?= csrf_field() ?><input type="hidden" name="tipe_id" value="<?= h($t['id']) ?>">
              <button class="btn small danger" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$proj['tipe']): ?><tr><td colspan="6" class="muted">Belum ada tipe. Tambahkan di samping.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>

    <?php if ($edit && isset($R['rumah'][$edit['id']])): $dm = $R['rumah'][$edit['id']]['dimensi']; ?>
      <h3>Asumsi geometri <?= h($edit['nama']) ?></h3>
      <p class="muted small">Denah diasumsikan persegi panjang rasio 1 : 1,5 sebagai dasar menghitung volume.</p>
      <dl class="kv wrap">
        <div><dt>Denah</dt><dd><?= angka($dm['denah'][0], 1) ?> × <?= angka($dm['denah'][1], 1) ?> m</dd></div>
        <div><dt>Keliling luar</dt><dd><?= angka($dm['keliling'], 1) ?> m</dd></div>
        <div><dt>Total panjang dinding</dt><dd><?= angka($dm['panjang_dinding'], 1) ?> m</dd></div>
        <div><dt>Dinding netto</dt><dd><?= angka($dm['dinding_netto'], 1) ?> m²</dd></div>
        <div><dt>Beton struktur</dt><dd><?= angka($dm['beton_m3'], 2) ?> m³</dd></div>
        <div><dt>Luas atap</dt><dd><?= angka($dm['luas_atap'], 1) ?> m²</dd></div>
      </dl>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2><?= $edit ? 'Ubah tipe' : 'Tambah tipe' ?></h2>
    <form method="post" action="<?= h(url('tipe_save', ['id' => $proj['id']])) ?>" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="tipe_id" value="<?= h($e['id']) ?>">
      <label class="fld"><span>Nama tipe</span><input type="text" name="nama" value="<?= h($e['nama']) ?>" maxlength="40" required placeholder="mis. Tipe 36"></label>
      <div class="form-grid two">
        <label class="fld"><span>Luas bangunan (m²)</span><input type="number" name="luas_bangunan" value="<?= h($e['luas_bangunan']) ?>" step="any" min="10" max="1000" required></label>
        <label class="fld"><span>Luas tanah (m²)</span><input type="number" name="luas_tanah" value="<?= h($e['luas_tanah']) ?>" step="any" min="20" max="5000" required></label>
        <label class="fld"><span>Lebar kavling (m)</span><input type="number" name="lebar_kavling" value="<?= h($e['lebar_kavling']) ?>" step="any" min="3" max="60" required></label>
        <label class="fld"><span>Panjang kavling (m)</span><input type="number" name="panjang_kavling" value="<?= h($e['panjang_kavling']) ?>" step="any" min="3" max="100" required></label>
        <label class="fld"><span>Jumlah lantai</span>
          <select name="lantai"><?php foreach ([1, 2, 3] as $n): ?><option value="<?= $n ?>" <?= (int)$e['lantai'] === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></label>
        <label class="fld"><span>Harga jual (Rp/unit)</span><input type="number" name="harga_jual" value="<?= h($e['harga_jual']) ?>" step="1000" min="0"></label>
        <label class="fld"><span>Kamar tidur</span><input type="number" name="kamar_tidur" value="<?= h($e['kamar_tidur']) ?>" min="0" max="10" step="1"></label>
        <label class="fld"><span>Kamar mandi</span><input type="number" name="kamar_mandi" value="<?= h($e['kamar_mandi']) ?>" min="0" max="10" step="1"></label>
      </div>
      <h3>Spesifikasi</h3>
      <div class="form-grid two">
        <?php foreach ($opsi as $g => $pil): ?>
          <label class="fld"><span><?= h($labelGrup[$g] ?? $g) ?></span>
            <select name="spec_<?= h($g) ?>">
              <?php foreach ($pil as $key => [$lbl]): ?>
                <option value="<?= h($key) ?>" <?= ($e['spec'][$g] ?? '') === $key ? 'selected' : '' ?>><?= h($lbl) ?></option>
              <?php endforeach; ?>
            </select></label>
        <?php endforeach; ?>
      </div>
      <div class="actions">
        <button class="btn primary" type="submit"><?= $edit ? 'Simpan perubahan' : 'Tambah tipe' ?></button>
        <?php if ($edit): ?><a class="btn ghost" href="<?= h(url('tipe', ['id' => $proj['id']])) ?>">Batal</a><?php endif; ?>
      </div>
    </form>
  </section>
</div>
