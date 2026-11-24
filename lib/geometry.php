<?php
declare(strict_types=1);

/**
 * Luas gabungan (union) dari kumpulan persegi panjang [x,y,w,h].
 * Dipakai agar persimpangan jalan tidak dihitung dua kali.
 * Metode: kompresi koordinat sumbu-x, lalu gabungkan interval-y tiap lajur.
 */
function union_area(array $rects): float
{
    if (!$rects) return 0.0;

    $xs = [];
    foreach ($rects as $r) {
        $xs[] = round((float)$r['x'], 4);
        $xs[] = round((float)$r['x'] + (float)$r['w'], 4);
    }
    $xs = array_values(array_unique($xs));
    sort($xs);

    $area = 0.0;
    for ($i = 0, $n = count($xs) - 1; $i < $n; $i++) {
        $x1 = $xs[$i];
        $x2 = $xs[$i + 1];
        if ($x2 - $x1 < 1e-9) continue;

        $iv = [];
        foreach ($rects as $r) {
            $rx1 = (float)$r['x'];
            $rx2 = $rx1 + (float)$r['w'];
            if ($rx1 <= $x1 + 1e-6 && $rx2 >= $x2 - 1e-6) {
                $iv[] = [(float)$r['y'], (float)$r['y'] + (float)$r['h']];
            }
        }
        if (!$iv) continue;
        usort($iv, fn($a, $b) => $a[0] <=> $b[0]);

        $len = 0.0;
        [$cs, $ce] = $iv[0];
        for ($k = 1, $m = count($iv); $k < $m; $k++) {
            if ($iv[$k][0] <= $ce) {
                $ce = max($ce, $iv[$k][1]);
            } else {
                $len += $ce - $cs;
                [$cs, $ce] = $iv[$k];
            }
        }
        $len += $ce - $cs;
        $area += ($x2 - $x1) * $len;
    }
    return $area;
}

/** Luas irisan dua persegi panjang. */
function overlap_area(array $a, array $b): float
{
    $ox = min($a['x'] + $a['w'], $b['x'] + $b['w']) - max($a['x'], $b['x']);
    $oy = min($a['y'] + $a['h'], $b['y'] + $b['h']) - max($a['y'], $b['y']);
    return ($ox > 0 && $oy > 0) ? $ox * $oy : 0.0;
}
