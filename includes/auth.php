<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('sakubijak');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): void
{
    if (!current_user()) {
        header('Location: ' . app_url('auth/login.php'));
        exit;
    }
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['nama'], 'email' => $u['email']];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrf_check($t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }
