<?php
declare(strict_types=1);
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/config/security.php';
requireAdmin();
include 'core/header.php';
include 'core/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- SIEM Config -->
            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-transfer me-2"></i>SIEM Forwarding Configuration</h5>
                        <div id="rsyslogStatus"></div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border mb-3" style="font-size: 0.8125rem;">
                            <strong>Syntax:</strong><br>
                            <code>local6.* @siem-server:514</code> — UDP forwarding<br>
                            <code>local6.* @@siem-server:514</code> — TCP forwarding (reliable)<br>
                            Lines starting with <code>#</code> are comments.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Forwarding Rules</label>
                            <textarea class="form-control font-monospace" id="forwardingRules" rows="10"
                                placeholder="# Example: Forward to QRadar via TCP&#10;# local6.*    @@qradar.infinitumit.com.tr:514&#10;&#10;# Example: Forward to Splunk&#10;# local6.*    @@splunk.infinitumit.com.tr:9997"
                                style="font-size: 0.8125rem; line-height: 1.6;"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="saveConfig()">
                                <i class="bx bx-save me-1"></i>Save & Apply
                            </button>
                            <button class="btn btn-outline-primary" onclick="sendTestLogs()">
                                <i class="bx bx-send me-1"></i>Send Test Logs
                            </button>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Facility</small>
                                <strong id="cfgFacility">-</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Format</small>
                                <strong>CEF</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Log File</small>
                                <strong id="cfgLogFile" style="font-size: 0.75rem;">-</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Log Viewer -->
            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-terminal me-2"></i>Recent Syslog Entries</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm" id="logLines" style="width: 80px;">
                                <option value="20">20</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="syslogViewer" style="height: 500px; overflow-y: auto; font-family: monospace; font-size: 0.75rem; padding: 1rem; background: #1a1a2e; color: #16c784;">
                            <div class="text-center py-4" style="color: #555;">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
<script>
$(document).ready(function() {
    loadConfig();
    refreshLogs();
});

function loadConfig() {
    ajaxPost({ action: 'siem_get_config' }).done(function(data) {
        $('#forwardingRules').val(data.forwarding_rules);
        $('#cfgFacility').text(data.facility);
        $('#cfgLogFile').text(data.log_file);

        var statusHtml;
        if (data.rsyslog_status === 'active') {
            statusHtml = '<span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i>rsyslog active</span>';
        } else {
            statusHtml = '<span class="badge bg-label-danger"><i class="bx bx-x-circle me-1"></i>rsyslog inactive</span>';
        }
        if (data.has_active_rules) {
            statusHtml += ' <span class="badge bg-label-info ms-1">Forwarding ON</span>';
        }
        $('#rsyslogStatus').html(statusHtml);
    });
}

function saveConfig() {
    var rules = $('#forwardingRules').val();
    confirmAction('Save SIEM Config', 'This will update rsyslog configuration and restart the service. Continue?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'siem_save_config', forwarding_rules: rules })
                .done(function(data) {
                    showSuccess(data.message);
                    loadConfig();
                    refreshLogs();
                })
                .fail(function(xhr) {
                    var msg = 'Failed to save.';
                    try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function sendTestLogs() {
    confirmAction('Send Test Logs', 'This will send 4 test CEF events to syslog. Continue?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'siem_test' })
                .done(function(data) {
                    showSuccess(data.message + ' ' + data.detail);
                    setTimeout(refreshLogs, 1000);
                })
                .fail(function(xhr) {
                    var msg = 'Failed.';
                    try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function refreshLogs() {
    var lines = $('#logLines').val();
    ajaxPost({ action: 'siem_recent_logs', lines: lines }).done(function(data) {
        var viewer = $('#syslogViewer');
        if (!data.logs || data.logs.length === 0) {
            viewer.html('<div class="text-center py-4" style="color: #555;">No log entries yet.</div>');
            return;
        }
        var html = '';
        data.logs.forEach(function(line) {
            var colored = escapeHtml(line)
                .replace(/(CEF:0)/g, '<span style="color: #ff6b6b;">$1</span>')
                .replace(/(InfinitumIT\|UnboundDNSPanel)/g, '<span style="color: #ffd93d;">$1</span>')
                .replace(/(src=[^\s]+)/g, '<span style="color: #6bcfff;">$1</span>')
                .replace(/(suser=[^\s]+)/g, '<span style="color: #c084fc;">$1</span>')
                .replace(/(msg=[^\s].*?(?=\sdhost=|$))/g, '<span style="color: #fff;">$1</span>')
                .replace(/(Login Failed|Failed login)/g, '<span style="color: #ff4444; font-weight: bold;">$1</span>')
                .replace(/(SIEM Test:)/g, '<span style="color: #ffd93d; font-weight: bold;">$1</span>');
            html += '<div style="border-bottom: 1px solid #2a2a4a; padding: 4px 0; word-break: break-all;">' + colored + '</div>';
        });
        viewer.html(html);
        viewer.scrollTop(0);
    });
}

$('#logLines').on('change', refreshLogs);

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
</script>
