<?php
declare(strict_types=1);

/* ---------- Output & format ---------- */

function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function angka(float $v, int $dec = 2): string
{
    return number_format($v, $dec, ',', '.');
}

function rp(float $v): string
{
    return 'Rp ' . number_format(round($v), 0, ',', '.');
}

/** Rupiah ringkas: Rp 1,25 M / Rp 850 jt */
function rp_ringkas(float $v): string
{
    $a = abs($v);
    if ($a >= 1e12) return 'Rp ' . angka($v / 1e12, 2) . ' T';
    if ($a >= 1e9)  return 'Rp ' . angka($v / 1e9, 2) . ' M';
    if ($a >= 1e6)  return 'Rp ' . angka($v / 1e6, 1) . ' jt';
    return rp($v);
}

/* ---------- URL, redirect, flash ---------- */

function url(string $r = 'home', array $q = []): string
{
    return 'index.php?' . http_build_query(['r' => $r] + $q);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Input ---------- */

/** Ambil angka dari input (menerima "12,5" maupun "12.5"), dibatasi min..max. */
function num(mixed $raw, float $def, float $min = -INF, float $max = INF): float
{
    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') return $def;
        if (str_contains($raw, ',') && !str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        }
    }
    if (!is_numeric($raw)) return $def;
    $v = (float)$raw;
    if (!is_finite($v)) return $def;
    return max($min, min($max, $v));
}

function str_in(mixed $raw, int $max = 80): string
{
    $s = is_string($raw) ? trim($raw) : '';
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s) ?? '';
    return mb_substr($s, 0, $max);
}

function valid_id(string $id): bool
{
    return (bool)preg_match('/^[a-z0-9]{6,32}$/', $id);
}

/* ---------- View ---------- */

function render(string $view, array $vars = [], string $title = ''): void
{
    $file = __DIR__ . '/../views/' . $view . '.php';
    if (!is_file($file)) {
        http_response_code(500);
        exit('View tidak ditemukan.');
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    $content = ob_get_clean();
    $pageTitle = $title;
    require __DIR__ . '/../views/layout.php';
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
