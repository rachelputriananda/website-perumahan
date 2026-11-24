<?php
/** @var array $proj @var array $R */
$S = $R['stat'];
$tipeMap = [];
foreach ($proj['tipe'] as $i => $t) $tipeMap[$t['id']] = $t + ['warna' => WARNA_TIPE[$i % count(WARNA_TIPE)]];
?>
<div class="page-head">
  <div>
    <h1><?= h($proj['nama']) ?></h1>
    <p class="muted"><?= h($proj['lokasi'] ?: 'Lokasi belum diisi') ?> · lahan <?= angka((float)$proj['lahan']['panjang'], 0) ?> × <?= angka((float)$proj['lahan']['lebar'], 0) ?> m</p>
  </div>
  <div class="actions">
    <a class="btn" href="<?= h(url('siteplan', ['id' => $proj['id']])) ?>">Buka site plan</a>
    <a class="btn primary" href="<?= h(url('rab', ['id' => $proj['id']])) ?>">Lihat RAB</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><span>Luas lahan</span><strong><?= angka($S['luas_lahan'], 0) ?> m²</strong></div>
  <div class="stat"><span>Rumah</span><strong><?= (int)$S['jumlah_rumah'] ?> unit</strong><small><?= (int)$S['jumlah_kavling'] ?> kavling</small></div>
  <div class="stat"><span>Rasio kavling</span><strong><?= angka($S['rasio_kavling'], 1) ?>%</strong><small>jalan <?= angka($S['rasio_jalan'], 1) ?>% · fasum <?= angka($S['rasio_fasum'], 1) ?>%</small></div>
  <div class="stat"><span>Total RAB</span><strong><?= h(rp_ringkas($R['total'])) ?></strong><small>incl. overhead &amp; PPN</small></div>
  <div class="stat"><span>Rata-rata per rumah</span><strong><?= h(rp_ringkas($R['total_per_rumah'])) ?></strong><small>termasuk infrastruktur</small></div>
</div>

<?php if ($S['peringatan']): ?>
  <section class="card warn-card">
    <h2>Perlu perhatian</h2>
    <ul>
      <?php foreach ($S['peringatan'] as $w): ?><li><?= h($w) ?></li><?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<div class="two-col">
  <section class="card">
    <div class="card-head"><h2>Pratinjau site plan</h2>
      <a class="btn small" href="<?= h(url('siteplan', ['id' => $proj['id']])) ?>">Edit</a></div>
    <?php if (!$proj['siteplan']['objects']): ?>
      <p class="muted">Site plan masih kosong. Buka editor dan gunakan <strong>Layout otomatis</strong> atau gambar manual.</p>
    <?php endif; ?>
    <?= siteplan_svg($proj['siteplan']['objects'], $proj['lahan'], $proj['tipe'], 720) ?>
    <ul class="legend">
      <?php foreach ($tipeMap as $t): ?>
        <li><i style="background:<?= h($t['warna']) ?>"></i><?= h($t['nama']) ?> (<?= (int)($S['per_tipe'][$t['id']]['jumlah'] ?? 0) ?>)</li>
      <?php endforeach; ?>
      <li><i style="background:#9aa0a8"></i>Jalan</li>
      <?php foreach (FASUM_JENIS as $k => [$lbl, , $warna]): if (isset($S['fasum_jenis'][$k])): ?>
        <li><i style="background:<?= h($warna) ?>"></i><?= h($lbl) ?></li>
      <?php endif; endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <h2>Rekap biaya</h2>
    <table class="tbl compact">
      <tbody>
      <?php foreach ($R['bagian'] as $b): ?>
        <tr><td><?= h($b['kode']) ?>. <?= h($b['judul']) ?></td><td class="num"><?= h(rp($b['subtotal'])) ?></td></tr>
      <?php endforeach; ?>
        <tr class="sub"><td>Subtotal</td><td class="num"><?= h(rp($R['subtotal'])) ?></td></tr>
        <tr><td>Overhead &amp; profit <?= angka((float)$proj['params']['overhead_persen'], 1) ?>%</td><td class="num"><?= h(rp($R['overhead'])) ?></td></tr>
        <tr><td>Kontingensi <?= angka((float)$proj['params']['kontingensi_persen'], 1) ?>%</td><td class="num"><?= h(rp($R['kontingensi'])) ?></td></tr>
        <tr><td>PPN <?= angka((float)$proj['params']['ppn_persen'], 1) ?>%</td><td class="num"><?= h(rp($R['ppn'])) ?></td></tr>
        <tr class="total"><td>Total</td><td class="num"><?= h(rp($R['total'])) ?></td></tr>
      </tbody>
    </table>
    <p class="muted small">Rata-rata <?= h(rp($R['per_m2_bangunan'])) ?> per m² bangunan (termasuk porsi infrastruktur &amp; fasum).</p>
  </section>
</div>

<section class="card">
  <h2>Rumah per tipe</h2>
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr><th>Tipe</th><th class="num">Unit</th><th class="num">LB / LT</th><th class="num">Biaya konstruksi / unit</th><th class="num">Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($tipeMap as $t):
        $n = (int)($S['per_tipe'][$t['id']]['jumlah'] ?? 0);
        $biaya = $R['rumah'][$t['id']]['total'] ?? 0; ?>
      <tr>
        <td><i class="dot" style="background:<?= h($t['warna']) ?>"></i><?= h($t['nama']) ?></td>
        <td class="num"><?= $n ?></td>
        <td class="num"><?= angka((float)$t['luas_bangunan'], 0) ?> / <?= angka((float)$t['luas_tanah'], 0) ?> m²</td>
        <td class="num"><?= h(rp($biaya)) ?></td>
        <td class="num"><?= h(rp($biaya * $n)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="muted small">Biaya konstruksi per unit belum termasuk infrastruktur, fasum, overhead, kontingensi, dan PPN.</p>
</section>
