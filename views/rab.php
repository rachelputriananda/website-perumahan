<?php
/** @var array $proj @var array $R */
$P = $proj['params'];
$S = $R['stat'];
$K = $R['kelayakan'];
$kom = $R['komposisi'];
$komTotal = max(1.0, array_sum($kom));
$komLabel = ['bahan' => 'Bahan', 'upah' => 'Upah', 'alat' => 'Alat', 'borongan' => 'Borongan'];
$matLabel = ['bahan' => 'Bahan', 'alat' => 'Alat', 'upah' => 'Upah tenaga'];
?>
<div class="page-head">
  <div>
    <h1>Rencana Anggaran Biaya</h1>
    <p class="muted"><?= h($proj['nama']) ?><?= $proj['lokasi'] ? ' · ' . h($proj['lokasi']) : '' ?> · <?= date('d/m/Y') ?></p>
  </div>
  <div class="actions no-print">
    <a class="btn" href="<?= h(url('rab_csv', ['id' => $proj['id']])) ?>">⬇ Unduh CSV (Excel)</a>
    <button class="btn primary" type="button" onclick="window.print()">🖨 Cetak / PDF</button>
  </div>
</div>

<?php if ($S['peringatan']): ?>
  <section class="card warn-card">
    <h2>Perlu perhatian</h2>
    <ul><?php foreach ($S['peringatan'] as $w): ?><li><?= h($w) ?></li><?php endforeach; ?></ul>
  </section>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span>Total RAB</span><strong><?= h(rp_ringkas($R['total'])) ?></strong><small>incl. overhead, kontingensi, PPN</small></div>
  <div class="stat"><span>Rumah</span><strong><?= (int)$S['jumlah_rumah'] ?> unit</strong></div>
  <div class="stat"><span>Rata-rata per rumah</span><strong><?= h(rp_ringkas($R['total_per_rumah'])) ?></strong></div>
  <div class="stat"><span>Per m² bangunan</span><strong><?= h(rp_ringkas($R['per_m2_bangunan'])) ?></strong></div>
</div>

<section class="card">
  <h2>Rekapitulasi</h2>
  <div class="table-wrap">
  <table class="tbl rab">
    <thead><tr><th class="no">No</th><th>Uraian pekerjaan</th><th class="num">Volume</th><th>Sat.</th><th class="num">Harga satuan</th><th class="num">Jumlah</th></tr></thead>
    <?php foreach ($R['bagian'] as $b): ?>
    <tbody>
      <tr class="grp"><td><?= h($b['kode']) ?></td><td colspan="4"><?= h(strtoupper($b['judul'])) ?></td><td class="num"><?= h(rp($b['subtotal'])) ?></td></tr>
      <?php foreach ($b['baris'] as $i => $r): ?>
        <tr><td class="no"><?= h($b['kode']) ?>.<?= $i + 1 ?></td><td><?= h($r['uraian']) ?></td>
            <td class="num"><?= h(angka($r['vol'], 2)) ?></td><td><?= h($r['sat']) ?></td>
            <td class="num"><?= h(rp($r['harga'])) ?></td><td class="num"><?= h(rp($r['jumlah'])) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$b['baris']): ?><tr><td></td><td colspan="5" class="muted">Tidak ada item (belum ada objek terkait di site plan).</td></tr><?php endif; ?>
    </tbody>
    <?php endforeach; ?>
    <tfoot>
      <tr class="sub"><td colspan="5">Subtotal (A+B+C+D)</td><td class="num"><?= h(rp($R['subtotal'])) ?></td></tr>
      <tr><td colspan="5">Overhead &amp; profit <?= h(angka((float)$P['overhead_persen'], 1)) ?>%</td><td class="num"><?= h(rp($R['overhead'])) ?></td></tr>
      <tr><td colspan="5">Kontingensi <?= h(angka((float)$P['kontingensi_persen'], 1)) ?>%</td><td class="num"><?= h(rp($R['kontingensi'])) ?></td></tr>
      <tr class="sub"><td colspan="5">Dasar pengenaan pajak</td><td class="num"><?= h(rp($R['dpp'])) ?></td></tr>
      <tr><td colspan="5">PPN <?= h(angka((float)$P['ppn_persen'], 1)) ?>%</td><td class="num"><?= h(rp($R['ppn'])) ?></td></tr>
      <tr class="total"><td colspan="5">TOTAL RAB</td><td class="num"><?= h(rp($R['total'])) ?></td></tr>
    </tfoot>
  </table>
  </div>
</section>

