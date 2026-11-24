<?php
declare(strict_types=1);

/**
 * DATA MASTER HARGA DASAR & ANALISA HARGA SATUAN PEKERJAAN (AHSP)
 *
 * PENTING:
 *  - Harga di sini adalah CONTOH/ASUMSI awal. Sesuaikan dengan harga pasar
 *    atau HSPK daerah proyek Anda (menu "Harga Satuan").
 *  - Koefisien AHSP mengikuti pola analisa SNI/Permen PUPR tetapi merupakan
 *    PENDEKATAN, bukan salinan resmi. Verifikasi dengan AHSP resmi yang
 *    berlaku sebelum dipakai untuk tender/kontrak.
 */

/** kode => [nama, satuan, harga, kategori] ; kategori: upah|bahan|alat|borongan */
function harga_default(): array
{
    return [
        // ---- UPAH (per orang-hari) ----
        'U01' => ['Pekerja', 'OH', 120000, 'upah'],
        'U02' => ['Tukang batu', 'OH', 165000, 'upah'],
        'U03' => ['Tukang kayu', 'OH', 165000, 'upah'],
        'U04' => ['Tukang besi', 'OH', 165000, 'upah'],
        'U05' => ['Tukang cat', 'OH', 160000, 'upah'],
        'U06' => ['Tukang listrik', 'OH', 175000, 'upah'],
        'U07' => ['Tukang pipa', 'OH', 170000, 'upah'],
        'U08' => ['Kepala tukang', 'OH', 180000, 'upah'],
        'U09' => ['Mandor', 'OH', 190000, 'upah'],

        // ---- ALAT (per jam) ----
        'E01' => ['Excavator + operator', 'jam', 650000, 'alat'],
        'E02' => ['Vibro roller + operator', 'jam', 480000, 'alat'],

        // ---- BAHAN ----
        'M01' => ['Semen portland', 'kg', 1450, 'bahan'],
        'M02' => ['Pasir pasang / beton', 'm³', 280000, 'bahan'],
        'M03' => ['Pasir urug', 'm³', 210000, 'bahan'],
        'M04' => ['Batu belah / batu kali', 'm³', 310000, 'bahan'],
        'M05' => ['Split / kerikil 2-3 cm', 'm³', 390000, 'bahan'],
        'M06' => ['Tanah urug (tiba di lokasi)', 'm³', 120000, 'bahan'],
        'M07' => ['Sirtu / agregat kelas B', 'm³', 240000, 'bahan'],
        'M08' => ['Besi beton', 'kg', 15500, 'bahan'],
        'M09' => ['Kawat beton (bendrat)', 'kg', 26000, 'bahan'],
        'M10' => ['Kayu kelas III (bekisting/bowplank)', 'm³', 3800000, 'bahan'],
        'M11' => ['Paku', 'kg', 24000, 'bahan'],
        'M12' => ['Bata ringan (AAC)', 'm³', 900000, 'bahan'],
        'M13' => ['Bata merah press', 'bh', 950, 'bahan'],
        'M14' => ['Mortar perekat bata ringan', 'kg', 2800, 'bahan'],
        'M15' => ['Rangka baja ringan + reng (material)', 'm²', 165000, 'bahan'],
        'M16' => ['Genteng metal pasir', 'm²', 78000, 'bahan'],
        'M17' => ['Genteng beton', 'm²', 92000, 'bahan'],
        'M18' => ['Spandek zincalume 0,30 mm', 'm²', 72000, 'bahan'],
        'M19' => ['Nok / bubungan metal', "m'", 48000, 'bahan'],
        'M20' => ['Lisplank GRC 20 cm + rangka', "m'", 62000, 'bahan'],
        'M21' => ['Plafon gypsum 9 mm + rangka hollow', 'm²', 98000, 'bahan'],
        'M22' => ['Plafon PVC + rangka', 'm²', 82000, 'bahan'],
        'M23' => ['Keramik lantai 40x40', 'm²', 75000, 'bahan'],
        'M24' => ['Keramik lantai 60x60', 'm²', 135000, 'bahan'],
        'M25' => ['Granit tile 60x60', 'm²', 215000, 'bahan'],
        'M26' => ['Keramik lantai KM 30x30', 'm²', 68000, 'bahan'],
        'M27' => ['Keramik dinding KM 25x40', 'm²', 76000, 'bahan'],
        'M28' => ['Plamir tembok', 'kg', 22000, 'bahan'],
        'M29' => ['Cat dasar / primer', 'kg', 38000, 'bahan'],
        'M30' => ['Cat tembok interior', 'kg', 42000, 'bahan'],
        'M31' => ['Cat tembok eksterior', 'kg', 64000, 'bahan'],
        'M32' => ['Pintu utama panel + kusen', 'unit', 1850000, 'bahan'],
        'M33' => ['Pintu kamar multipleks + kusen', 'unit', 1150000, 'bahan'],
        'M34' => ['Pintu KM PVC + kusen', 'unit', 480000, 'bahan'],
        'M35' => ['Jendela aluminium + kaca 5 mm', 'unit', 980000, 'bahan'],
        'M36' => ['Closet jongkok', 'unit', 350000, 'bahan'],
        'M37' => ['Closet duduk', 'unit', 900000, 'bahan'],
        'M38' => ['Kran + floor drain + shower set', 'set', 320000, 'bahan'],
        'M39' => ['Kitchen sink stainless', 'unit', 700000, 'bahan'],
        'M40' => ['Pipa PVC AW 1/2"', "m'", 12500, 'bahan'],
        'M41' => ['Pipa PVC 3" air kotor', "m'", 40000, 'bahan'],
        'M42' => ['Fitting & aksesoris pipa', 'set', 25000, 'bahan'],
        'M43' => ['Kabel NYM 2x1,5 mm', "m'", 7500, 'bahan'],
        'M44' => ['Kabel NYM 3x2,5 mm', "m'", 14000, 'bahan'],
        'M45' => ['Pipa conduit', "m'", 6500, 'bahan'],
        'M46' => ['Lampu LED 9W + fitting', 'bh', 42000, 'bahan'],
        'M47' => ['Saklar', 'bh', 28000, 'bahan'],
        'M48' => ['Stop kontak', 'bh', 32000, 'bahan'],
        'M49' => ['MCB 1 phase', 'bh', 62000, 'bahan'],
        'M50' => ['Box MCB', 'bh', 125000, 'bahan'],
        'M51' => ['Septictank + resapan (precast/biotech)', 'unit', 3200000, 'bahan'],
        'M52' => ['Paving block K-300 t=6 cm', 'm²', 88000, 'bahan'],
        'M53' => ['Wiremesh M8', 'm²', 46000, 'bahan'],
        'M54' => ['U-ditch precast 30x40', "m'", 190000, 'bahan'],
        'M55' => ['Penutup U-ditch precast', "m'", 60000, 'bahan'],
        'M56' => ['Pipa PVC 2" distribusi air', "m'", 58000, 'bahan'],
        'M57' => ['Jaringan listrik TR kawasan (kabel + tiang)', "m'", 260000, 'bahan'],
        'M58' => ['Tiang PJU + armatur LED 40W', 'unit', 3600000, 'bahan'],
        'M59' => ['Sumur bor + menara + tandon + pompa', 'paket', 85000000, 'bahan'],
        'M60' => ['Biaya sambung listrik per rumah (1300 VA)', 'unit', 2200000, 'bahan'],
        'M61' => ['Sambungan air bersih per rumah', 'unit', 1400000, 'bahan'],

        // ---- BORONGAN (fasum per m² / lump sum) ----
        'F01' => ['Taman & RTH', 'm²', 350000, 'borongan'],
        'F02' => ['Tempat ibadah (mushola/masjid)', 'm²', 4500000, 'borongan'],
        'F03' => ['Pos jaga / kantor pengelola', 'm²', 3800000, 'borongan'],
        'F04' => ['Lapangan olahraga / bermain', 'm²', 450000, 'borongan'],
        'F05' => ['Area parkir paving', 'm²', 260000, 'borongan'],
        'F06' => ['Fasum lainnya', 'm²', 600000, 'borongan'],
        'F10' => ['Gerbang kawasan', 'unit', 45000000, 'borongan'],
        'F11' => ['Pagar keliling kawasan', "m'", 420000, 'borongan'],
        'F12' => ['Direksi keet, papan proyek & K3', 'ls', 30000000, 'borongan'],
    ];
}

