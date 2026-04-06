<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

define('HOST_ENTRIES_FILE', '/etc/unbound/host_entries.conf');
define('HASH_FILE', __DIR__ . '/../filehash.txt');
define('SYSLOG_ENABLED', true);
define('SYSLOG_IDENT', 'unbound-dns-panel');
define('SYSLOG_CEF_VENDOR', 'InfinitumIT');
define('SYSLOG_CEF_PRODUCT', 'UnboundDNSPanel');
define('SYSLOG_CEF_VERSION', '1.0');

// ─── Authentication ────────────────────────────────────────────────────────────

function authenticateUser(string $username, string $password): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, username, password, role, is_active FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        return null;
    }
    if (!password_verify($password, $user['password'])) {
        return null;
    }
    // Rehash if needed (algorithm upgrade)
    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $user['id']]);
    }
    return $user;
}

// ─── Rate Limiting ─────────────────────────────────────────────────────────────

function checkLoginRateLimit(string $ip): bool
{
    global $pdo;
    // Clean old attempts (older than 15 min)
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->execute();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() < 10; // max 10 attempts per 15 min
}

function recordLoginAttempt(string $ip, string $username): void
{
    global $pdo;
    $pdo->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)')->execute([$ip, $username]);
}

// ─── Audit Logging ─────────────────────────────────────────────────────────────

function auditLog(string $action, string $details = '', int $userId = 0, string $username = ''): void
{
    global $pdo;
    if ($userId === 0 && function_exists('currentUserId')) {
        $userId = currentUserId();
    }
    if ($userId === 0) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }
    if ($username === '' && function_exists('currentUsername')) {
        $username = currentUsername();
    }
    if ($username === '') {
        $username = $_SESSION['username'] ?? 'system';
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Database log
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $username, $action, $details, $ip]);

    // Syslog in CEF format
    if (SYSLOG_ENABLED) {
        sendToSyslog($action, $details, $username, $userId, $ip);
    }
}

// ─── Syslog / SIEM Integration ─────────────────────────────────────────────────

function getSyslogSeverity(string $action): int
{
    // Map actions to syslog severity levels
    $severityMap = [
        'login'           => LOG_INFO,      // 6
        'logout'          => LOG_INFO,      // 6
        'dns_add'         => LOG_NOTICE,    // 5
        'dns_edit'        => LOG_NOTICE,    // 5
        'dns_delete'      => LOG_WARNING,   // 4
        'dns_restart'     => LOG_NOTICE,    // 5
        'dns_query'       => LOG_INFO,      // 6
        'user_create'     => LOG_NOTICE,    // 5
        'user_update'     => LOG_NOTICE,    // 5
        'user_delete'     => LOG_WARNING,   // 4
        'password_change' => LOG_NOTICE,    // 5
        'login_failed'    => LOG_WARNING,   // 4
    ];
    return $severityMap[$action] ?? LOG_INFO;
}

function getCefSeverity(string $action): int
{
    // CEF severity: 0-3 Low, 4-6 Medium, 7-8 High, 9-10 Critical
    $cefMap = [
        'login'           => 1,
        'logout'          => 1,
        'dns_add'         => 3,
        'dns_edit'        => 3,
        'dns_delete'      => 5,
        'dns_restart'     => 4,
        'dns_query'       => 1,
        'user_create'     => 3,
        'user_update'     => 3,
        'user_delete'     => 6,
        'password_change' => 4,
        'login_failed'    => 5,
    ];
    return $cefMap[$action] ?? 3;
}

function getCefEventName(string $action): string
{
    $nameMap = [
        'login'           => 'User Login',
        'logout'          => 'User Logout',
        'dns_add'         => 'DNS Record Added',
        'dns_edit'        => 'DNS Record Modified',
        'dns_delete'      => 'DNS Record Deleted',
        'dns_restart'     => 'DNS Service Reloaded',
        'dns_query'       => 'DNS Query Executed',
        'user_create'     => 'User Account Created',
        'user_update'     => 'User Account Modified',
        'user_delete'     => 'User Account Deleted',
        'password_change' => 'Password Changed',
        'login_failed'    => 'Login Failed',
    ];
    return $nameMap[$action] ?? $action;
}

