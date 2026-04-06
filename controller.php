<?php
declare(strict_types=1);

require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/config/security.php';

header('Content-Type: application/json');

// Verify CSRF token for all POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token. Please refresh the page.']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    // ─── DNS Operations ────────────────────────────────────────────────
    case 'dns_add':
        $fqdn = trim($_POST['fqdn'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $type = trim($_POST['type'] ?? 'A');

        if ($fqdn === '' || $value === '') {
            http_response_code(400);
            echo json_encode(['error' => 'FQDN and value are required.']);
            exit();
        }
        if (!validateFqdn($fqdn)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid FQDN format.']);
            exit();
        }
        if (!validateIpOrHostname($value)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid IP address or hostname.']);
            exit();
        }
        if (!validateRecordType($type)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid record type.']);
            exit();
        }

        if (addDNSRecord($fqdn, $type, $value)) {
            echo json_encode(['success' => true, 'message' => 'DNS record added successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add DNS record.']);
        }
        break;

    case 'dns_edit':
        $fqdn = trim($_POST['fqdn'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $type = trim($_POST['type'] ?? 'A');
        $oldFqdn = trim($_POST['old_fqdn'] ?? '');
        $oldValue = trim($_POST['old_value'] ?? '');
        $oldType = trim($_POST['old_type'] ?? 'A');

        if ($fqdn === '' || $value === '' || $oldFqdn === '' || $oldValue === '') {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required.']);
            exit();
        }
        if (!validateFqdn($fqdn) || !validateFqdn($oldFqdn)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid FQDN format.']);
            exit();
        }
        if (!validateIpOrHostname($value) || !validateIpOrHostname($oldValue)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid IP address or hostname.']);
            exit();
        }

        if (editDNSRecord($fqdn, $type, $value, $oldFqdn, $oldType, $oldValue)) {
            echo json_encode(['success' => true, 'message' => 'DNS record updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update DNS record. Record may not exist.']);
        }
        break;

    case 'dns_delete':
        $fqdn = trim($_POST['fqdn'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $type = trim($_POST['type'] ?? 'A');

        if ($fqdn === '' || $value === '') {
            http_response_code(400);
            echo json_encode(['error' => 'FQDN and value are required.']);
            exit();
        }

        if (deleteDNSRecord($fqdn, $type, $value)) {
            echo json_encode(['success' => true, 'message' => 'DNS record deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete DNS record.']);
        }
        break;

    case 'dns_restart':
        if (restartDNSService()) {
            echo json_encode(['success' => true, 'message' => 'Unbound service reloaded successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reload DNS service.']);
        }
        break;

    case 'dns_query':
        $domain = trim($_POST['domain'] ?? '');
        if ($domain === '' || !validateFqdn($domain)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid domain name.']);
            exit();
        }
        $result = queryDNS($domain);
        auditLog('dns_query', "Queried: {$domain}");
        echo json_encode(['success' => true, 'result' => $result]);
        break;

    // ─── DNS List (AJAX Pagination) ────────────────────────────────────
    case 'dns_list':
        $page = max(1, (int)($_POST['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_POST['per_page'] ?? 25)));
        $search = trim($_POST['search'] ?? '');
        $typeFilter = trim($_POST['type_filter'] ?? '');
        $result = getDNSRecordsPaginated($page, $perPage, $search, $typeFilter);
        $result['has_changes'] = hasUnappliedChanges();
        echo json_encode($result);
        break;

    // ─── Password Change ───────────────────────────────────────────────
    case 'change_password':
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if ($old === '' || $new === '') {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required.']);
            exit();
        }
        $result = changePassword(currentUserId(), $old, $new);
        if ($result === 'success') {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        } elseif ($result === 'invalid') {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is incorrect.']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result]);
        }
        break;

    // ─── User Management (Admin Only) ──────────────────────────────────
    case 'user_create':
        requireAdmin();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $email = trim($_POST['email'] ?? '');

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required.']);
            exit();
        }
        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters.']);
            exit();
        }
        if (!in_array($role, ['admin', 'user'], true)) {
            $role = 'user';
        }

        if (createUser($username, $password, $role, $email)) {
            echo json_encode(['success' => true, 'message' => "User '{$username}' created."]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists.']);
        }
        break;

    case 'user_update':
        requireAdmin();
        $id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $email = trim($_POST['email'] ?? '');
        $isActive = (bool)($_POST['is_active'] ?? true);
        $newPassword = $_POST['password'] ?? '';

        if ($id <= 0 || $username === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input.']);
            exit();
        }

        if (updateUser($id, $username, $role, $email, $isActive, $newPassword)) {
            echo json_encode(['success' => true, 'message' => "User '{$username}' updated."]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Username may already exist.']);
        }
        break;

    case 'user_delete':
        requireAdmin();
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user ID.']);
            exit();
        }
        if (deleteUser($id)) {
            echo json_encode(['success' => true, 'message' => 'User deleted.']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete this user.']);
        }
        break;

    case 'user_list':
        requireAdmin();
        echo json_encode(['users' => getAllUsers()]);
        break;

    // ─── Audit Logs ────────────────────────────────────────────────────
    case 'get_logs':
        $page = max(1, (int)($_POST['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_POST['per_page'] ?? 50)));
        $search = trim($_POST['search'] ?? '');
        $actionFilter = trim($_POST['action_filter'] ?? '');
        echo json_encode(getAuditLogs($page, $perPage, $search, $actionFilter));
        break;

    // ─── SIEM Configuration (Admin Only) ───────────────────────────────
    case 'siem_get_config':
        requireAdmin();
        echo json_encode(getSiemConfig());
        break;

    case 'siem_save_config':
        requireAdmin();
        $forwardingRules = trim($_POST['forwarding_rules'] ?? '');
        $result = saveSiemConfig($forwardingRules);
        if ($result === true) {
            echo json_encode(['success' => true, 'message' => 'SIEM configuration saved and rsyslog reloaded.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $result]);
        }
        break;

    case 'siem_test':
        requireAdmin();
        $testResult = sendSiemTestLog();
        echo json_encode(['success' => true, 'message' => 'Test log sent.', 'detail' => $testResult]);
        break;

    case 'siem_recent_logs':
        requireAdmin();
        $lines = max(10, min(200, (int)($_POST['lines'] ?? 50)));
        echo json_encode(['logs' => getRecentSyslogEntries($lines)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action.']);
}
