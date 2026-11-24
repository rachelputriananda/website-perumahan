<?php
declare(strict_types=1);

/**
 * GENERATOR SITE PLAN OTOMATIS (pola "sisir"/double-loaded street)
 *
 *   ┌────┬───────────────────────────┬──────────┐
 *   │ J  │ [kavling][kavling] ...    │          │
 *   │ A  │ ===== jalan lingkungan == │  Fasum   │
 *   │ L  │ [kavling][kavling] ...    │ (taman,  │
 *   │ A  │ [kavling][kavling] ...    │  ibadah) │
 *   │ N  │ ===== jalan lingkungan == │          │
 *   │    │ [kavling][kavling] ...    │          │
 *   └────┴───────────────────────────┴──────────┘
 *
 * Jalan utama di sisi kiri (x=0), jalan lingkungan bercabang ke kanan,
 * kavling berhadapan di kedua sisi jalan. Bila rasio kavling melebihi
 * batas, kolom kavling di ujung kanan diubah menjadi zona fasum.
 *
 * Semua satuan meter. Hasil: daftar objek siteplan.
 */
function generate_layout(float $P, float $L, array $o, array $tipe): array
{
    $rwMain = max(3.0, (float)($o['lebar_jalan_utama'] ?? 8));
    $rw     = max(3.0, (float)($o['lebar_jalan'] ?? 6));
    $lw     = max(2.0, (float)($tipe['lebar_kavling'] ?? 6));
    $ld     = max(2.0, (float)($tipe['panjang_kavling'] ?? 12));
    $maxR   = max(10.0, min(95.0, (float)($o['rasio_kavling_max'] ?? 60)));

    $W    = $P - $rwMain;
    $cols = (int)floor($W / $lw + 1e-9);
    $cell = 2 * $ld + $rw;
    $k    = (int)floor($L / $cell + 1e-9);

    if ($cols < 1 || $k < 1) {
        return ['ok' => false, 'pesan' => 'Lahan terlalu sempit untuk ukuran kavling dan jalan yang dipilih.', 'objects' => []];
    }

    // Kurangi kolom di ujung kanan jika rasio kavling melebihi batas.
    $lotArea = $lw * $ld;
    $total   = $P * $L;
    $nLots   = 2 * $k * $cols;
    $c = 0;
    if ($nLots * $lotArea / $total * 100 > $maxR) {
        $kelebihan = $nLots * $lotArea - $maxR / 100 * $total;
        $c = (int)ceil($kelebihan / ($lotArea * 2 * $k));
        if ($c >= $cols) $c = $cols - 1;
    }
    $colsAktif = $cols - $c;
    $xFasum    = $rwMain + $colsAktif * $lw;
    $wFasum    = $P - $xFasum;
    $hBlok     = $k * $cell;

    $objs = [];
    $n = 0;
    $id = function () use (&$n) { return 'o' . (++$n); };
    $r2 = fn($v) => round($v, 2);

    // Jalan utama
    $objs[] = ['id' => $id(), 'type' => 'road', 'x' => 0, 'y' => 0, 'w' => $r2($rwMain), 'h' => $r2($L),
               'label' => 'Jalan Utama ' . $r2($rwMain) . ' m', 'tipe_id' => '', 'jenis' => ''];

    $huruf = range('A', 'Z');
    $row = 0;
    for ($i = 0; $i < $k; $i++) {
        $y0 = $i * $cell;
        // jalan lingkungan
        $objs[] = ['id' => $id(), 'type' => 'road', 'x' => $r2($rwMain), 'y' => $r2($y0 + $ld),
                   'w' => $r2($colsAktif * $lw), 'h' => $r2($rw),
                   'label' => 'Jalan ' . $r2($rw) . ' m', 'tipe_id' => '', 'jenis' => ''];
        foreach ([$y0, $y0 + $ld + $rw] as $y) {
            $blok = $huruf[$row % 26] . ($row >= 26 ? intdiv($row, 26) : '');
            for ($j = 0; $j < $colsAktif; $j++) {
                $objs[] = ['id' => $id(), 'type' => 'lot', 'x' => $r2($rwMain + $j * $lw), 'y' => $r2($y),
                           'w' => $r2($lw), 'h' => $r2($ld),
                           'label' => $blok . '-' . str_pad((string)($j + 1), 2, '0', STR_PAD_LEFT),
                           'tipe_id' => (string)($tipe['id'] ?? ''), 'jenis' => ''];
            }
            $row++;
        }
    }

    // Zona fasum di sisi kanan
    $ibadahDibuat = false;
    if ($wFasum >= 3 && $hBlok > 0) {
        // Mushola dibuat sebagai bangunan 8x8 m (harga ibadah dihitung per m² bangunan),
        // sisa zona dijadikan taman agar biaya fasum tidak membengkak.
        $sIb = 8.0;
        $hIbadah = 0.0;
        if ($wFasum >= $sIb && $hBlok >= $sIb + 3) {
            $hIbadah = $sIb;
            $objs[] = ['id' => $id(), 'type' => 'fasum', 'x' => $r2($xFasum), 'y' => 0, 'w' => $r2($sIb), 'h' => $r2($sIb),
                       'label' => 'Mushola', 'tipe_id' => '', 'jenis' => 'ibadah'];
            $ibadahDibuat = true;
            if ($wFasum - $sIb >= 3) {
                $objs[] = ['id' => $id(), 'type' => 'fasum', 'x' => $r2($xFasum + $sIb), 'y' => 0,
                           'w' => $r2($wFasum - $sIb), 'h' => $r2($sIb),
                           'label' => 'Taman', 'tipe_id' => '', 'jenis' => 'taman'];
            }
        }
        if ($hBlok - $hIbadah >= 3) {
            $objs[] = ['id' => $id(), 'type' => 'fasum', 'x' => $r2($xFasum), 'y' => $r2($hIbadah),
                       'w' => $r2($wFasum), 'h' => $r2($hBlok - $hIbadah),
                       'label' => 'Taman', 'tipe_id' => '', 'jenis' => 'taman'];
        }
    }
    // Sisa lahan di bawah blok
    $sisaY = $L - $hBlok;
    if ($sisaY >= 3) {
        $objs[] = ['id' => $id(), 'type' => 'fasum', 'x' => $r2($rwMain), 'y' => $r2($hBlok),
                   'w' => $r2($P - $rwMain), 'h' => $r2($sisaY),
                   'label' => 'Taman', 'tipe_id' => '', 'jenis' => 'taman'];
    }
    // Tidak ada ruang untuk tempat ibadah: ubah satu kavling pojok menjadi mushola
    if (!$ibadahDibuat) {
        foreach ($objs as &$ob) {
            if ($ob['type'] === 'lot') {
                $ob['type'] = 'fasum'; $ob['jenis'] = 'ibadah'; $ob['tipe_id'] = ''; $ob['label'] = 'Mushola';
                break;
            }
        }
        unset($ob);
    }

    return ['ok' => true, 'pesan' => '', 'objects' => $objs];
}

