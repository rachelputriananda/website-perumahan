<?php
declare(strict_types=1);

/** Penyimpanan proyek berbasis file JSON (tanpa database). */

function project_path(string $id): string
{
    if (!valid_id($id)) {
        throw new InvalidArgumentException('ID proyek tidak valid');
    }
    return PROJECT_DIR . '/' . $id . '.json';
}

function project_load(string $id): ?array
{
    if (!valid_id($id)) return null;
    $f = project_path($id);
    if (!is_file($f)) return null;
    $j = json_decode((string)file_get_contents($f), true);
    return is_array($j) ? project_normalize($j) : null;
}

function project_save(array $p): void
{
    if (!is_dir(PROJECT_DIR)) mkdir(PROJECT_DIR, 0775, true);
    $p['updated_at'] = date('c');
    $f = project_path($p['id']);
    $tmp = $f . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false) {
        throw new RuntimeException('Gagal menyimpan proyek');
    }
    rename($tmp, $f); // atomik: tidak meninggalkan file setengah tertulis
}

function project_delete(string $id): void
{
    $f = project_path($id);
    if (is_file($f)) unlink($f);
}

function project_list(): array
{
    $out = [];
    foreach (glob(PROJECT_DIR . '/*.json') ?: [] as $f) {
        $j = json_decode((string)file_get_contents($f), true);
        if (is_array($j) && isset($j['id'])) $out[] = project_normalize($j);
    }
    usort($out, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
    return $out;
}

function params_default(): array
{
    return [
        'tinggi_urugan'    => 0.30,  // m, rata-rata urugan pematangan lahan
        'peninggian_lantai' => 0.30, // m, urugan peninggi lantai rumah
        'tinggi_dinding'   => 3.0,   // m per lantai
        'jenis_jalan'      => 'paving', // paving | beton
        'tebal_sirtu'      => 0.15,  // m
        'sisi_drainase'    => 2,     // 1 atau 2 sisi jalan
        'jarak_pju'        => 30,    // m
        'rumah_per_sumur'  => 60,    // unit rumah per paket sumur bor
        'pagar_keliling'   => 1,
        'jumlah_gerbang'   => 1,
        'rasio_kavling_max' => 60,   // % — sesuaikan dengan peraturan daerah
        'overhead_persen'  => 10,
        'kontingensi_persen' => 5,
        'ppn_persen'       => 11,
        'harga_tanah_m2'   => 0,
        'perizinan_persen' => 2,
        'pemasaran_persen' => 3,
    ];
}

function tipe_default_list(): array
{
    $mk = fn($id, $nama, $lb, $lt, $lw, $ld, $kt, $km, $spec, $jual) => [
        'id' => $id, 'nama' => $nama, 'luas_bangunan' => $lb, 'luas_tanah' => $lt,
        'lebar_kavling' => $lw, 'panjang_kavling' => $ld, 'lantai' => 1,
        'kamar_tidur' => $kt, 'kamar_mandi' => $km, 'spec' => $spec, 'harga_jual' => $jual,
    ];
    return [
        $mk('t1', 'Tipe 36', 36, 72, 6, 12, 2, 1,
            ['atap' => 'metal', 'dinding' => 'bata_ringan', 'lantai' => 'keramik40', 'plafon' => 'pvc', 'sanitair' => 'jongkok'], 320000000),
        $mk('t2', 'Tipe 45', 45, 84, 7, 12, 2, 1,
            ['atap' => 'metal', 'dinding' => 'bata_ringan', 'lantai' => 'keramik40', 'plafon' => 'gypsum', 'sanitair' => 'duduk'], 400000000),
        $mk('t3', 'Tipe 60', 60, 120, 8, 15, 3, 2,
            ['atap' => 'beton', 'dinding' => 'bata_ringan', 'lantai' => 'keramik60', 'plafon' => 'gypsum', 'sanitair' => 'duduk'], 560000000),
    ];
}

/** Lengkapi field yang belum ada (kompatibilitas data lama) dan bersihkan tipe. */
function project_normalize(array $p): array
{
    $p['params'] = array_replace(params_default(), is_array($p['params'] ?? null) ? $p['params'] : []);
    $p['harga']  = array_replace(harga_awal(), is_array($p['harga'] ?? null) ? $p['harga'] : []);
    $p['lahan']  = array_replace(['panjang' => 100.0, 'lebar' => 60.0], is_array($p['lahan'] ?? null) ? $p['lahan'] : []);
    $p['tipe']   = array_values(is_array($p['tipe'] ?? null) ? $p['tipe'] : []);
    $p['siteplan'] = ['objects' => array_values($p['siteplan']['objects'] ?? [])];
    return $p;
}

function project_new(array $in): array
{
    return [
        'id' => bin2hex(random_bytes(5)),
        'nama' => $in['nama'] ?? 'Perumahan Baru',
        'lokasi' => $in['lokasi'] ?? '',
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'lahan' => ['panjang' => (float)($in['panjang'] ?? 100), 'lebar' => (float)($in['lebar'] ?? 60)],
        'params' => params_default(),
        'harga' => harga_awal(),
        'tipe' => tipe_default_list(),
        'siteplan' => ['objects' => []],
    ];
}

/** Ambil proyek dari ?id= atau tampilkan 404. */
function project_or_404(): array
{
    $p = project_load((string)($_GET['id'] ?? $_POST['id'] ?? ''));
    if (!$p) {
        http_response_code(404);
        render('notfound', [], 'Tidak ditemukan');
        exit;
    }
    return $p;
}