function sendToSyslog(string $action, string $details, string $username, int $userId, string $ip): void
{
    $severity = getSyslogSeverity($action);
    $cefSeverity = getCefSeverity($action);
    $eventName = getCefEventName($action);

    // Escape CEF extension values (pipe and backslash)
    $escDetails = str_replace(['\\', '='], ['\\\\', '\\='], $details);
    $escUsername = str_replace(['\\', '='], ['\\\\', '\\='], $username);

    // CEF format: CEF:Version|Device Vendor|Device Product|Device Version|Signature ID|Name|Severity|Extensions
    $cef = sprintf(
        'CEF:0|%s|%s|%s|%s|%s|%d|src=%s suser=%s suid=%d msg=%s dhost=%s',
        SYSLOG_CEF_VENDOR,
        SYSLOG_CEF_PRODUCT,
        SYSLOG_CEF_VERSION,
        $action,
        $eventName,
        $cefSeverity,
        $ip,
        $escUsername,
        $userId,
        $escDetails,
        gethostname()
    );

    openlog(SYSLOG_IDENT, LOG_PID | LOG_NDELAY, LOG_LOCAL6);
    syslog($severity, $cef);
    closelog();
}

function getAuditLogs(int $page = 1, int $perPage = 50, string $search = '', string $actionFilter = ''): array
{
    global $pdo;
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($actionFilter !== '') {
        $where[] = 'action = ?';
        $params[] = $actionFilter;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs {$whereClause}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data
    $offset = ($page - 1) * $perPage;
    $dataStmt = $pdo->prepare("SELECT * FROM audit_logs {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $allParams = array_merge($params, [$perPage, $offset]);
    $dataStmt->execute($allParams);
    $logs = $dataStmt->fetchAll();

    return [
        'data'       => $logs,
        'total'      => $total,
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => (int)ceil($total / $perPage),
    ];
}

// ─── DNS Record Parsing ────────────────────────────────────────────────────────

function parseDNSRecords(): array
{
    $records = [];
    if (!file_exists(HOST_ENTRIES_FILE)) {
        return $records;
    }

    $handle = fopen(HOST_ENTRIES_FILE, 'r');
    if (!$handle) {
        return $records;
    }

    $lineNum = 0;
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        $lineNum++;
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, 'local-data-ptr:')) {
            continue;
        }
        // Parse: local-data: "fqdn. TYPE value"
        if (preg_match('/^local-data:\s*"([^"]+)"/', $line, $matches)) {
            $parts = preg_split('/\s+/', trim($matches[1]));
            if (count($parts) >= 3) {
                $fqdn = rtrim($parts[0], '.');
                $type = $parts[1];
                if ($type === 'MX' && count($parts) >= 4) {
                    $value = $parts[3]; // skip priority
                    $priority = (int)$parts[2];
                } else {
                    $value = $parts[2];
                    $priority = 0;
                }
                $records[] = [
                    'line'     => $lineNum,
                    'fqdn'     => $fqdn,
                    'type'     => $type,
                    'value'    => $value,
                    'priority' => $priority,
                    'raw'      => $line,
                ];
            }
        }
    }
    fclose($handle);
    return $records;
}