/** Normalisasi & validasi objek siteplan dari klien. */
function siteplan_sanitize(array $in, array $tipeList): array
{
    $tipeIds = array_column($tipeList, 'id');
    $out = [];
    $seen = [];
    foreach (array_slice($in, 0, 2000) as $o) {
        if (!is_array($o)) continue;
        $type = $o['type'] ?? '';
        if (!in_array($type, ['road', 'lot', 'fasum'], true)) continue;
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($o['id'] ?? ''));
        if ($id === '' || isset($seen[$id])) $id = 'o' . bin2hex(random_bytes(3));
        $seen[$id] = true;

        $tipe = (string)($o['tipe_id'] ?? '');
        if ($type !== 'lot' || !in_array($tipe, $tipeIds, true)) $tipe = '';
        $jenis = (string)($o['jenis'] ?? '');
        if ($type !== 'fasum' || !isset(FASUM_JENIS[$jenis])) $jenis = $type === 'fasum' ? 'lainnya' : '';

        $out[] = [
            'id' => $id, 'type' => $type,
            'x' => round(num($o['x'] ?? 0, 0, -2000, 20000), 2),
            'y' => round(num($o['y'] ?? 0, 0, -2000, 20000), 2),
            'w' => round(num($o['w'] ?? 1, 1, 0.5, 20000), 2),
            'h' => round(num($o['h'] ?? 1, 1, 0.5, 20000), 2),
            'label' => str_in($o['label'] ?? '', 40),
            'tipe_id' => $tipe, 'jenis' => $jenis,
        ];
    }
    return $out;
}

/**
 * Statistik site plan untuk RAB & ringkasan.
 */
