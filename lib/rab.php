<?php
declare(strict_types=1);

/**
 * MESIN HITUNG RAB
 *
 * Alur: site plan -> statistik (jumlah kavling per tipe, luas jalan, fasum)
 *       tipe rumah -> volume pekerjaan (parametrik) x harga satuan (AHSP)
 *       -> rekap per bagian -> overhead, kontingensi, PPN -> total.
 *
 * Volume rumah dihitung dengan model geometri sederhana (denah persegi
 * panjang rasio 1:1,5). Hasilnya cocok untuk estimasi awal / studi kelayakan,
 * BUKAN pengganti gambar kerja & BQ detail.
 */

/* ---------- Pembantu baris ---------- */

/** Tambahkan satu baris pekerjaan ber-AHSP dan akumulasikan material/komposisi. */
function rab_baris_ahsp(array $A, string $kode, string $uraian, float $vol, array &$agg): array
{
    $a = $A[$kode];
    foreach ($a['komponen'] as $k) {
        $q = $k['koef'] * $vol;
        $agg['qty'][$k['kode']] = ($agg['qty'][$k['kode']] ?? 0.0) + $q;
    }
    $agg['komposisi']['upah']  += $a['upah']  * $vol;
    $agg['komposisi']['bahan'] += $a['bahan'] * $vol;
    $agg['komposisi']['alat']  += $a['alat']  * $vol;
    return ['uraian' => $uraian, 'vol' => $vol, 'sat' => $a['satuan'], 'harga' => $a['harga'],
            'jumlah' => $a['harga'] * $vol, 'ref' => $kode];
}

/** Baris borongan (harga langsung per satuan, tanpa analisa). */
function rab_baris_borongan(array $harga, string $kode, string $uraian, float $vol, array &$agg): array
{
    $d = harga_default()[$kode];
    $hg = (float)($harga[$kode] ?? $d[2]);
    $agg['komposisi']['borongan'] += $hg * $vol;
    return ['uraian' => $uraian, 'vol' => $vol, 'sat' => $d[1], 'harga' => $hg, 'jumlah' => $hg * $vol, 'ref' => $kode];
}

function rab_bagian(string $kode, string $judul, array $baris): array
{
    $baris = array_values(array_filter($baris, fn($b) => $b['vol'] > 1e-9));
    return ['kode' => $kode, 'judul' => $judul, 'baris' => $baris,
            'subtotal' => array_sum(array_column($baris, 'jumlah'))];
}

function agg_baru(): array
{
    return ['qty' => [], 'komposisi' => ['upah' => 0.0, 'bahan' => 0.0, 'alat' => 0.0, 'borongan' => 0.0]];
}

/* ---------- Rumah ---------- */

/**
 * Volume pekerjaan satu unit rumah.
 * @return array ['dimensi'=>[...], 'grup'=>[[kode,judul,[[ahsp,uraian,vol],...]],...]]
 */