function getDNSRecordsPaginated(int $page = 1, int $perPage = 25, string $search = '', string $typeFilter = ''): array
{
    $allRecords = parseDNSRecords();

    // Filter
    if ($search !== '' || $typeFilter !== '') {
        $allRecords = array_filter($allRecords, function ($r) use ($search, $typeFilter) {
            $matchSearch = true;
            $matchType = true;
            if ($search !== '') {
                $s = strtolower($search);
                $matchSearch = str_contains(strtolower($r['fqdn']), $s)
                    || str_contains(strtolower($r['value']), $s);
            }
            if ($typeFilter !== '') {
                $matchType = $r['type'] === $typeFilter;
            }
            return $matchSearch && $matchType;
        });
        $allRecords = array_values($allRecords);
    }

    $total = count($allRecords);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    $data = array_slice($allRecords, $offset, $perPage);

    return [
        'data'       => $data,
        'total'      => $total,
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => $totalPages,
    ];
}

// ─── DNS Record Manipulation (Safe - No shell_exec sed) ────────────────────────

function validateFqdn(string $fqdn): bool
{
    // Allow valid hostnames: letters, digits, dots, hyphens, underscores
    return (bool)preg_match('/^[a-zA-Z0-9._-]{1,253}$/', $fqdn);
}

function validateIpOrHostname(string $value): bool
{
    // Valid IP address or hostname
    if (filter_var($value, FILTER_VALIDATE_IP)) {
        return true;
    }
    return (bool)preg_match('/^[a-zA-Z0-9._-]{1,253}$/', $value);
}

function validateRecordType(string $type): bool
{
    return in_array($type, ['A', 'AAAA', 'MX', 'CNAME', 'TXT'], true);
}

function buildRecordLine(string $fqdn, string $type, string $value, int $priority = 10): string
{
    $fqdn = rtrim($fqdn, '.') . '.';
    if ($type === 'MX') {
        return "local-data: \"{$fqdn} {$type} {$priority} {$value}\"";
    }
    return "local-data: \"{$fqdn} {$type} {$value}\"";
}

