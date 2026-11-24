<?php
/** @var string $content */
$proj = $proj ?? null;
$tab = $tab ?? '';
$wide = $wide ?? false;
$tabs = $proj ? [
    'project'  => ['Ringkasan', 'project'],
    'siteplan' => ['Site Plan', 'siteplan'],
    'tipe'     => ['Tipe Rumah', 'tipe'],
    'harga'    => ['Harga Satuan', 'harga'],
    'ahsp'     => ['Analisa (AHSP)', 'ahsp'],
    'rab'      => ['RAB', 'rab'],
] : [];
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ? $pageTitle . ' — ' . APP_NAME : APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= $wide ? 'wide' : '' ?>">
<header class="top no-print">
  <div class="top-in">
    <a class="brand" href="<?= h(url()) ?>"><span class="logo" aria-hidden="true">▦</span> <?= h(APP_NAME) ?></a>
    <?php if ($proj): ?>
      <span class="crumb">/ <?= h($proj['nama']) ?></span>
    <?php endif; ?>
  </div>
  <?php if ($proj): ?>
  <nav class="tabs" aria-label="Menu proyek">
    <?php foreach ($tabs as $key => [$label, $route]): ?>
      <a href="<?= h(url($route, ['id' => $proj['id']])) ?>" class="<?= $tab === $key ? 'on' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
    <a class="tab-right <?= $tab === 'project_edit' ? 'on' : '' ?>" href="<?= h(url('project_edit', ['id' => $proj['id']])) ?>">⚙ Pengaturan</a>
  </nav>
  <?php endif; ?>
</header>

<main class="page">
  <?php foreach (flashes() as $f): ?>
    <div class="flash <?= h($f['type']) ?> no-print" role="status"><?= h($f['msg']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<footer class="foot no-print">
  <?= h(APP_NAME) ?> v<?= h(APP_VERSION) ?> — estimasi awal; harga &amp; koefisien adalah contoh, verifikasi sebelum dipakai untuk tender/kontrak.
</footer>
</body>
</html>