function rumah_volume(array $t, array $P): array
{
    $lantai = max(1, (int)$t['lantai']);
    $LB = max(1.0, (float)$t['luas_bangunan']);
    $kt = max(0, (int)$t['kamar_tidur']);
    $km = max(0, (int)$t['kamar_mandi']);
    $H  = max(2.4, (float)$P['tinggi_dinding']);
    $spec = $t['spec'] ?? [];
    $opsi = spec_opsi();
    $pilih = fn(string $g) => $opsi[$g][$spec[$g] ?? array_key_first($opsi[$g])] ?? reset($opsi[$g]);

    // --- Geometri denah ---
    $A  = $LB / $lantai;                  // luas denah per lantai
    $w  = sqrt($A / 1.5);                 // lebar
    $l  = 1.5 * $w;                       // panjang
    $K  = 2 * ($w + $l);                  // keliling luar
    $fSekat = 0.15 + 0.10 * ($kt + $km);  // panjang dinding sekat relatif terhadap keliling
    $Lp = $K * (1 + $fSekat);             // panjang total as dinding/pondasi

    // --- Bukaan ---
    $nPintuUtama = 1;
    $nJendela = $kt + 2 * $lantai;
    $bukaan = $nPintuUtama * 0.9 * 2.1 + $kt * 0.9 * 2.1 + $km * 0.8 * 2.1 + $nJendela * 0.9 * 1.2;
    $bukaanLuar = $nPintuUtama * 0.9 * 2.1 + $nJendela * 0.9 * 1.2;

    // --- Volume turunan ---
    $galian = $Lp * 0.60 * 0.70;
    $urugPasirPondasi = $Lp * 0.60 * 0.05;
    $pondasi = $Lp * 0.27;                // penampang trapesium rata-rata 0,45 x 0,60
    $urugKembali = max(0.0, $galian - $pondasi - $urugPasirPondasi);

    $nKolom = (int)ceil($Lp / 3.0);
    $dimKolom = $lantai > 1 ? 0.15 : 0.12;
    $sloof = $Lp * 0.15 * 0.20;
    $kolom = $nKolom * $dimKolom * $dimKolom * $H * $lantai;
    $ring  = $Lp * $lantai * 0.12 * 0.15;
    $plat  = ($lantai - 1) * $A * 0.12;

    $dindingBruto = $Lp * $H * $lantai;
    $dindingNet = max(0.0, $dindingBruto - $bukaan);
    $plester = $dindingNet * 2;
    $luarArea = max(0.0, $K * $H * $lantai - $bukaanLuar);
    $keramikKmDinding = $km * 11.0;
    $dalamArea = max(0.0, $plester - $luarArea - $keramikKmDinding);

    $atapW = $w + 1.0;                    // overstek 0,5 m tiap sisi
    $atapL = $l + 1.0;
    $luasAtap = $atapW * $atapL / cos(deg2rad(30));
    $plafon = $A * $lantai;
    $lantaiUmum = max(0.0, $A * $lantai - $km * 3.0);

    [$labelAtap, $kodeAtap] = $pilih('atap');
    [$labelLantai, $kodeLantai] = $pilih('lantai');
    [$labelPlafon, $kodePlafon] = $pilih('plafon');
    [$labelSan, $kodeSan] = $pilih('sanitair');
    [$labelDinding, $kodeDinding] = $pilih('dinding');

    $grup = [
        ['I', 'Pekerjaan Persiapan', [
            ['A02', 'Pengukuran & bowplank', $K + 8],
        ]],
        ['II', 'Pekerjaan Tanah & Pondasi', [
            ['B01', 'Galian tanah pondasi', $galian],
            ['B02', 'Urugan pasir bawah pondasi t=5 cm', $urugPasirPondasi],
            ['B03', 'Pondasi batu belah 1:5', $pondasi],
            ['B04', 'Urugan kembali galian', $urugKembali],
            ['B05', 'Urugan tanah peninggian lantai', $A * (float)$P['peninggian_lantai']],
            ['B02', 'Urugan pasir bawah lantai t=5 cm', $A * 0.05],
        ]],
        ['III', 'Pekerjaan Struktur Beton', [
            ['C01', 'Sloof 15/20', $sloof],
            ['C01', 'Kolom praktis ' . ($dimKolom * 100) . '/' . ($dimKolom * 100), $kolom],
            ['C01', 'Ring balok 12/15', $ring],
            ['C01', 'Plat lantai t=12 cm (lantai atas)', $plat],
            ['C02', 'Lantai kerja', $A],
        ]],
        ['IV', 'Pekerjaan Dinding & Plesteran', [
            [$kodeDinding, 'Pasangan dinding ' . strtolower($labelDinding), $dindingNet],
            ['D03', 'Plesteran 2 sisi', $plester],
            ['D04', 'Acian 2 sisi', $plester],
        ]],
        ['V', 'Pekerjaan Kusen, Pintu & Jendela', [
            ['E01', 'Pintu utama', $nPintuUtama],
            ['E02', 'Pintu kamar tidur', $kt],
            ['E03', 'Pintu kamar mandi', $km],
            ['E04', 'Jendela', $nJendela],
        ]],
        ['VI', 'Pekerjaan Atap', [
            ['F01', 'Rangka atap baja ringan (kemiringan 30°)', $luasAtap],
            [$kodeAtap, 'Penutup atap — ' . strtolower($labelAtap), $luasAtap],
            ['F05', 'Bubungan / nok', $atapL],
            ['F06', 'Lisplank', 2 * ($atapW + $atapL)],
        ]],
        ['VII', 'Pekerjaan Plafon', [
            [$kodePlafon, 'Plafon — ' . strtolower($labelPlafon), $plafon],
        ]],
        ['VIII', 'Pekerjaan Lantai & Finishing', [
            [$kodeLantai, 'Lantai — ' . strtolower($labelLantai), $lantaiUmum],
            ['H04', 'Lantai kamar mandi', $km * 3.0],
            ['H05', 'Dinding keramik kamar mandi', $keramikKmDinding],
            ['I01', 'Cat dinding interior', $dalamArea],
            ['I02', 'Cat dinding eksterior', $luarArea],
            ['I03', 'Cat plafon', $plafon],
        ]],
        ['IX', 'Pekerjaan Sanitair & Plumbing', [
            [$kodeSan, $labelSan, $km],
            ['J03', 'Kran, floor drain & shower', $km],
            ['J04', 'Kitchen sink', 1],
            ['L01', 'Titik air bersih', $km * 3 + 3],
            ['L02', 'Pipa air kotor PVC 3"', 6 + 4 * $km],
            ['L03', 'Septictank + resapan', 1],
        ]],
        ['X', 'Pekerjaan Instalasi Listrik', [
            ['K01', 'Titik lampu + saklar', ceil($LB / 5)],
            ['K02', 'Titik stop kontak', ceil($LB / 6)],
            ['K03', 'MCB + box panel', 1],
        ]],
    ];

    return [
        'dimensi' => [
            'denah' => [$w, $l], 'keliling' => $K, 'panjang_dinding' => $Lp,
            'luas_atap' => $luasAtap, 'dinding_netto' => $dindingNet, 'beton_m3' => $sloof + $kolom + $ring + $plat,
        ],
        'grup' => $grup,
    ];
}

