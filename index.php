<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
foreach (['helpers', 'geometry', 'ahsp', 'storage', 'layout', 'rab', 'svg'] as $f) {
    require __DIR__ . '/lib/' . $f . '.php';
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Siapkan folder data & lindungi dari akses langsung (Apache). Untuk Nginx lihat README.
if (!is_dir(PROJECT_DIR)) mkdir(PROJECT_DIR, 0775, true);
if (!is_file(DATA_DIR . '/.htaccess')) file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

$r = (string)($_GET['r'] ?? 'home');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

/** Baca body JSON (untuk endpoint editor site plan). */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw !== false ? $raw : '', true);
    return is_array($j) ? $j : [];
}

/** Ambil nilai pilihan dari daftar yang diizinkan. */
function pilih(mixed $v, array $boleh, string $def): string
{
    return is_string($v) && in_array($v, $boleh, true) ? $v : $def;
}

/** Baca field proyek (nama, lokasi, lahan, parameter) dari form. */
function baca_form_proyek(array $p, bool $lengkap): array
{
    $p['nama'] = str_in($_POST['nama'] ?? '', 80) ?: 'Perumahan Tanpa Nama';
    $p['lokasi'] = str_in($_POST['lokasi'] ?? '', 120);
    $p['lahan']['panjang'] = num($_POST['panjang'] ?? null, (float)$p['lahan']['panjang'], 10, 5000);
    $p['lahan']['lebar']   = num($_POST['lebar'] ?? null, (float)$p['lahan']['lebar'], 10, 5000);
    if (!$lengkap) return $p;

    $d = $p['params'];
    $p['params'] = [
        'tinggi_urugan'     => num($_POST['tinggi_urugan'] ?? null, $d['tinggi_urugan'], 0, 5),
        'peninggian_lantai' => num($_POST['peninggian_lantai'] ?? null, $d['peninggian_lantai'], 0, 2),
        'tinggi_dinding'    => num($_POST['tinggi_dinding'] ?? null, $d['tinggi_dinding'], 2.4, 5),
        'jenis_jalan'       => pilih($_POST['jenis_jalan'] ?? '', ['paving', 'beton'], 'paving'),
        'tebal_sirtu'       => num($_POST['tebal_sirtu'] ?? null, $d['tebal_sirtu'], 0, 1),
        'sisi_drainase'     => (int)num($_POST['sisi_drainase'] ?? null, 2, 1, 2),
        'jarak_pju'         => num($_POST['jarak_pju'] ?? null, $d['jarak_pju'], 10, 200),
        'rumah_per_sumur'   => num($_POST['rumah_per_sumur'] ?? null, $d['rumah_per_sumur'], 1, 1000),
        'pagar_keliling'    => isset($_POST['pagar_keliling']) ? 1 : 0,
        'jumlah_gerbang'    => (int)num($_POST['jumlah_gerbang'] ?? null, 1, 0, 20),
        'rasio_kavling_max' => num($_POST['rasio_kavling_max'] ?? null, $d['rasio_kavling_max'], 10, 95),
        'overhead_persen'   => num($_POST['overhead_persen'] ?? null, $d['overhead_persen'], 0, 100),
        'kontingensi_persen' => num($_POST['kontingensi_persen'] ?? null, $d['kontingensi_persen'], 0, 100),
        'ppn_persen'        => num($_POST['ppn_persen'] ?? null, $d['ppn_persen'], 0, 100),
        'harga_tanah_m2'    => num($_POST['harga_tanah_m2'] ?? null, $d['harga_tanah_m2'], 0, 1e9),
        'perizinan_persen'  => num($_POST['perizinan_persen'] ?? null, $d['perizinan_persen'], 0, 100),
        'pemasaran_persen'  => num($_POST['pemasaran_persen'] ?? null, $d['pemasaran_persen'], 0, 100),
    ];
    return $p;
}

