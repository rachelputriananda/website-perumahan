<?php
/** @var array $proj */
$def = harga_default();
$judul = ['upah' => 'Upah tenaga kerja', 'alat' => 'Sewa alat', 'bahan' => 'Bahan & komponen', 'borongan' => 'Harga borongan (fasum & lump sum)'];
$kelompok = [];
foreach ($def as $kode => $d) $kelompok[$d[3]][$kode] = $d;
?>
<div class="page-head">
  <div>
    <h1>Harga satuan dasar</h1>
    <p class="muted">Ubah harga sesuai pasar/HSPK daerah proyek. Semua analisa dan RAB dihitung ulang otomatis.</p>
  </div>
</div>

<div class="flash warn" role="note">
  <strong>Harga di bawah adalah nilai contoh.</strong> Ganti dengan harga yang berlaku di lokasi dan waktu proyek Anda
  (survei toko material, HSPK pemda setempat, atau penawaran supplier).
</div>

<form method="post" action="<?= h(url('harga_save', ['id' => $proj['id']])) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="actions sticky">
    <button class="btn primary" type="submit">Simpan harga</button>
    <button class="btn ghost" type="submit" name="reset" value="1" formnovalidate
            onclick="return confirm('Kembalikan semua harga ke nilai contoh bawaan? Perubahan Anda akan hilang.')">Reset ke contoh</button>
    <input type="search" id="cari" class="search" placeholder="Cari item…" aria-label="Cari item harga">
  </div>

  <?php foreach ($judul as $kat => $jdl): if (empty($kelompok[$kat])) continue; ?>
  <section class="card">
    <h2><?= h($jdl) ?></h2>
    <div class="table-wrap">
    <table class="tbl compact harga-tbl">
      <thead><tr><th>Kode</th><th>Uraian</th><th>Satuan</th><th class="num">Harga (Rp)</th><th class="num">Contoh bawaan</th></tr></thead>
      <tbody>
      <?php foreach ($kelompok[$kat] as $kode => $d):
          $nilai = $proj['harga'][$kode] ?? $d[2]; $ubah = abs((float)$nilai - (float)$d[2]) > 0.001; ?>
        <tr data-cari="<?= h(strtolower($kode . ' ' . $d[0])) ?>">
          <td><code><?= h($kode) ?></code></td>
          <td><?= h($d[0]) ?></td>
          <td><?= h($d[1]) ?></td>
          <td class="num"><input class="cell <?= $ubah ? 'changed' : '' ?>" type="number" name="harga[<?= h($kode) ?>]" value="<?= h((string)$nilai) ?>" min="0" step="any" aria-label="Harga <?= h($d[0]) ?>"></td>
          <td class="num muted"><?= h(angka((float)$d[2], 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </section>
  <?php endforeach; ?>
</form>

<script>
document.getElementById('cari').addEventListener('input', function () {
  var q = this.value.trim().toLowerCase();
  document.querySelectorAll('tr[data-cari]').forEach(function (tr) {
    tr.hidden = q !== '' && tr.dataset.cari.indexOf(q) === -1;
  });
});
</script>