/** Hitung RAB satu unit rumah. */
function rab_rumah(array $t, array $P, array $A, array &$agg): array
{
    $v = rumah_volume($t, $P);
    $grup = [];
    $total = 0.0;
    foreach ($v['grup'] as [$kode, $judul, $items]) {
        $baris = [];
        foreach ($items as [$ahsp, $uraian, $vol]) {
            $baris[] = rab_baris_ahsp($A, $ahsp, $uraian, (float)$vol, $agg);
        }
        $g = rab_bagian($kode, $judul, $baris);
        $total += $g['subtotal'];
        $grup[] = $g;
    }
    return ['grup' => $grup, 'total' => $total, 'dimensi' => $v['dimensi']];
}

/* ---------- Proyek ---------- */

function rab_hitung(array $p): array
{
    $P = $p['params'];
    $harga = $p['harga'];
    $A = ahsp_hitung($harga);
    $objs = $p['siteplan']['objects'] ?? [];
    $S = siteplan_stats($objs, $p['lahan'], $p['tipe'], $P);

    $agg = agg_baru();
    $tipeMap = [];
    foreach ($p['tipe'] as $t) $tipeMap[$t['id']] = $t;
    $jmlRumah = (int)$S['jumlah_rumah'];

    /* A. Persiapan & pematangan lahan */
    $baA = [
        rab_baris_borongan($harga, 'F12', 'Direksi keet, papan proyek & K3', 1, $agg),
        rab_baris_ahsp($A, 'A01', 'Pembersihan & perataan lahan', $S['luas_lahan'], $agg),
        rab_baris_ahsp($A, 'A02', 'Pengukuran & pematokan kavling', $S['setengah_keliling_kavling'], $agg),
        rab_baris_ahsp($A, 'P01', 'Pematangan lahan (urugan & pemadatan)', $S['luas_lahan'] * (float)$P['tinggi_urugan'], $agg),
    ];
    if ((int)$P['pagar_keliling'] === 1) {
        $baA[] = rab_baris_borongan($harga, 'F11', 'Pagar keliling kawasan', $S['keliling_lahan'], $agg);
    }
    $baA[] = rab_baris_borongan($harga, 'F10', 'Gerbang kawasan', (float)$P['jumlah_gerbang'], $agg);
    $bagA = rab_bagian('A', 'Persiapan & Pematangan Lahan', $baA);

    /* B. Infrastruktur */
    $kodeJalan = $P['jenis_jalan'] === 'beton' ? 'P04' : 'P03';
    $namaJalan = $P['jenis_jalan'] === 'beton' ? 'Perkerasan jalan beton K-225 t=15 cm' : 'Perkerasan jalan paving block t=6 cm';
    $pj = $S['panjang_jalan'];
    $baB = [
        rab_baris_ahsp($A, 'P02', 'Lapis pondasi sirtu', $S['luas_jalan'] * (float)$P['tebal_sirtu'], $agg),
        rab_baris_ahsp($A, $kodeJalan, $namaJalan, $S['luas_jalan'], $agg),
        rab_baris_ahsp($A, 'P05', 'Saluran drainase U-ditch (' . (int)$P['sisi_drainase'] . ' sisi jalan)', $pj * (int)$P['sisi_drainase'], $agg),
        rab_baris_ahsp($A, 'P06', 'Jaringan listrik tegangan rendah', $pj, $agg),
        rab_baris_ahsp($A, 'P07', 'Penerangan jalan umum (PJU)', $pj > 0 ? ceil($pj / max(5.0, (float)$P['jarak_pju'])) : 0, $agg),
        rab_baris_ahsp($A, 'P09', 'Sumur bor + menara + tandon', $jmlRumah > 0 ? ceil($jmlRumah / max(1.0, (float)$P['rumah_per_sumur'])) : 0, $agg),
        rab_baris_ahsp($A, 'P08', 'Pipa distribusi air bersih', $pj, $agg),
        rab_baris_ahsp($A, 'P10', 'Sambungan listrik per rumah', $jmlRumah, $agg),
        rab_baris_ahsp($A, 'P11', 'Sambungan air bersih per rumah', $jmlRumah, $agg),
    ];
    $bagB = rab_bagian('B', 'Infrastruktur Kawasan', $baB);

    /* C. Fasilitas umum */
    $baC = [];
    foreach (FASUM_JENIS as $key => [$label, $kodeHarga]) {
        $luas = (float)($S['fasum_jenis'][$key] ?? 0);
        if ($luas > 0) $baC[] = rab_baris_borongan($harga, $kodeHarga, $label, $luas, $agg);
    }
    $bagC = rab_bagian('C', 'Fasilitas Umum & Sosial', $baC);

    /* D. Bangunan rumah */
    $baD = [];
    $rumahDetail = [];
    foreach ($p['tipe'] as $t) {
        $n = (int)($S['per_tipe'][$t['id']]['jumlah'] ?? 0);
        $aggTipe = agg_baru();
        $r = rab_rumah($t, $P, $A, $aggTipe);
        $rumahDetail[$t['id']] = $r + ['jumlah_unit' => $n];
        if ($n < 1) continue;
        // akumulasi ke total material proyek (dikali jumlah unit)
        foreach ($aggTipe['qty'] as $k => $q) $agg['qty'][$k] = ($agg['qty'][$k] ?? 0.0) + $q * $n;
        foreach (['upah', 'bahan', 'alat'] as $c) $agg['komposisi'][$c] += $aggTipe['komposisi'][$c] * $n;
        $baD[] = ['uraian' => $t['nama'] . ' (LB ' . angka((float)$t['luas_bangunan'], 0) . ' m² / LT ' . angka((float)$t['luas_tanah'], 0) . ' m²)',
                  'vol' => (float)$n, 'sat' => 'unit', 'harga' => $r['total'], 'jumlah' => $r['total'] * $n,
                  'ref' => 'tipe:' . $t['id']];
    }
    $bagD = rab_bagian('D', 'Bangunan Rumah', $baD);

    /* Rekap */
    $bagian = [$bagA, $bagB, $bagC, $bagD];
    $subtotal = array_sum(array_column($bagian, 'subtotal'));
    $overhead = $subtotal * (float)$P['overhead_persen'] / 100;
    $kontingensi = $subtotal * (float)$P['kontingensi_persen'] / 100;
    $dpp = $subtotal + $overhead + $kontingensi;
    $ppn = $dpp * (float)$P['ppn_persen'] / 100;
    $total = $dpp + $ppn;

    /* Rekap kebutuhan material/upah/alat */
    $def = harga_default();
    $material = [];
    foreach ($agg['qty'] as $k => $q) {
        $hg = (float)($harga[$k] ?? $def[$k][2]);
        $material[$k] = ['kode' => $k, 'nama' => $def[$k][0], 'sat' => $def[$k][1], 'kat' => $def[$k][3],
                         'qty' => $q, 'harga' => $hg, 'jumlah' => $q * $hg];
    }
    // urutkan: bahan, alat, upah; dalam kategori dari yang termahal
    $urutKat = ['bahan' => 0, 'alat' => 1, 'upah' => 2];
    uasort($material, fn($a, $b) => [$urutKat[$a['kat']] ?? 9, -$a['jumlah']] <=> [$urutKat[$b['kat']] ?? 9, -$b['jumlah']]);

    /* Kelayakan sederhana */
    $pendapatan = 0.0;
    foreach ($p['tipe'] as $t) {
        $pendapatan += (float)($S['per_tipe'][$t['id']]['jumlah'] ?? 0) * (float)($t['harga_jual'] ?? 0);
    }
    $biayaLahan = $S['luas_lahan'] * (float)$P['harga_tanah_m2'];
    $perizinan = ($total + $biayaLahan) * (float)$P['perizinan_persen'] / 100;
    $pemasaran = $pendapatan * (float)$P['pemasaran_persen'] / 100;
    $totalBiaya = $total + $biayaLahan + $perizinan + $pemasaran;
    $laba = $pendapatan - $totalBiaya;

    $luasBangunanTotal = 0.0;
    foreach ($S['per_tipe'] as $tid => $v) {
        $luasBangunanTotal += $v['jumlah'] * (float)$tipeMap[$tid]['luas_bangunan'];
    }

    return [
        'stat' => $S,
        'bagian' => $bagian,
        'subtotal' => $subtotal, 'overhead' => $overhead, 'kontingensi' => $kontingensi,
        'dpp' => $dpp, 'ppn' => $ppn, 'total' => $total,
        'total_per_rumah' => $jmlRumah > 0 ? $total / $jmlRumah : 0.0,
        'per_m2_bangunan' => $luasBangunanTotal > 0 ? $total / $luasBangunanTotal : 0.0,
        'komposisi' => $agg['komposisi'],
        'material' => $material,
        'rumah' => $rumahDetail,
        'ahsp' => $A,
        'kelayakan' => [
            'pendapatan' => $pendapatan, 'biaya_konstruksi' => $total, 'biaya_lahan' => $biayaLahan,
            'perizinan' => $perizinan, 'pemasaran' => $pemasaran, 'total_biaya' => $totalBiaya,
            'laba' => $laba, 'margin' => $pendapatan > 0 ? $laba / $pendapatan * 100 : 0.0,
            'hpp_per_unit' => $jmlRumah > 0 ? $totalBiaya / $jmlRumah : 0.0,
        ],
    ];
}

