<section class="card center">
  <h1>Oops</h1>
  <p><?= h($pesan ?? 'Halaman atau proyek yang Anda cari tidak ditemukan.') ?></p>
  <p><a class="btn" href="<?= h(url()) ?>">← Kembali ke daftar proyek</a></p>
</section>
