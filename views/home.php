<div class="page-head">
  <div>
    <h1>Proyek Perumahan</h1>
    <p class="muted">Desain site plan, atur tipe rumah, dan dapatkan RAB otomatis.</p>
  </div>
  <div class="actions">
    <form method="post" action="<?= h(url('project_demo')) ?>" class="inline">
      <?= csrf_field() ?>
      <button class="btn ghost" type="submit">✨ Buat proyek contoh</button>
    </form>
    <a class="btn primary" href="<?= h(url('project_new')) ?>">+ Proyek baru</a>
  </div>
</div>

<?php if (!$items): ?>
  <section class="card center empty">
    <h2>Belum ada proyek</h2>
    <p class="muted">Mulai dengan proyek baru, atau klik <strong>Buat proyek contoh</strong> untuk melihat contoh lengkap
       (layout otomatis 48 kavling + RAB) yang bisa Anda utak-atik.</p>
  </section>
<?php else: ?>
  <div class="grid cards">
  <?php foreach ($items as ['p' => $p, 'R' => $R]): ?>
    <article class="card proj">
      <a class="thumb" href="<?= h(url('project', ['id' => $p['id']])) ?>" aria-label="Buka <?= h($p['nama']) ?>">
        <?= siteplan_svg($p['siteplan']['objects'], $p['lahan'], $p['tipe'], 480) ?>
      </a>
      <h3><a href="<?= h(url('project', ['id' => $p['id']])) ?>"><?= h($p['nama']) ?></a></h3>
      <p class="muted small"><?= h($p['lokasi'] ?: 'Lokasi belum diisi') ?></p>
      <dl class="kv">
        <div><dt>Lahan</dt><dd><?= angka((float)$R['stat']['luas_lahan'], 0) ?> m²</dd></div>
        <div><dt>Rumah</dt><dd><?= (int)$R['stat']['jumlah_rumah'] ?> unit</dd></div>
        <div><dt>Total RAB</dt><dd><strong><?= h(rp_ringkas($R['total'])) ?></strong></dd></div>
      </dl>
      <div class="row-actions">
        <a class="btn small" href="<?= h(url('siteplan', ['id' => $p['id']])) ?>">Site plan</a>
        <a class="btn small" href="<?= h(url('rab', ['id' => $p['id']])) ?>">RAB</a>
        <form method="post" action="<?= h(url('project_delete', ['id' => $p['id']])) ?>" class="inline"
              onsubmit="return confirm('Hapus proyek &quot;<?= h(addslashes($p['nama'])) ?>&quot;? Tindakan ini tidak bisa dibatalkan.')">
          <?= csrf_field() ?>
          <button class="btn small danger" type="submit">Hapus</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