/** Ekspor CSV (pemisah ; agar langsung terbaca di Excel lokal Indonesia). */
function rab_csv(array $p, array $R): string
{
    $f = fopen('php://temp', 'w+');
    $w = fn(array $row) => fputcsv($f, $row, ';', '"', '\\');
    $w(['RENCANA ANGGARAN BIAYA — ' . $p['nama']]);
    $w(['Lokasi', $p['lokasi']]);
    $w(['Dicetak', date('d/m/Y H:i')]);
    $w([]);
    $w(['No', 'Uraian Pekerjaan', 'Volume', 'Satuan', 'Harga Satuan (Rp)', 'Jumlah (Rp)']);
    foreach ($R['bagian'] as $b) {
        $w([$b['kode'], strtoupper($b['judul']), '', '', '', round($b['subtotal'])]);
        foreach ($b['baris'] as $i => $r) {
            $w([$b['kode'] . '.' . ($i + 1), $r['uraian'], round($r['vol'], 2), $r['sat'], round($r['harga']), round($r['jumlah'])]);
        }
    }
    $w([]);
    $w(['', 'Subtotal', '', '', '', round($R['subtotal'])]);
    $w(['', 'Overhead & profit ' . $p['params']['overhead_persen'] . '%', '', '', '', round($R['overhead'])]);
    $w(['', 'Kontingensi ' . $p['params']['kontingensi_persen'] . '%', '', '', '', round($R['kontingensi'])]);
    $w(['', 'PPN ' . $p['params']['ppn_persen'] . '%', '', '', '', round($R['ppn'])]);
    $w(['', 'TOTAL', '', '', '', round($R['total'])]);
    $w([]);
    $w(['REKAP MATERIAL, UPAH & ALAT (sebelum overhead/pajak)']);
    $w(['Kode', 'Uraian', 'Kebutuhan', 'Satuan', 'Harga (Rp)', 'Jumlah (Rp)', 'Kategori']);
    foreach ($R['material'] as $m) {
        $w([$m['kode'], $m['nama'], round($m['qty'], 2), $m['sat'], round($m['harga']), round($m['jumlah']), $m['kat']]);
    }
    rewind($f);
    $csv = stream_get_contents($f);
    fclose($f);
    return "\xEF\xBB\xBF" . $csv;
}