function addDNSRecord(string $fqdn, string $type, string $value): bool
{
    if (!validateFqdn($fqdn) || !validateIpOrHostname($value) || !validateRecordType($type)) {
        return false;
    }

    $line = buildRecordLine($fqdn, $type, $value);
    $result = file_put_contents(HOST_ENTRIES_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($result !== false) {
        auditLog('dns_add', "Added {$type} record: {$fqdn} -> {$value}");
        return true;
    }
    return false;
}

function editDNSRecord(string $fqdn, string $type, string $value, string $oldFqdn, string $oldType, string $oldValue): bool
{
    if (!validateFqdn($fqdn) || !validateIpOrHostname($value) || !validateRecordType($type)) {
        return false;
    }
    if (!validateFqdn($oldFqdn) || !validateIpOrHostname($oldValue) || !validateRecordType($oldType)) {
        return false;
    }

    $oldLine = buildRecordLine($oldFqdn, $oldType, $oldValue);
    $newLine = buildRecordLine($fqdn, $type, $value);

    $content = file_get_contents(HOST_ENTRIES_FILE);
    if ($content === false) {
        return false;
    }

    $newContent = str_replace($oldLine, $newLine, $content, $count);
    if ($count === 0) {
        return false;
    }

    if (file_put_contents(HOST_ENTRIES_FILE, $newContent, LOCK_EX) !== false) {
        auditLog('dns_edit', "Edited: {$oldFqdn} ({$oldType} {$oldValue}) -> {$fqdn} ({$type} {$value})");
        return true;
    }
    return false;
}

function deleteDNSRecord(string $fqdn, string $type, string $value): bool
{
    if (!validateFqdn($fqdn) || !validateIpOrHostname($value) || !validateRecordType($type)) {
        return false;
    }

    $targetLine = buildRecordLine($fqdn, $type, $value);
    $content = file_get_contents(HOST_ENTRIES_FILE);
    if ($content === false) {
        return false;
    }

    $lines = explode("\n", $content);
    $newLines = [];
    $found = false;
    foreach ($lines as $line) {
        if (trim($line) === $targetLine) {
            $found = true;
            continue;
        }
        $newLines[] = $line;
    }

    if (!$found) {
        return false;
    }

    $newContent = implode("\n", $newLines);
    if (file_put_contents(HOST_ENTRIES_FILE, $newContent, LOCK_EX) !== false) {
        auditLog('dns_delete', "Deleted {$type} record: {$fqdn} -> {$value}");
        return true;
    }
    return false;
}

// ─── DNS Service ───────────────────────────────────────────────────────────────

function getHostEntriesHash(): string
{
    if (!file_exists(HOST_ENTRIES_FILE)) {
        return '';
    }
    return hash_file('sha256', HOST_ENTRIES_FILE);
}

function readStoredHash(): string
{
    if (!file_exists(HASH_FILE)) {
        return '';
    }
    return trim((string)file_get_contents(HASH_FILE));
}

function hasUnappliedChanges(): bool
{
    return getHostEntriesHash() !== readStoredHash();
}

function restartDNSService(): bool
{
    $output = shell_exec('sudo /usr/sbin/service unbound reload 2>&1');
    $hash = getHostEntriesHash();
    file_put_contents(HASH_FILE, $hash);
    auditLog('dns_restart', 'Unbound service reloaded. Output: ' . ($output ?? 'no output'));
    return true;
}

// ─── User Management ───────────────────────────────────────────────────────────

function getAllUsers(): array
{
    global $pdo;
    return $pdo->query('SELECT id, username, role, email, is_active, created_at, updated_at FROM users ORDER BY id')->fetchAll();
}

function getUserById(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, username, role, email, is_active, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createUser(string $username, string $password, string $role = 'user', string $email = ''): bool
{
    global $pdo;
    // Check duplicate
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)');
    $result = $stmt->execute([$username, $hash, $role, $email]);

    if ($result) {
        auditLog('user_create', "Created user: {$username} (role: {$role})");
    }
    return $result;
}

function updateUser(int $id, string $username, string $role, string $email, bool $isActive, string $newPassword = ''): bool
{
    global $pdo;
    // Check duplicate username excluding self
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }

    if ($newPassword !== '') {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, role = ?, email = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$username, $hash, $role, $email, $isActive ? 1 : 0, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, role = ?, email = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$username, $role, $email, $isActive ? 1 : 0, $id]);
    }

    auditLog('user_update', "Updated user #{$id}: {$username} (role: {$role}, active: " . ($isActive ? 'yes' : 'no') . ')');
    return true;
}

function deleteUser(int $id): bool
{
    global $pdo;
    // Prevent deleting yourself
    if ($id === currentUserId()) {
        return false;
    }
    $user = getUserById($id);
    if (!$user) {
        return false;
    }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    auditLog('user_delete', "Deleted user: {$user['username']}");
    return true;
}

function changePassword(int $userId, string $oldPassword, string $newPassword): string
{
    global $pdo;
    if (strlen($newPassword) < 8) {
        return 'Password must be at least 8 characters.';
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return 'invalid';
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
    auditLog('password_change', 'Password changed');
    return 'success';
}

// ─── DNS Query ─────────────────────────────────────────────────────────────────

function queryDNS(string $domain, string $server = '127.0.0.1'): string
{
    // Validate domain
    if (!validateFqdn($domain)) {
        return 'Invalid domain name.';
    }
    // Use dig with escapeshellarg for safety
    $domain = escapeshellarg($domain);
    $server = escapeshellarg('@' . $server);
    $output = shell_exec("dig {$server} {$domain} +short 2>&1");
    return $output ?? 'No result.';
}

// ─── SIEM Configuration ────────────────────────────────────────────────────────

define('RSYSLOG_CONF_PATH', '/etc/rsyslog.d/60-unbound-dns-panel.conf');
define('SYSLOG_LOG_PATH', '/var/log/unbound-dns-panel.log');

function getSiemConfig(): array
{
    $conf = '';
    $forwardingRules = '';
    $status = 'unknown';

    if (file_exists(RSYSLOG_CONF_PATH)) {
        $conf = file_get_contents(RSYSLOG_CONF_PATH);
        // Extract the forwarding section between the markers
        if (preg_match('/# ─── SIEM Forwarding.*?─+\n(.*?)# ─+/s', $conf, $m)) {
            $forwardingRules = trim($m[1]);
        }
    }

    // Check rsyslog service status
    $serviceOutput = trim(shell_exec('systemctl is-active rsyslog 2>&1') ?? '');
    $status = $serviceOutput === 'active' ? 'active' : 'inactive';

    // Check if there are active (uncommented) forwarding rules
    $hasActiveRules = false;
    if ($forwardingRules !== '') {
        $lines = explode("\n", $forwardingRules);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && !str_starts_with($trimmed, '#')) {
                $hasActiveRules = true;
                break;
            }
        }
    }

    return [
        'forwarding_rules' => $forwardingRules,
        'rsyslog_status'   => $status,
        'has_active_rules'  => $hasActiveRules,
        'log_file'         => SYSLOG_LOG_PATH,
        'facility'         => 'local6',
    ];
}