function siteplan_stats(array $objs, array $lahan, array $tipeList, array $params): array
{
    $P = (float)$lahan['panjang'];
    $L = (float)$lahan['lebar'];
    $luasLahan = $P * $L;

    $roads = $lots = $fasum = [];
    foreach ($objs as $o) {
        if ($o['type'] === 'road') $roads[] = $o;
        elseif ($o['type'] === 'lot') $lots[] = $o;
        elseif ($o['type'] === 'fasum') $fasum[] = $o;
    }

    // Jalan
    $luasJalan = union_area($roads);
    $sumLen = $sumArea = 0.0;
    foreach ($roads as $r) {
        $sumLen += max($r['w'], $r['h']);
        $sumArea += $r['w'] * $r['h'];
    }
    $lebarRata = $sumLen > 0 ? $sumArea / $sumLen : 0.0;
    $panjangJalan = $lebarRata > 0 ? $luasJalan / $lebarRata : 0.0;

    // Kavling per tipe
    $tipeMap = [];
    foreach ($tipeList as $t) $tipeMap[$t['id']] = $t;
    $perTipe = [];
    $luasKavling = 0.0;
    $tanpaTipe = 0;
    $sumSetengahKeliling = 0.0;
    foreach ($lots as $l) {
        $a = $l['w'] * $l['h'];
        $luasKavling += $a;
        $sumSetengahKeliling += $l['w'] + $l['h'];
        $tid = $l['tipe_id'];
        if ($tid === '' || !isset($tipeMap[$tid])) { $tanpaTipe++; continue; }
        $perTipe[$tid]['jumlah'] = ($perTipe[$tid]['jumlah'] ?? 0) + 1;
        $perTipe[$tid]['luas'] = ($perTipe[$tid]['luas'] ?? 0) + $a;
    }

    // Fasum per jenis
    $fasumJenis = [];
    $luasFasum = 0.0;
    foreach ($fasum as $f) {
        $a = $f['w'] * $f['h'];
        $j = isset(FASUM_JENIS[$f['jenis']]) ? $f['jenis'] : 'lainnya';
        $fasumJenis[$j] = ($fasumJenis[$j] ?? 0) + $a;
        $luasFasum += $a;
    }

    // Peringatan
    $warn = [];
    $maxR = (float)$params['rasio_kavling_max'];
    $rasioKav = $luasLahan > 0 ? $luasKavling / $luasLahan * 100 : 0;
    if ($rasioKav > $maxR) {
        $warn[] = 'Rasio kavling ' . angka($rasioKav, 1) . '% melebihi batas ' . angka($maxR, 0) . '% (atur di parameter proyek).';
    }
    if ($tanpaTipe > 0) $warn[] = $tanpaTipe . ' kavling belum diberi tipe rumah — tidak ikut dihitung di RAB.';

    $tumpang = 0;
    $bangunan = array_merge($lots, $roads, $fasum);
    for ($i = 0, $n = count($bangunan); $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            if ($bangunan[$i]['type'] === 'road' && $bangunan[$j]['type'] === 'road') continue; // persimpangan wajar
            if (overlap_area($bangunan[$i], $bangunan[$j]) > 0.05) $tumpang++;
        }
    }
    if ($tumpang > 0) $warn[] = $tumpang . ' pasang objek saling tumpang tindih di site plan.';

    $diLuar = 0;
    foreach ($objs as $o) {
        if ($o['x'] < -0.01 || $o['y'] < -0.01 || $o['x'] + $o['w'] > $P + 0.01 || $o['y'] + $o['h'] > $L + 0.01) $diLuar++;
    }
    if ($diLuar > 0) $warn[] = $diLuar . ' objek berada di luar batas lahan.';

    foreach ($perTipe as $tid => $v) {
        $t = $tipeMap[$tid];
        $rata = $v['luas'] / $v['jumlah'];
        if ($rata + 0.01 < (float)$t['luas_tanah']) {
            $warn[] = $t['nama'] . ': luas kavling rata-rata ' . angka($rata, 1) . ' m² lebih kecil dari luas tanah tipe (' . angka((float)$t['luas_tanah'], 0) . ' m²).';
        }
    }

    return [
        'luas_lahan' => $luasLahan,
        'keliling_lahan' => 2 * ($P + $L),
        'luas_jalan' => $luasJalan,
        'panjang_jalan' => $panjangJalan,
        'luas_kavling' => $luasKavling,
        'jumlah_kavling' => count($lots),
        'kavling_tanpa_tipe' => $tanpaTipe,
        'per_tipe' => $perTipe,
        'jumlah_rumah' => array_sum(array_column($perTipe, 'jumlah')),
        'setengah_keliling_kavling' => $sumSetengahKeliling,
        'fasum_jenis' => $fasumJenis,
        'luas_fasum' => $luasFasum,
        'rasio_kavling' => $rasioKav,
        'rasio_jalan' => $luasLahan > 0 ? $luasJalan / $luasLahan * 100 : 0,
        'rasio_fasum' => $luasLahan > 0 ? $luasFasum / $luasLahan * 100 : 0,
        'peringatan' => $warn,
    ];
}
