// ─── DNS Records Management ────────────────────────────────────────────────────

var currentPage = 1;
var searchTimer = null;

$(document).ready(function() {
    loadDNSRecords();

    $('#dnsSearch').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { currentPage = 1; loadDNSRecords(); }, 400);
    });

    $('#typeFilter, #perPage').on('change', function() {
        currentPage = 1;
        loadDNSRecords();
    });

    // Enter key on query
    $('#queryDomain').on('keypress', function(e) {
        if (e.which === 13) dnsQuery();
    });
});

function loadDNSRecords() {
    var search = $('#dnsSearch').val();
    var typeFilter = $('#typeFilter').val();
    var perPage = $('#perPage').val();

    ajaxPost({
        action: 'dns_list',
        page: currentPage,
        per_page: perPage,
        search: search,
        type_filter: typeFilter
    }).done(function(data) {
        renderTable(data);
        renderPagination(data);
        updateStatus(data.has_changes);
    }).fail(function() {
        $('#dnsTableBody').html('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load records.</td></tr>');
    });
}

function renderTable(data) {
    var tbody = '';
    if (data.data.length === 0) {
        tbody = '<tr><td colspan="5" class="text-center text-muted py-4">No records found.</td></tr>';
    } else {
        var startNum = (data.page - 1) * data.perPage;
        data.data.forEach(function(r, i) {
            var typeBadge = getTypeBadge(r.type);
            tbody += '<tr>' +
                '<td class="text-muted">' + (startNum + i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(r.fqdn) + '</strong></td>' +
                '<td><code>' + escapeHtml(r.value) + '</code></td>' +
                '<td>' + typeBadge + '</td>' +
                '<td>' +
                    '<div class="dropdown">' +
                        '<button type="button" class="btn btn-sm btn-icon p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
                            '<i class="bx bx-dots-vertical-rounded"></i>' +
                        '</button>' +
                        '<div class="dropdown-menu">' +
                            '<a class="dropdown-item" href="javascript:void(0);" onclick="openEdit(\'' + escapeAttr(r.fqdn) + '\',\'' + escapeAttr(r.value) + '\',\'' + escapeAttr(r.type) + '\')">' +
                                '<i class="bx bx-edit-alt me-1"></i> Edit</a>' +
                            '<a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteRecord(\'' + escapeAttr(r.fqdn) + '\',\'' + escapeAttr(r.value) + '\',\'' + escapeAttr(r.type) + '\')">' +
                                '<i class="bx bx-trash me-1"></i> Delete</a>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '</tr>';
        });
    }
    $('#dnsTableBody').html(tbody);
    $('#recordInfo').text('Showing ' + data.data.length + ' of ' + data.total + ' records (Page ' + data.page + '/' + data.totalPages + ')');
}

function renderPagination(data) {
    var html = '';
    var tp = data.totalPages;
    var cp = data.page;

    if (tp <= 1) { $('#pagination').html(''); return; }

    html += '<li class="page-item ' + (cp === 1 ? 'disabled' : '') + '">' +
            '<a class="page-link" href="javascript:void(0);" onclick="goPage(' + (cp - 1) + ')"><i class="bx bx-chevron-left"></i></a></li>';

    var start = Math.max(1, cp - 2);
    var end = Math.min(tp, cp + 2);

    if (start > 1) {
        html += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="goPage(1)">1</a></li>';
        if (start > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for (var p = start; p <= end; p++) {
        html += '<li class="page-item ' + (p === cp ? 'active' : '') + '">' +
                '<a class="page-link" href="javascript:void(0);" onclick="goPage(' + p + ')">' + p + '</a></li>';
    }

    if (end < tp) {
        if (end < tp - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        html += '<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="goPage(' + tp + ')">' + tp + '</a></li>';
    }

    html += '<li class="page-item ' + (cp === tp ? 'disabled' : '') + '">' +
            '<a class="page-link" href="javascript:void(0);" onclick="goPage(' + (cp + 1) + ')"><i class="bx bx-chevron-right"></i></a></li>';

    $('#pagination').html(html);
}

function goPage(page) {
    currentPage = page;
    loadDNSRecords();
}

function updateStatus(hasChanges) {
    var btn = $('#applyBtn');
    var indicator = $('#statusIndicator');
    if (hasChanges) {
        btn.prop('disabled', false);
        indicator.html('<span class="dot orange"></span><small class="text-warning">Unapplied changes</small>');
    } else {
        btn.prop('disabled', true);
        indicator.html('<span class="dot green"></span><small class="text-muted">All changes applied</small>');
    }
}

function getTypeBadge(type) {
    var colors = { 'A': 'primary', 'AAAA': 'info', 'MX': 'warning', 'CNAME': 'success', 'TXT': 'secondary' };
    return '<span class="badge bg-label-' + (colors[type] || 'dark') + '">' + escapeHtml(type) + '</span>';
}

// ─── CRUD Operations ───────────────────────────────────────────────────────────

function addRecord() {
    var fqdn = $('#addFqdn').val().trim();
    var value = $('#addValue').val().trim();
    var type = $('#addType').val();

    if (!fqdn || !value) {
        showError('Please fill in all fields.');
        return;
    }

    confirmAction('Add DNS Record', 'Add ' + type + ' record: ' + fqdn + ' → ' + value + '?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'dns_add', fqdn: fqdn, value: value, type: type })
                .done(function(data) {
                    showSuccess(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
                    $('#addFqdn, #addValue').val('');
                    loadDNSRecords();
                })
                .fail(function(xhr) {
                    var msg = 'Failed to add record.';
                    try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function openEdit(fqdn, value, type) {
    $('#editOldFqdn').val(fqdn);
    $('#editOldValue').val(value);
    $('#editOldType').val(type);
    $('#editFqdn').val(fqdn);
    $('#editValue').val(value);
    $('#editType').val(type);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function editRecord() {
    var fqdn = $('#editFqdn').val().trim();
    var value = $('#editValue').val().trim();
    var type = $('#editType').val();
    var oldFqdn = $('#editOldFqdn').val();
    var oldValue = $('#editOldValue').val();
    var oldType = $('#editOldType').val();

    if (!fqdn || !value) {
        showError('Please fill in all fields.');
        return;
    }

    confirmAction('Edit DNS Record', 'Update this record?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({
                action: 'dns_edit',
                fqdn: fqdn, value: value, type: type,
                old_fqdn: oldFqdn, old_value: oldValue, old_type: oldType
            })
            .done(function(data) {
                showSuccess(data.message);
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                loadDNSRecords();
            })
            .fail(function(xhr) {
                var msg = 'Failed to update record.';
                try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                showError(msg);
            });
        }
    });
}

function deleteRecord(fqdn, value, type) {
    confirmAction('Delete DNS Record', 'Delete ' + type + ' record: ' + fqdn + ' → ' + value + '?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'dns_delete', fqdn: fqdn, value: value, type: type })
                .done(function(data) {
                    showSuccess(data.message);
                    loadDNSRecords();
                })
                .fail(function(xhr) {
                    var msg = 'Failed to delete record.';
                    try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function applyRules() {
    confirmAction('Apply Rules', 'This will reload the Unbound DNS service. Are you sure?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'dns_restart' })
                .done(function(data) {
                    showSuccess(data.message);
                    loadDNSRecords();
                })
                .fail(function(xhr) {
                    var msg = 'Failed to reload service.';
                    try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function dnsQuery() {
    var domain = $('#queryDomain').val().trim();
    if (!domain) { showError('Please enter a domain name.'); return; }

    ajaxPost({ action: 'dns_query', domain: domain })
        .done(function(data) {
            $('#queryResult').removeClass('d-none');
            $('#queryOutput').text(data.result || 'No result.');
        })
        .fail(function(xhr) {
            var msg = 'Query failed.';
            try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
            showError(msg);
        });
}

// ─── Helpers ───────────────────────────────────────────────────────────────────

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function escapeAttr(str) {
    return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}
