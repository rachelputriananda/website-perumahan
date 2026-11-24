<?php
declare(strict_types=1);

/**
 * Uji mandiri mesin hitung. Jalankan:  php tests/run.php
 * Tidak menyentuh folder data/ (semua di memori).
 */

require __DIR__ . '/../config.php';
foreach (['helpers', 'geometry', 'ahsp', 'storage', 'layout', 'rab'] as $f) require __DIR__ . "/../lib/$f.php";

$gagal = 0;
function cek(string $nama, bool $ok, string $info = ''): void
{
    global $gagal;
    echo ($ok ? '  [ok]   ' : '  [GAGAL] ') . $nama . ($info !== '' ? "  — $info" : '') . "\n";
    if (!$ok) $gagal++;
}
function dekat(float $a, float $b, float $tol = 1e-6): bool { return abs($a - $b) <= $tol * max(1.0, abs($b)); }

echo "Geometri\n";
cek('union dua persegi bertumpuk', dekat(union_area([['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10], ['x' => 5, 'y' => 5, 'w' => 10, 'h' => 10]]), 175));
cek('union persimpangan jalan (+)', dekat(union_area([['x' => 0, 'y' => 4, 'w' => 20, 'h' => 2], ['x' => 9, 'y' => 0, 'w' => 2, 'h' => 10]]), 40 + 20 - 4));
cek('union kosong', union_area([]) === 0.0);

echo "\nGenerator layout\n";
$tipe = tipe_default_list();
$lay = generate_layout(100, 60, ['lebar_jalan_utama' => 8, 'lebar_jalan' => 6, 'rasio_kavling_max' => 60], $tipe[0]);
cek('layout berhasil', $lay['ok'], (string)count($lay['objects']) . ' objek');
$S0 = siteplan_stats($lay['objects'], ['panjang' => 100, 'lebar' => 60], $tipe, params_default());
cek('tanpa objek tumpang tindih / di luar lahan', !array_filter($S0['peringatan'], fn($w) => str_contains($w, 'tumpang') || str_contains($w, 'luar')), implode(' | ', $S0['peringatan']));
cek('rasio kavling <= 60%', $S0['rasio_kavling'] <= 60.0 + 1e-6, angka($S0['rasio_kavling'], 1) . '%');
cek('lahan terlalu sempit ditolak', generate_layout(10, 10, [], $tipe[0])['ok'] === false);

echo "\nRAB\n";
$p = project_new(['nama' => 'Uji', 'panjang' => 100, 'lebar' => 60]);
$p['siteplan']['objects'] = $lay['objects'];
$R = rab_hitung($p);
cek('total > 0', $R['total'] > 0, rp($R['total']));
cek('jumlah rumah = kavling bertipe', $R['stat']['jumlah_rumah'] === $S0['jumlah_kavling'], (string)$R['stat']['jumlah_rumah'] . ' unit');

// Invarian: total komposisi (upah+bahan+alat+borongan) harus sama dengan subtotal
$kom = array_sum($R['komposisi']);
cek('komposisi biaya = subtotal', dekat($kom, $R['subtotal'], 1e-9), rp($kom) . ' vs ' . rp($R['subtotal']));

// Invarian: jumlah rekap material = subtotal - borongan
$mat = array_sum(array_column($R['material'], 'jumlah'));
cek('rekap material = subtotal - borongan', dekat($mat, $R['subtotal'] - $R['komposisi']['borongan'], 1e-9));

cek('PPN & overhead konsisten', dekat($R['total'], $R['subtotal'] * (1 + 0.10 + 0.05) * 1.11, 1e-9));

echo "\nHarga per m² bangunan rumah (tanpa overhead/PPN)\n";
foreach ($p['tipe'] as $t) {
    $d = $R['rumah'][$t['id']];
    $perM2 = $d['total'] / $t['luas_bangunan'];
    printf("  %-8s LB %3d m²  -> %s  (%s/m²)\n", $t['nama'], $t['luas_bangunan'], rp($d['total']), rp($perM2));
    cek($t['nama'] . ' wajar (Rp 2,5–7 jt/m²)', $perM2 > 2.5e6 && $perM2 < 7e6);
}

echo "\nRingkasan proyek contoh\n";
foreach ($R['bagian'] as $b) printf("  %s. %-34s %s\n", $b['kode'], $b['judul'], rp($b['subtotal']));
printf("  Total (incl. overhead, kontingensi, PPN): %s\n", rp($R['total']));
printf("  Per rumah: %s | Per m² bangunan: %s\n", rp($R['total_per_rumah']), rp($R['per_m2_bangunan']));
printf("  Jalan: %s m² (%s m'), rasio kavling %s%%\n", angka($R['stat']['luas_jalan'], 0), angka($R['stat']['panjang_jalan'], 0), angka($R['stat']['rasio_kavling'], 1));

echo "\nPenyimpanan\n";
$cek = str_in("  halo\x07dunia  ", 20);
cek('str_in bersihkan kontrol', $cek === 'halodunia');
cek('num menerima koma desimal', dekat(num('12,5', 0), 12.5));
cek('num menolak non-angka', num('abc', 7) === 7.0);
cek('valid_id menolak path traversal', !valid_id('../etc/passwd'));

echo "\n" . ($gagal === 0 ? "SEMUA UJI LULUS\n" : "$gagal UJI GAGAL\n");
exit($gagal === 0 ? 0 : 1);
