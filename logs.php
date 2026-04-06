<?php
declare(strict_types=1);
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/config/security.php';
include 'core/header.php';
include 'core/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-3"><i class="bx bx-list-ul me-2"></i>Audit Logs</h5>
                <div class="dns-toolbar">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="logSearch" placeholder="Search logs...">
                    </div>
                    <select class="form-select" id="actionFilter" style="max-width: 180px;">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="dns_add">DNS Add</option>
                        <option value="dns_edit">DNS Edit</option>
                        <option value="dns_delete">DNS Delete</option>
                        <option value="dns_restart">DNS Restart</option>
                        <option value="dns_query">DNS Query</option>
                        <option value="user_create">User Create</option>
                        <option value="user_update">User Update</option>
                        <option value="user_delete">User Delete</option>
                        <option value="password_change">Password Change</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 170px;">Date</th>
                            <th style="width: 120px;">User</th>
                            <th style="width: 130px;">Action</th>
                            <th>Details</th>
                            <th style="width: 130px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted" id="logInfo">Loading...</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="logPagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
<script>
var logPage = 1;
var logSearchTimer = null;

$(document).ready(function() {
    loadLogs();
    $('#logSearch').on('input', function() {
        clearTimeout(logSearchTimer);
        logSearchTimer = setTimeout(function() { logPage = 1; loadLogs(); }, 400);
    });
    $('#actionFilter').on('change', function() { logPage = 1; loadLogs(); });
});

function loadLogs() {
    ajaxPost({
        action: 'get_logs',
        page: logPage,
        per_page: 50,
        search: $('#logSearch').val(),
        action_filter: $('#actionFilter').val()
    }).done(function(data) {
        var html = '';
        if (data.data.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted py-4">No logs found.</td></tr>';
        } else {
            data.data.forEach(function(log) {
                html += '<tr>' +
                    '<td><small>' + escapeHtml(log.created_at) + '</small></td>' +
                    '<td><strong>' + escapeHtml(log.username) + '</strong></td>' +
                    '<td>' + getActionBadge(log.action) + '</td>' +
                    '<td><small>' + escapeHtml(log.details || '') + '</small></td>' +
                    '<td><code class="small">' + escapeHtml(log.ip_address) + '</code></td>' +
                    '</tr>';
            });
        }
        $('#logsTableBody').html(html);
        $('#logInfo').text('Showing ' + data.data.length + ' of ' + data.total + ' logs (Page ' + data.page + '/' + data.totalPages + ')');
        renderLogPagination(data);
    });
}

function getActionBadge(action) {
    var colors = {
        'login': 'success', 'logout': 'secondary',
        'dns_add': 'primary', 'dns_edit': 'info', 'dns_delete': 'danger', 'dns_restart': 'warning', 'dns_query': 'dark',
        'user_create': 'primary', 'user_update': 'info', 'user_delete': 'danger',
        'password_change': 'warning'
    };
    var labels = {
        'login': 'Login', 'logout': 'Logout',
        'dns_add': 'DNS Add', 'dns_edit': 'DNS Edit', 'dns_delete': 'DNS Delete', 'dns_restart': 'DNS Restart', 'dns_query': 'DNS Query',
        'user_create': 'User Create', 'user_update': 'User Update', 'user_delete': 'User Delete',
        'password_change': 'Password'
    };
    return '<span class="badge bg-label-' + (colors[action] || 'dark') + '">' + (labels[action] || action) + '</span>';
}

function renderLogPagination(data) {
    var html = '';
    var tp = data.totalPages;
    var cp = data.page;
    if (tp <= 1) { $('#logPagination').html(''); return; }

    html += '<li class="page-item ' + (cp === 1 ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0);" onclick="goLogPage(' + (cp-1) + ')"><i class="bx bx-chevron-left"></i></a></li>';
    var start = Math.max(1, cp - 2), end = Math.min(tp, cp + 2);
    if (start > 1) {
        html += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="goLogPage(1)">1</a></li>';
        if (start > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for (var p = start; p <= end; p++) {
        html += '<li class="page-item ' + (p === cp ? 'active' : '') + '"><a class="page-link" href="javascript:void(0);" onclick="goLogPage(' + p + ')">' + p + '</a></li>';
    }
    if (end < tp) {
        if (end < tp - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        html += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="goLogPage(' + tp + ')">' + tp + '</a></li>';
    }
    html += '<li class="page-item ' + (cp === tp ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0);" onclick="goLogPage(' + (cp+1) + ')"><i class="bx bx-chevron-right"></i></a></li>';
    $('#logPagination').html(html);
}

function goLogPage(p) { logPage = p; loadLogs(); }

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
</script>
