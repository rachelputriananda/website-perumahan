<?php
/**
 * Router untuk server bawaan PHP (hanya pengembangan lokal):
 *   php -S localhost:8000 router.php
 * Memblokir akses langsung ke folder internal (data proyek, kode, view, tes).
 */
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (preg_match('#(^|/)(data|lib|views|tests)(/|$)#', $path) || preg_match('#(^|/)(config|router)\.php$#', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
return false; // sajikan file statis / index.php seperti biasa