function saveSiemConfig(string $forwardingRules): string|bool
{
    // Validate: no dangerous shell characters / commands
    if (preg_match('/[;&|`$]/', $forwardingRules)) {
        return 'Invalid characters detected in configuration.';
    }

    // Build the full config
    $config = <<<CONF
# InfinitumIT Unbound DNS Panel - Syslog Configuration
# Logs from the DNS management panel (facility local6)

# Template: SIEM-compatible format with ISO8601 timestamp
template(name="UnboundDNSPanelFormat" type="string"
    string="%timegenerated:::date-rfc3339% %HOSTNAME% %syslogtag%%msg%\\n"
)

# Write to dedicated log file
local6.*    /var/log/unbound-dns-panel.log;UnboundDNSPanelFormat

# ─── SIEM Forwarding ────────────────────────────────────────────────────
{$forwardingRules}
# ─────────────────────────────────────────────────────────────────────────

# Stop processing these messages in other log files
& stop
CONF;

    // Write config via sudo tee
    $tmpFile = tempnam(sys_get_temp_dir(), 'rsyslog_');
    file_put_contents($tmpFile, $config);

    $output = shell_exec("sudo cp {$tmpFile} " . RSYSLOG_CONF_PATH . " 2>&1");
    unlink($tmpFile);

    // Validate rsyslog config
    $validate = shell_exec('sudo rsyslogd -N1 2>&1');
    if (str_contains($validate ?? '', 'error')) {
        return 'rsyslog config validation failed: ' . $validate;
    }

    // Restart rsyslog
    shell_exec('sudo systemctl restart rsyslog 2>&1');

    auditLog('siem_config', 'SIEM forwarding configuration updated');
    return true;
}

function sendSiemTestLog(): string
{
    $testEvents = [
        ['login', 'SIEM Test: User login event'],
        ['dns_add', 'SIEM Test: DNS record added - test.example.com A 192.168.1.1'],
        ['dns_delete', 'SIEM Test: DNS record deleted - test.example.com'],
        ['login_failed', 'SIEM Test: Failed login attempt from 10.0.0.1'],
    ];

    foreach ($testEvents as [$action, $detail]) {
        sendToSyslog($action, $detail, currentUsername(), currentUserId(), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    auditLog('siem_test', 'Sent ' . count($testEvents) . ' test events to syslog');
    return count($testEvents) . ' test events sent to syslog (facility local6).';
}

function getRecentSyslogEntries(int $lines = 50): array
{
    if (!file_exists(SYSLOG_LOG_PATH)) {
        return [];
    }
    $output = shell_exec("tail -n {$lines} " . escapeshellarg(SYSLOG_LOG_PATH) . " 2>&1");
    if ($output === null || $output === '') {
        return [];
    }
    $entries = array_filter(explode("\n", trim($output)));
    return array_reverse($entries); // newest first
}
