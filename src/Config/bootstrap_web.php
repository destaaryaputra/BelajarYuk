<?php
/**
 * Belajaryuk - Web Bootstrap
 * Inisialisasi session dan CSRF untuk frontend
 */

$env_file = __DIR__ . '/lingkungan.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set double-submit cookie for production stability
if (!isset($_COOKIE['csrf_token']) || $_COOKIE['csrf_token'] !== $_SESSION['csrf_token']) {
    setcookie('csrf_token', $_SESSION['csrf_token'], [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => false, // JS needs to read it or we just rely on headers
        'samesite' => 'Lax'
    ]);
}

function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? $_COOKIE['csrf_token'] ?? '';
}
