<?php
// includes/auth.php
declare(strict_types=1);

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie a little; still works on localhost (Secure=false).
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

/** Redirect to login if not authenticated. Call at top of protected pages. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Generate (or reuse) the CSRF token for this session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Echo a ready-made hidden CSRF input. */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verify CSRF on POST. Aborts on failure. */
function verify_csrf_or_die(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Invalid request token. Please reload the page and try again.');
    }
}