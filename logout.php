<?php
declare(strict_types=1);

require_once __DIR__ . '/core/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    auditLog('logout', "User '" . ($_SESSION['username'] ?? '') . "' logged out");
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