/** Judul kelompok AHSP berdasarkan huruf awal kode */
function ahsp_grup(): array
{
    return [
        'A' => 'Persiapan',
        'B' => 'Tanah & Pondasi',
        'C' => 'Struktur Beton',
        'D' => 'Dinding & Plesteran',
        'E' => 'Kusen, Pintu & Jendela',
        'F' => 'Atap',
        'G' => 'Plafon',
        'H' => 'Lantai & Keramik',
        'I' => 'Pengecatan',
        'J' => 'Sanitair',
        'K' => 'Instalasi Listrik Rumah',
        'L' => 'Instalasi Air & Sanitasi Rumah',
        'P' => 'Infrastruktur Kawasan',
    ];
}

/** kode => [nama, satuan, [[kode_harga, koefisien], ...]] */
function ahsp_master(): array
{
    return [
        // A. Persiapan
        'A01' => ['Pembersihan & perataan lahan', 'm²', [['U01', 0.10], ['U09', 0.005]]],
        'A02' => ['Pengukuran & pemasangan bowplank', "m'", [['M10', 0.012], ['M11', 0.02], ['U01', 0.10], ['U03', 0.10], ['U08', 0.01], ['U09', 0.005]]],

        // B. Tanah & pondasi
        'B01' => ['Galian tanah pondasi', 'm³', [['U01', 0.75], ['U09', 0.025]]],
        'B02' => ['Urugan pasir', 'm³', [['M03', 1.20], ['U01', 0.30], ['U09', 0.01]]],
        'B03' => ['Pasangan pondasi batu belah 1:5', 'm³', [['M04', 1.20], ['M01', 136], ['M02', 0.544], ['U01', 1.50], ['U02', 0.75], ['U08', 0.075], ['U09', 0.075]]],
        'B04' => ['Urugan kembali galian', 'm³', [['U01', 0.25], ['U09', 0.0125]]],
        'B05' => ['Urugan tanah peninggian lantai', 'm³', [['M06', 1.20], ['U01', 0.40], ['U09', 0.02]]],

        // C. Struktur beton
        'C01' => ['Beton bertulang K-225 (sloof, kolom, balok, plat) termasuk besi & bekisting', 'm³', [
            ['M01', 371], ['M02', 0.50], ['M05', 0.78], ['M08', 150], ['M09', 2.25], ['M10', 0.30], ['M11', 3.0],
            ['U01', 6.86], ['U02', 0.275], ['U03', 2.08], ['U04', 1.05], ['U08', 0.343], ['U09', 0.353],
        ]],
        'C02' => ['Lantai kerja beton 1:3:5 t=5 cm', 'm²', [['M01', 12], ['M02', 0.036], ['M05', 0.06], ['U01', 0.20], ['U02', 0.10], ['U08', 0.01], ['U09', 0.01]]],

        // D. Dinding
        'D01' => ['Pasangan dinding bata ringan t=10 cm', 'm²', [['M12', 0.105], ['M14', 4.0], ['U01', 0.10], ['U02', 0.10], ['U08', 0.01], ['U09', 0.005]]],
        'D02' => ['Pasangan dinding bata merah ½ bata 1:5', 'm²', [['M13', 70], ['M01', 11.5], ['M02', 0.043], ['U01', 0.30], ['U02', 0.10], ['U08', 0.01], ['U09', 0.015]]],
        'D03' => ['Plesteran 1:5 tebal 15 mm', 'm²', [['M01', 7.776], ['M02', 0.023], ['U01', 0.30], ['U02', 0.15], ['U08', 0.015], ['U09', 0.015]]],
        'D04' => ['Acian', 'm²', [['M01', 3.25], ['U01', 0.20], ['U02', 0.10], ['U08', 0.01], ['U09', 0.01]]],

        // E. Kusen, pintu, jendela
        'E01' => ['Pintu utama panel + kusen', 'unit', [['M32', 1], ['U03', 0.80], ['U01', 0.40], ['U08', 0.08], ['U09', 0.04]]],
        'E02' => ['Pintu kamar multipleks + kusen', 'unit', [['M33', 1], ['U03', 0.50], ['U01', 0.25], ['U08', 0.05], ['U09', 0.025]]],
        'E03' => ['Pintu kamar mandi PVC + kusen', 'unit', [['M34', 1], ['U03', 0.30], ['U01', 0.15], ['U09', 0.015]]],
        'E04' => ['Jendela aluminium + kaca', 'unit', [['M35', 1], ['U03', 0.35], ['U01', 0.20], ['U09', 0.02]]],

        // F. Atap
        'F01' => ['Rangka atap baja ringan + reng', 'm²', [['M15', 1.0], ['U03', 0.10], ['U01', 0.10], ['U08', 0.01], ['U09', 0.005]]],
        'F02' => ['Penutup atap genteng metal pasir', 'm²', [['M16', 1.05], ['U03', 0.075], ['U01', 0.075], ['U08', 0.0075], ['U09', 0.004]]],
        'F03' => ['Penutup atap genteng beton', 'm²', [['M17', 1.05], ['U03', 0.10], ['U01', 0.10], ['U08', 0.01], ['U09', 0.005]]],
        'F04' => ['Penutup atap spandek', 'm²', [['M18', 1.05], ['U03', 0.06], ['U01', 0.06], ['U08', 0.006], ['U09', 0.003]]],
        'F05' => ['Bubungan / nok', "m'", [['M19', 1.05], ['U03', 0.10], ['U01', 0.10], ['U08', 0.01], ['U09', 0.005]]],
        'F06' => ['Lisplank GRC', "m'", [['M20', 1.0], ['U03', 0.15], ['U01', 0.10], ['U08', 0.015], ['U09', 0.008]]],

        // G. Plafon
        'G01' => ['Plafon gypsum + rangka hollow', 'm²', [['M21', 1.05], ['U03', 0.15], ['U01', 0.10], ['U08', 0.015], ['U09', 0.008]]],
        'G02' => ['Plafon PVC + rangka', 'm²', [['M22', 1.05], ['U03', 0.12], ['U01', 0.10], ['U08', 0.012], ['U09', 0.006]]],

        // H. Lantai & keramik
        'H01' => ['Lantai keramik 40x40', 'm²', [['M23', 1.05], ['M01', 10], ['M02', 0.045], ['U01', 0.30], ['U02', 0.15], ['U08', 0.015], ['U09', 0.015]]],
        'H02' => ['Lantai keramik 60x60', 'm²', [['M24', 1.05], ['M01', 10], ['M02', 0.045], ['U01', 0.35], ['U02', 0.20], ['U08', 0.02], ['U09', 0.02]]],
        'H03' => ['Lantai granit tile 60x60', 'm²', [['M25', 1.05], ['M01', 10], ['M02', 0.045], ['U01', 0.40], ['U02', 0.25], ['U08', 0.025], ['U09', 0.025]]],
        'H04' => ['Lantai kamar mandi keramik 30x30', 'm²', [['M26', 1.05], ['M01', 10], ['M02', 0.045], ['U01', 0.40], ['U02', 0.25], ['U08', 0.025], ['U09', 0.02]]],
        'H05' => ['Dinding kamar mandi keramik 25x40', 'm²', [['M27', 1.05], ['M01', 9], ['M02', 0.04], ['U01', 0.40], ['U02', 0.25], ['U08', 0.025], ['U09', 0.02]]],

        // I. Pengecatan
        'I01' => ['Cat dinding interior (plamir, primer, 2 lapis)', 'm²', [['M28', 0.10], ['M29', 0.10], ['M30', 0.26], ['U05', 0.07], ['U01', 0.02], ['U08', 0.007], ['U09', 0.003]]],
        'I02' => ['Cat dinding eksterior (plamir, primer, 2 lapis)', 'm²', [['M28', 0.10], ['M29', 0.10], ['M31', 0.26], ['U05', 0.07], ['U01', 0.02], ['U08', 0.007], ['U09', 0.003]]],
        'I03' => ['Cat plafon', 'm²', [['M28', 0.08], ['M30', 0.20], ['U05', 0.06], ['U01', 0.02], ['U08', 0.006], ['U09', 0.003]]],

        // J. Sanitair
        'J01' => ['Closet jongkok terpasang', 'unit', [['M36', 1], ['M01', 5], ['M02', 0.01], ['U07', 0.50], ['U01', 0.30], ['U09', 0.03]]],
        'J02' => ['Closet duduk terpasang', 'unit', [['M37', 1], ['M01', 3], ['U07', 0.60], ['U01', 0.30], ['U09', 0.03]]],
        'J03' => ['Kran, floor drain & shower set', 'set', [['M38', 1], ['U07', 0.60], ['U01', 0.30], ['U09', 0.03]]],
        'J04' => ['Kitchen sink stainless', 'unit', [['M39', 1], ['U07', 0.50], ['U01', 0.20], ['U09', 0.02]]],

        // K. Listrik rumah
        'K01' => ['Titik lampu + saklar (kabel, conduit)', 'titik', [['M43', 10], ['M45', 7], ['M46', 1], ['M47', 1], ['U06', 0.35], ['U01', 0.15], ['U09', 0.02]]],
        'K02' => ['Titik stop kontak (kabel, conduit)', 'titik', [['M44', 10], ['M45', 7], ['M48', 1], ['U06', 0.35], ['U01', 0.15], ['U09', 0.02]]],
        'K03' => ['MCB + box panel', 'set', [['M49', 2], ['M50', 1], ['U06', 0.50], ['U01', 0.20], ['U09', 0.02]]],

        // L. Air & sanitasi rumah
        'L01' => ['Instalasi air bersih', 'titik', [['M40', 5], ['M42', 1], ['U07', 0.25], ['U01', 0.12], ['U09', 0.01]]],
        'L02' => ['Instalasi air kotor PVC 3"', "m'", [['M41', 1.05], ['M42', 0.1], ['U07', 0.10], ['U01', 0.08], ['U09', 0.005]]],
        'L03' => ['Septictank + resapan', 'unit', [['M51', 1], ['U01', 1.50], ['U02', 0.50], ['U09', 0.05]]],

        // P. Infrastruktur kawasan
        'P01' => ['Pematangan lahan: urugan & pemadatan', 'm³', [['M06', 1.15], ['E01', 0.015], ['E02', 0.01], ['U01', 0.10], ['U09', 0.005]]],
        'P02' => ['Lapis pondasi sirtu dipadatkan', 'm³', [['M07', 1.25], ['E02', 0.02], ['U01', 0.30], ['U09', 0.015]]],
        'P03' => ['Perkerasan jalan paving block t=6 cm', 'm²', [['M52', 1.03], ['M03', 0.05], ['U01', 0.25], ['U02', 0.10], ['U08', 0.01], ['U09', 0.0125]]],
        'P04' => ['Perkerasan jalan beton K-225 t=15 cm + wiremesh', 'm²', [
            ['M01', 55.65], ['M02', 0.075], ['M05', 0.117], ['M53', 1.05], ['M10', 0.006], ['M11', 0.03],
            ['U01', 0.60], ['U02', 0.12], ['U04', 0.05], ['U08', 0.012], ['U09', 0.03],
        ]],
        'P05' => ['Saluran drainase U-ditch precast 30x40 + tutup', "m'", [['M54', 1.0], ['M55', 1.0], ['M03', 0.01], ['M01', 3], ['U01', 0.30], ['U02', 0.10], ['U08', 0.01], ['U09', 0.015]]],
        'P06' => ['Jaringan listrik tegangan rendah kawasan', "m'", [['M57', 1.0], ['U06', 0.05], ['U01', 0.08], ['U09', 0.005]]],
        'P07' => ['Tiang PJU + armatur LED terpasang', 'unit', [['M58', 1], ['U06', 1.0], ['U01', 1.0], ['U09', 0.05]]],
        'P08' => ['Pipa distribusi air bersih PVC 2"', "m'", [['M56', 1.05], ['U07', 0.08], ['U01', 0.10], ['U09', 0.005]]],
        'P09' => ['Sumur bor + menara + tandon + pompa', 'paket', [['M59', 1], ['U07', 2.0], ['U01', 4.0], ['U09', 0.2]]],
        'P10' => ['Sambungan listrik per rumah', 'unit', [['M60', 1], ['U06', 0.5], ['U01', 0.3]]],
        'P11' => ['Sambungan air bersih per rumah', 'unit', [['M61', 1], ['U07', 0.5], ['U01', 0.3]]],
    ];
}

