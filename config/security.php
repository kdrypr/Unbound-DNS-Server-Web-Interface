<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Session timeout: 30 minutes
$timeout = 30 * 60;
if (isset($_SESSION['last_active']) && $_SESSION['last_active'] < (time() - $timeout)) {
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=1');
    exit();
}

// Session fingerprint check
$fingerprint = hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '', true));
if (isset($_SESSION['fingerprint']) && !hash_equals($_SESSION['fingerprint'], $fingerprint)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// Regenerate session ID periodically (every 5 minutes)
if (!isset($_SESSION['regenerated_at']) || $_SESSION['regenerated_at'] < (time() - 300)) {
    session_regenerate_id(true);
    $_SESSION['regenerated_at'] = time();
}

$_SESSION['last_active'] = time();
$_SESSION['fingerprint'] = $fingerprint;

// CSRF token management
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// Current user helpers
function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUsername(): string
{
    return $_SESSION['username'] ?? '';
}

function currentUserRole(): string
{
    return $_SESSION['role'] ?? 'user';
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
