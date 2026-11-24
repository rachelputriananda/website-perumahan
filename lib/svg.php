<?php
declare(strict_types=1);

/** Pratinjau site plan sebagai SVG (dirender di server, dipakai di ringkasan & cetak RAB). */
function siteplan_svg(array $objs, array $lahan, array $tipeList, int $lebarPx = 720): string
{
    $P = max(1.0, (float)$lahan['panjang']);
    $L = max(1.0, (float)$lahan['lebar']);
    $s = $lebarPx / $P;
    $tinggi = $L * $s;

    $warna = [];
    foreach ($tipeList as $i => $t) $warna[$t['id']] = WARNA_TIPE[$i % count(WARNA_TIPE)];

    $o = sprintf('<svg class="spsvg" viewBox="0 0 %.1f %.1f" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Pratinjau site plan">', $lebarPx, $tinggi);
    $o .= sprintf('<rect x="0" y="0" width="%.1f" height="%.1f" fill="#f4f1e8" stroke="#8a8577" stroke-width="1.5"/>', $lebarPx, $tinggi);

    foreach (['road', 'fasum', 'lot'] as $urut) {
        foreach ($objs as $b) {
            if ($b['type'] !== $urut) continue;
            $x = $b['x'] * $s; $y = $b['y'] * $s; $w = $b['w'] * $s; $h = $b['h'] * $s;
            if ($urut === 'road') {
                $fill = '#9aa0a8'; $stroke = 'none';
            } elseif ($urut === 'fasum') {
                $fill = FASUM_JENIS[$b['jenis']][2] ?? '#e0b48a'; $stroke = '#6b7a6d';
            } else {
                $fill = $warna[$b['tipe_id']] ?? '#d9d4c4'; $stroke = '#ffffff';
            }
            $o .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s" stroke="%s" stroke-width="0.8" fill-opacity="%s"/>',
                $x, $y, $w, $h, $fill, $stroke, $urut === 'lot' ? '0.9' : '1');
            if ($urut === 'fasum' && $w > 40 && $h > 14) {
                $o .= sprintf('<text x="%.1f" y="%.1f" font-size="10" fill="#334" text-anchor="middle" dominant-baseline="middle">%s</text>',
                    $x + $w / 2, $y + $h / 2, h($b['label']));
            }
        }
    }
    return $o . '</svg>';
}
