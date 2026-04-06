<?php
declare(strict_types=1);

require_once __DIR__ . '/core/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all fields.']);
    exit();
}

// Rate limiting
if (!checkLoginRateLimit($ip)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many login attempts. Please try again in 15 minutes.']);
    exit();
}

recordLoginAttempt($ip, $username);

$user = authenticateUser($username, $password);
if ($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_active'] = time();
    $_SESSION['fingerprint'] = hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', hash('sha256', $ip, true));
    $_SESSION['regenerated_at'] = time();

    // Log successful login
    auditLog('login', "User '{$user['username']}' logged in from {$ip}");

    echo json_encode(['success' => true]);
} else {
    // Log failed login to syslog
    sendToSyslog('login_failed', "Failed login attempt for user '{$username}' from {$ip}", $username, 0, $ip);
    http_response_code(401);
    echo json_encode(['error' => 'Invalid username or password.']);
}
