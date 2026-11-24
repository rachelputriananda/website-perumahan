<?php
/** @var array $proj @var array $A */
$grup = ahsp_grup();
$per = [];
foreach ($A as $kode => $a) $per[$kode[0]][$kode] = $a;
$katLabel = ['upah' => 'Upah', 'bahan' => 'Bahan', 'alat' => 'Alat'];
?>
<div class="page-head">
  <div>
    <h1>Analisa harga satuan pekerjaan (AHSP)</h1>
    <p class="muted">Harga satuan tiap pekerjaan = Σ koefisien × harga dasar. Klik baris untuk melihat komponennya.</p>
  </div>
  <div class="actions"><input type="search" id="cari" class="search" placeholder="Cari pekerjaan…" aria-label="Cari pekerjaan"></div>
</div>

<div class="flash warn" role="note">
  Koefisien merupakan <strong>pendekatan</strong> mengikuti pola analisa SNI/Permen PUPR, bukan salinan resmi.
  Bandingkan dengan AHSP resmi yang berlaku sebelum dipakai untuk dokumen tender atau kontrak.
</div>

<?php foreach ($grup as $huruf => $judul): if (empty($per[$huruf])) continue; ?>
<section class="card ahsp-grup">
  <h2><?= h($huruf) ?>. <?= h($judul) ?></h2>
  <?php foreach ($per[$huruf] as $kode => $a): ?>
  <details data-cari="<?= h(strtolower($kode . ' ' . $a['nama'])) ?>">
    <summary>
      <span class="code"><?= h($kode) ?></span>
      <span class="nm"><?= h($a['nama']) ?></span>
      <span class="pr"><?= h(rp($a['harga'])) ?> / <?= h($a['satuan']) ?></span>
    </summary>
    <div class="table-wrap">
    <table class="tbl compact">
      <thead><tr><th>Kode</th><th>Komponen</th><th>Kat.</th><th>Sat.</th><th class="num">Koefisien</th><th class="num">Harga</th><th class="num">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($a['komponen'] as $k): ?>
        <tr><td><code><?= h($k['kode']) ?></code></td><td><?= h($k['nama']) ?></td><td><?= h($katLabel[$k['kat']] ?? $k['kat']) ?></td>
            <td><?= h($k['sat']) ?></td><td class="num"><?= h(angka($k['koef'], 4)) ?></td>
            <td class="num"><?= h(angka($k['harga'], 0)) ?></td><td class="num"><?= h(angka($k['jumlah'], 0)) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="6">Upah</td><td class="num"><?= h(angka($a['upah'], 0)) ?></td></tr>
        <tr><td colspan="6">Bahan</td><td class="num"><?= h(angka($a['bahan'], 0)) ?></td></tr>
        <?php if ($a['alat'] > 0): ?><tr><td colspan="6">Alat</td><td class="num"><?= h(angka($a['alat'], 0)) ?></td></tr><?php endif; ?>
        <tr class="total"><td colspan="6">Harga satuan per <?= h($a['satuan']) ?></td><td class="num"><?= h(rp($a['harga'])) ?></td></tr>
      </tfoot>
    </table>
    </div>
  </details>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>

<script>
document.getElementById('cari').addEventListener('input', function () {
  var q = this.value.trim().toLowerCase();
  document.querySelectorAll('details[data-cari]').forEach(function (d) {
    d.hidden = q !== '' && d.dataset.cari.indexOf(q) === -1;
    if (q !== '' && !d.hidden) d.open = false;
  });
  document.querySelectorAll('.ahsp-grup').forEach(function (g) {
    g.hidden = q !== '' && !g.querySelector('details:not([hidden])');
  });
});
</script>
