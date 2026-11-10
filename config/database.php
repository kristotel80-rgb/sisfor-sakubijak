<?php
// Konfigurasi database - sesuaikan dengan server Anda
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sakubijak');
define('DB_USER', 'root');
define('DB_PASS', '');

date_default_timezone_set('Asia/Jakarta');

// BASE_URL otomatis, mis. "/sakubijak" bila ditaruh di htdocs/sakubijak
$__root = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$__doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
define('BASE_URL', rtrim(str_replace($__doc, '', $__root), '/'));

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