/** Pilihan spesifikasi rumah: grup => [kunci => [label, kode AHSP]] */
function spec_opsi(): array
{
    return [
        'atap' => [
            'metal'   => ['Genteng metal pasir', 'F02'],
            'beton'   => ['Genteng beton', 'F03'],
            'spandek' => ['Spandek zincalume', 'F04'],
        ],
        'dinding' => [
            'bata_ringan' => ['Bata ringan 10 cm', 'D01'],
            'bata_merah'  => ['Bata merah ½ bata', 'D02'],
        ],
        'lantai' => [
            'keramik40' => ['Keramik 40x40', 'H01'],
            'keramik60' => ['Keramik 60x60', 'H02'],
            'granit60'  => ['Granit tile 60x60', 'H03'],
        ],
        'plafon' => [
            'gypsum' => ['Gypsum + rangka hollow', 'G01'],
            'pvc'    => ['PVC + rangka', 'G02'],
        ],
        'sanitair' => [
            'jongkok' => ['Closet jongkok', 'J01'],
            'duduk'   => ['Closet duduk', 'J02'],
        ],
    ];
}

/** Harga awal proyek baru: kode => angka */
function harga_awal(): array
{
    $o = [];
    foreach (harga_default() as $k => $d) $o[$k] = (float)$d[2];
    return $o;
}