<section class="card">
  <h2>Komposisi biaya langsung</h2>
  <div class="bar" role="img" aria-label="Komposisi biaya">
    <?php foreach ($komLabel as $k => $lbl): $pct = $kom[$k] / $komTotal * 100; if ($pct < 0.05) continue; ?>
      <span class="seg seg-<?= h($k) ?>" style="width:<?= h(number_format($pct, 2, '.', '')) ?>%" title="<?= h($lbl) ?> <?= h(angka($pct, 1)) ?>%"></span>
    <?php endforeach; ?>
  </div>
  <ul class="legend">
    <?php foreach ($komLabel as $k => $lbl): ?>
      <li><i class="seg-<?= h($k) ?>"></i><?= h($lbl) ?> — <?= h(rp($kom[$k])) ?> (<?= h(angka($kom[$k] / $komTotal * 100, 1)) ?>%)</li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="card">
  <h2>Rincian per unit rumah</h2>
  <p class="muted small">Biaya langsung satu unit (sebelum overhead, kontingensi, dan PPN).</p>
  <?php foreach ($proj['tipe'] as $t): $d = $R['rumah'][$t['id']] ?? null; if (!$d) continue; ?>
  <details>
    <summary><span class="nm"><?= h($t['nama']) ?></span>
      <span class="pr"><?= h(rp($d['total'])) ?> · <?= (int)$d['jumlah_unit'] ?> unit</span></summary>
    <div class="table-wrap">
    <table class="tbl compact rab">
      <thead><tr><th class="no">No</th><th>Uraian</th><th class="num">Volume</th><th>Sat.</th><th class="num">Harga satuan</th><th class="num">Jumlah</th></tr></thead>
      <?php foreach ($d['grup'] as $g): ?>
      <tbody>
        <tr class="grp"><td><?= h($g['kode']) ?></td><td colspan="4"><?= h($g['judul']) ?></td><td class="num"><?= h(rp($g['subtotal'])) ?></td></tr>
        <?php foreach ($g['baris'] as $i => $r): ?>
          <tr><td class="no"><?= $i + 1 ?></td><td><?= h($r['uraian']) ?> <small class="muted">[<?= h($r['ref']) ?>]</small></td>
              <td class="num"><?= h(angka($r['vol'], 2)) ?></td><td><?= h($r['sat']) ?></td>
              <td class="num"><?= h(rp($r['harga'])) ?></td><td class="num"><?= h(rp($r['jumlah'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
      <?php endforeach; ?>
      <tfoot><tr class="total"><td colspan="5">Total per unit <?= h($t['nama']) ?></td><td class="num"><?= h(rp($d['total'])) ?></td></tr></tfoot>
    </table>
    </div>
  </details>
  <?php endforeach; ?>
</section>

<section class="card">
  <h2>Rekap kebutuhan material, upah &amp; alat</h2>
  <p class="muted small">Akumulasi seluruh proyek dari koefisien AHSP (sebelum overhead &amp; pajak). Berguna untuk daftar belanja dan jadwal tenaga kerja.</p>
  <details>
    <summary><span class="nm"><?= count($R['material']) ?> item</span><span class="pr"><?= h(rp(array_sum(array_column($R['material'], 'jumlah')))) ?></span></summary>
    <div class="table-wrap">
    <table class="tbl compact">
      <thead><tr><th>Kode</th><th>Uraian</th><th>Kategori</th><th class="num">Kebutuhan</th><th>Sat.</th><th class="num">Harga</th><th class="num">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($R['material'] as $m): ?>
        <tr><td><code><?= h($m['kode']) ?></code></td><td><?= h($m['nama']) ?></td><td><?= h($matLabel[$m['kat']] ?? $m['kat']) ?></td>
            <td class="num"><?= h(angka($m['qty'], 2)) ?></td><td><?= h($m['sat']) ?></td>
            <td class="num"><?= h(rp($m['harga'])) ?></td><td class="num"><?= h(rp($m['jumlah'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </details>
</section>

<section class="card">
  <h2>Kelayakan sederhana</h2>
  <p class="muted small">Gambaran kasar untuk membandingkan biaya dengan target penjualan. Isi harga jual di menu Tipe Rumah dan harga tanah di Pengaturan.</p>
  <table class="tbl compact">
    <tbody>
      <tr><td>Pendapatan penjualan</td><td class="num"><?= h(rp($K['pendapatan'])) ?></td></tr>
      <tr><td>Biaya konstruksi (RAB)</td><td class="num"><?= h(rp($K['biaya_konstruksi'])) ?></td></tr>
      <tr><td>Biaya tanah (<?= h(angka($S['luas_lahan'], 0)) ?> m² × <?= h(rp((float)$P['harga_tanah_m2'])) ?>)</td><td class="num"><?= h(rp($K['biaya_lahan'])) ?></td></tr>
      <tr><td>Perizinan &amp; legal <?= h(angka((float)$P['perizinan_persen'], 1)) ?>%</td><td class="num"><?= h(rp($K['perizinan'])) ?></td></tr>
      <tr><td>Pemasaran &amp; komisi <?= h(angka((float)$P['pemasaran_persen'], 1)) ?>%</td><td class="num"><?= h(rp($K['pemasaran'])) ?></td></tr>
      <tr class="sub"><td>Total biaya</td><td class="num"><?= h(rp($K['total_biaya'])) ?></td></tr>
      <tr class="total <?= $K['laba'] < 0 ? 'neg' : '' ?>"><td>Laba kotor (<?= h(angka($K['margin'], 1)) ?>% dari pendapatan)</td><td class="num"><?= h(rp($K['laba'])) ?></td></tr>
      <tr><td>Biaya pokok per unit rumah</td><td class="num"><?= h(rp($K['hpp_per_unit'])) ?></td></tr>
    </tbody>
  </table>
</section>

<section class="card notes">
  <h2>Catatan &amp; asumsi</h2>
  <ul>
    <li>Volume rumah dihitung dari model denah persegi panjang (rasio 1 : 1,5) dan faktor sekat per jumlah ruang — cocok untuk estimasi awal, bukan pengganti gambar kerja &amp; BQ detail.</li>
    <li>Harga satuan dasar adalah nilai contoh; koefisien AHSP merupakan pendekatan. Sesuaikan dengan HSPK/harga pasar setempat.</li>
    <li>Biaya tanah, perizinan (PBG, pengesahan site plan, dll.), dan biaya jaringan PLN/PDAM di luar kawasan tidak termasuk RAB kecuali diisi di bagian kelayakan atau harga satuan.</li>
    <li>Luas dan panjang jalan dihitung dari gabungan objek jalan di site plan (persimpangan tidak dihitung ganda).</li>
  </ul>
</section>