try {
    switch ($r) {

        /* ---------------- Daftar proyek ---------------- */
        case 'home':
            $items = [];
            foreach (project_list() as $p) {
                $R = rab_hitung($p);
                $items[] = ['p' => $p, 'R' => $R];
            }
            render('home', ['items' => $items], 'Proyek');
            break;

        case 'project_new':
            if ($isPost) {
                csrf_verify();
                $p = baca_form_proyek(project_new([]), false);
                project_save($p);
                flash('Proyek dibuat. Lanjut ke tab Site Plan untuk mulai mendesain.');
                redirect(url('siteplan', ['id' => $p['id']]));
            }
            render('project_form', ['proj' => null, 'p' => project_new([])], 'Proyek baru');
            break;

        case 'project_demo':
            if (!$isPost) redirect(url());
            csrf_verify();
            $p = project_new(['nama' => 'Contoh: Perumahan Griya Asri', 'lokasi' => 'Contoh lokasi', 'panjang' => 100, 'lebar' => 60]);
            $lay = generate_layout(100, 60, ['lebar_jalan_utama' => 8, 'lebar_jalan' => 6, 'rasio_kavling_max' => 60], $p['tipe'][0]);
            $p['siteplan']['objects'] = $lay['objects'];
            project_save($p);
            flash('Proyek contoh dibuat dengan layout otomatis. Silakan ubah sesuai kebutuhan.');
            redirect(url('project', ['id' => $p['id']]));

        case 'project_delete':
            if (!$isPost) redirect(url());
            csrf_verify();
            $p = project_or_404();
            project_delete($p['id']);
            flash('Proyek "' . $p['nama'] . '" dihapus.');
            redirect(url());

        /* ---------------- Ringkasan & pengaturan ---------------- */
        case 'project':
            $p = project_or_404();
            $R = rab_hitung($p);
            render('project', ['proj' => $p, 'tab' => 'project', 'R' => $R], $p['nama']);
            break;

        case 'project_edit':
            $p = project_or_404();
            if ($isPost) {
                csrf_verify();
                $p = baca_form_proyek($p, true);
                project_save($p);
                flash('Pengaturan proyek disimpan.');
                redirect(url('project', ['id' => $p['id']]));
            }
            render('project_form', ['proj' => $p, 'p' => $p, 'tab' => 'project_edit'], 'Pengaturan proyek');
            break;

        /* ---------------- Tipe rumah ---------------- */
        case 'tipe':
            $p = project_or_404();
            $R = rab_hitung($p);
            $edit = null;
            foreach ($p['tipe'] as $t) if ($t['id'] === ($_GET['edit'] ?? '')) $edit = $t;
            render('tipe', ['proj' => $p, 'tab' => 'tipe', 'R' => $R, 'edit' => $edit], 'Tipe rumah');
            break;

        case 'tipe_save':
            if (!$isPost) redirect(url());
            csrf_verify();
            $p = project_or_404();
            $opsi = spec_opsi();
            $spec = [];
            foreach ($opsi as $g => $pil) {
                $spec[$g] = pilih($_POST['spec_' . $g] ?? '', array_keys($pil), (string)array_key_first($pil));
            }
            $id = (string)($_POST['tipe_id'] ?? '');
            $idx = null;
            foreach ($p['tipe'] as $i => $t) if ($t['id'] === $id) $idx = $i;
            $t = [
                'id' => $idx !== null ? $id : 't' . bin2hex(random_bytes(3)),
                'nama' => str_in($_POST['nama'] ?? '', 40) ?: 'Tipe baru',
                'luas_bangunan' => num($_POST['luas_bangunan'] ?? null, 36, 10, 1000),
                'luas_tanah' => num($_POST['luas_tanah'] ?? null, 72, 20, 5000),
                'lebar_kavling' => num($_POST['lebar_kavling'] ?? null, 6, 3, 60),
                'panjang_kavling' => num($_POST['panjang_kavling'] ?? null, 12, 3, 100),
                'lantai' => (int)num($_POST['lantai'] ?? null, 1, 1, 3),
                'kamar_tidur' => (int)num($_POST['kamar_tidur'] ?? null, 2, 0, 10),
                'kamar_mandi' => (int)num($_POST['kamar_mandi'] ?? null, 1, 0, 10),
                'spec' => $spec,
                'harga_jual' => num($_POST['harga_jual'] ?? null, 0, 0, 1e11),
            ];
            if ($idx !== null) $p['tipe'][$idx] = $t; else $p['tipe'][] = $t;
            project_save($p);
            flash('Tipe "' . $t['nama'] . '" disimpan.');
            redirect(url('tipe', ['id' => $p['id']]));

        case 'tipe_delete':
            if (!$isPost) redirect(url());
            csrf_verify();
            $p = project_or_404();
            $id = (string)($_POST['tipe_id'] ?? '');
            $p['tipe'] = array_values(array_filter($p['tipe'], fn($t) => $t['id'] !== $id));
            foreach ($p['siteplan']['objects'] as &$o) if ($o['tipe_id'] === $id) $o['tipe_id'] = '';
            unset($o);
            project_save($p);
            flash('Tipe dihapus. Kavling yang memakainya kini tanpa tipe.');
            redirect(url('tipe', ['id' => $p['id']]));

        /* ---------------- Harga & AHSP ---------------- */
        case 'harga':
            $p = project_or_404();
            render('harga', ['proj' => $p, 'tab' => 'harga'], 'Harga satuan');
            break;

        case 'harga_save':
            if (!$isPost) redirect(url());
            csrf_verify();
            $p = project_or_404();
            if (isset($_POST['reset'])) {
                $p['harga'] = harga_awal();
                flash('Harga dikembalikan ke nilai contoh bawaan.');
            } else {
                $in = is_array($_POST['harga'] ?? null) ? $_POST['harga'] : [];
                foreach (harga_default() as $k => $d) {
                    if (array_key_exists($k, $in)) $p['harga'][$k] = num($in[$k], (float)$d[2], 0, 1e12);
                }
                flash('Harga satuan disimpan. RAB dihitung ulang otomatis.');
            }
            project_save($p);
            redirect(url('harga', ['id' => $p['id']]));

        case 'ahsp':
            $p = project_or_404();
            render('ahsp', ['proj' => $p, 'tab' => 'ahsp', 'A' => ahsp_hitung($p['harga'])], 'Analisa harga satuan');
            break;

        /* ---------------- Site plan ---------------- */
        case 'siteplan':
            $p = project_or_404();
            render('siteplan', ['proj' => $p, 'tab' => 'siteplan', 'wide' => true], 'Site plan');
            break;

        case 'siteplan_save':
            if (!$isPost) json_out(['ok' => false, 'pesan' => 'Metode tidak valid'], 405);
            csrf_verify();
            $p = project_or_404();
            $b = json_body();
            $p['siteplan']['objects'] = siteplan_sanitize(is_array($b['objects'] ?? null) ? $b['objects'] : [], $p['tipe']);
            project_save($p);
            $S = siteplan_stats($p['siteplan']['objects'], $p['lahan'], $p['tipe'], $p['params']);
            json_out(['ok' => true, 'tersimpan' => date('H:i:s'), 'jumlah' => count($p['siteplan']['objects']),
                      'peringatan' => $S['peringatan']]);

        case 'siteplan_generate':
            if (!$isPost) json_out(['ok' => false, 'pesan' => 'Metode tidak valid'], 405);
            csrf_verify();
            $p = project_or_404();
            $b = json_body();
            $tipe = null;
            foreach ($p['tipe'] as $t) if ($t['id'] === ($b['tipe_id'] ?? '')) $tipe = $t;
            if (!$tipe) json_out(['ok' => false, 'pesan' => 'Pilih tipe rumah untuk kavling.'], 422);
            $lay = generate_layout((float)$p['lahan']['panjang'], (float)$p['lahan']['lebar'], [
                'lebar_jalan_utama' => num($b['lebar_jalan_utama'] ?? null, 8, 3, 30),
                'lebar_jalan' => num($b['lebar_jalan'] ?? null, 6, 3, 20),
                'rasio_kavling_max' => num($b['rasio_kavling_max'] ?? null, (float)$p['params']['rasio_kavling_max'], 10, 95),
            ], $tipe);
            json_out($lay, $lay['ok'] ? 200 : 422);

        /* ---------------- RAB ---------------- */
        case 'rab':
            $p = project_or_404();
            render('rab', ['proj' => $p, 'tab' => 'rab', 'R' => rab_hitung($p)], 'RAB');
            break;

        case 'rab_csv':
            $p = project_or_404();
            $csv = rab_csv($p, rab_hitung($p));
            $nama = preg_replace('/[^A-Za-z0-9_-]+/', '_', $p['nama']) ?: 'rab';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="RAB_' . $nama . '.csv"');
            echo $csv;
            exit;

        default:
            http_response_code(404);
            render('notfound', [], 'Tidak ditemukan');
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    render('notfound', ['pesan' => 'Terjadi kesalahan di server. Cek log PHP untuk detailnya.'], 'Kesalahan');
}
