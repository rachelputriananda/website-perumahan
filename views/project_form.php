<?php
$baru = $proj === null;
$P = $p['params'];
$fld = function (string $name, string $label, $val, string $hint = '', string $extra = 'step="any"') {
    echo '<label class="fld"><span>' . h($label) . '</span><input type="number" name="' . h($name) . '" value="' . h((string)$val) . '" ' . $extra . '>';
    if ($hint !== '') echo '<small>' . h($hint) . '</small>';
    echo '</label>';
};
?>
<div class="page-head">
  <h1><?= $baru ? 'Proyek baru' : 'Pengaturan proyek' ?></h1>
</div>

<form method="post" action="<?= h($baru ? url('project_new') : url('project_edit', ['id' => $p['id']])) ?>" class="stack">
  <?= csrf_field() ?>
  <section class="card">
    <h2>Identitas &amp; lahan</h2>
    <div class="form-grid">
      <label class="fld wide2"><span>Nama proyek</span><input type="text" name="nama" value="<?= h($p['nama'] === 'Perumahan Baru' && $baru ? '' : $p['nama']) ?>" maxlength="80" required placeholder="mis. Perumahan Griya Asri"></label>
      <label class="fld wide2"><span>Lokasi</span><input type="text" name="lokasi" value="<?= h($p['lokasi']) ?>" maxlength="120" placeholder="Kecamatan / kota"></label>
      <?php $fld('panjang', 'Panjang lahan (m)', $p['lahan']['panjang'], 'Sisi horizontal pada site plan', 'step="any" min="10" max="5000" required'); ?>
      <?php $fld('lebar', 'Lebar lahan (m)', $p['lahan']['lebar'], 'Sisi vertikal pada site plan', 'step="any" min="10" max="5000" required'); ?>
    </div>
    <?php if ($baru): ?>
      <p class="muted small">Lahan dianggap persegi panjang. Parameter biaya dan jalan bisa diatur setelah proyek dibuat (menu Pengaturan).</p>
    <?php endif; ?>
  </section>

<?php if (!$baru): ?>
  <section class="card">
    <h2>Tata ruang</h2>
    <div class="form-grid">
      <?php $fld('rasio_kavling_max', 'Rasio kavling maksimum (%)', $P['rasio_kavling_max'], 'Peringatan muncul bila terlampaui. Sesuaikan dengan peraturan daerah.'); ?>
      <label class="fld"><span>Perkerasan jalan</span>
        <select name="jenis_jalan">
          <option value="paving" <?= $P['jenis_jalan'] === 'paving' ? 'selected' : '' ?>>Paving block t=6 cm</option>
          <option value="beton" <?= $P['jenis_jalan'] === 'beton' ? 'selected' : '' ?>>Beton K-225 t=15 cm</option>
        </select></label>
      <label class="fld"><span>Drainase</span>
        <select name="sisi_drainase">
          <option value="2" <?= (int)$P['sisi_drainase'] === 2 ? 'selected' : '' ?>>Kiri &amp; kanan jalan</option>
          <option value="1" <?= (int)$P['sisi_drainase'] === 1 ? 'selected' : '' ?>>Satu sisi jalan</option>
        </select></label>
      <?php $fld('jarak_pju', 'Jarak antar PJU (m)', $P['jarak_pju']); ?>
      <?php $fld('rumah_per_sumur', 'Rumah per paket sumur bor', $P['rumah_per_sumur'], 'Dipakai bila air bersih dari sumur bor'); ?>
      <label class="fld chk"><input type="checkbox" name="pagar_keliling" value="1" <?= (int)$P['pagar_keliling'] === 1 ? 'checked' : '' ?>><span>Pagar keliling kawasan</span></label>
      <?php $fld('jumlah_gerbang', 'Jumlah gerbang', $P['jumlah_gerbang'], '', 'step="1" min="0"'); ?>
    </div>
  </section>

  <section class="card">
    <h2>Pekerjaan tanah &amp; rumah</h2>
    <div class="form-grid">
      <?php $fld('tinggi_urugan', 'Tinggi urugan lahan rata-rata (m)', $P['tinggi_urugan'], 'Pematangan seluruh lahan'); ?>
      <?php $fld('tebal_sirtu', 'Tebal lapis sirtu jalan (m)', $P['tebal_sirtu']); ?>
      <?php $fld('peninggian_lantai', 'Peninggian lantai rumah (m)', $P['peninggian_lantai'], 'Urugan di bawah lantai'); ?>
      <?php $fld('tinggi_dinding', 'Tinggi dinding per lantai (m)', $P['tinggi_dinding']); ?>
    </div>
  </section>

  <section class="card">
    <h2>Biaya &amp; pajak</h2>
    <div class="form-grid">
      <?php $fld('overhead_persen', 'Overhead & profit (%)', $P['overhead_persen']); ?>
      <?php $fld('kontingensi_persen', 'Kontingensi (%)', $P['kontingensi_persen']); ?>
      <?php $fld('ppn_persen', 'PPN (%)', $P['ppn_persen'], 'Sesuaikan dengan ketentuan pajak yang berlaku untuk proyek Anda'); ?>
    </div>
    <h3>Kelayakan sederhana</h3>
    <div class="form-grid">
      <?php $fld('harga_tanah_m2', 'Harga beli tanah (Rp/m²)', $P['harga_tanah_m2'], 'Isi agar laba/margin terhitung'); ?>
      <?php $fld('perizinan_persen', 'Perizinan & legal (%)', $P['perizinan_persen'], 'Dari konstruksi + tanah'); ?>
      <?php $fld('pemasaran_persen', 'Pemasaran & komisi (%)', $P['pemasaran_persen'], 'Dari pendapatan penjualan'); ?>
    </div>
  </section>
<?php endif; ?>

  <div class="actions">
    <button class="btn primary" type="submit"><?= $baru ? 'Buat proyek' : 'Simpan pengaturan' ?></button>
    <a class="btn ghost" href="<?= h($baru ? url() : url('project', ['id' => $p['id']])) ?>">Batal</a>
  </div>
</form>
