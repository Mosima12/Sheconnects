<?php
// logout.php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Destroy the session and rotate the cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

session_start(); // start a fresh one just for the flash message
flash('success', 'You’ve been logged out.');
header('Location: ' . BASE_URL . '/index.php');
exit;