/**
 * Hitung harga satuan tiap AHSP berdasarkan tabel harga proyek.
 * @return array kode => [kode,nama,satuan,komponen[],upah,bahan,alat,harga]
 */
function ahsp_hitung(array $harga): array
{
    $def = harga_default();
    $out = [];
    foreach (ahsp_master() as $kode => [$nama, $sat, $komp]) {
        $tot = ['upah' => 0.0, 'bahan' => 0.0, 'alat' => 0.0];
        $rows = [];
        foreach ($komp as [$k, $koef]) {
            $d = $def[$k];
            $hg = (float)($harga[$k] ?? $d[2]);
            $sub = $koef * $hg;
            $tot[$d[3]] += $sub;
            $rows[] = ['kode' => $k, 'nama' => $d[0], 'sat' => $d[1], 'koef' => $koef,
                       'harga' => $hg, 'jumlah' => $sub, 'kat' => $d[3]];
        }
        $out[$kode] = [
            'kode' => $kode, 'nama' => $nama, 'satuan' => $sat, 'komponen' => $rows,
            'upah' => $tot['upah'], 'bahan' => $tot['bahan'], 'alat' => $tot['alat'],
            'harga' => $tot['upah'] + $tot['bahan'] + $tot['alat'],
        ];
    }
    return $out;
}
