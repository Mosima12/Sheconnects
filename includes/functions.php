<?php
// includes/functions.php
declare(strict_types=1);

/** Escape for HTML output. Use this on EVERY user-generated value. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Trim + basic length validation. Returns null if valid, else error message. */
function validate_length(string $value, int $min, int $max, string $field): ?string
{
    $len = mb_strlen($value);
    if ($len < $min) return "{$field} must be at least {$min} characters.";
    if ($len > $max) return "{$field} must be at most {$max} characters.";
    return null;
}

/** Validate that a URL is http/https. Returns normalized URL or null. */
function safe_http_url(?string $url): ?string
{
    $url = trim((string)$url);
    if ($url === '') return null;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) return null;
    return $url;
}

/** Set a one-shot flash message. */
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/** Get and clear a flash message. */
function get_flash(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}