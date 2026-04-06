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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bx bx-user me-2"></i>User Management</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bx bx-plus me-1"></i>Add User
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="newUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="newEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (min 8 chars)</label>
                        <input type="password" class="form-control" id="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="newRole">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createUser()">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave empty to keep current)</label>
                        <input type="password" class="form-control" id="editPassword">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="editRole">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="editActive">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
<script>
$(document).ready(function() { loadUsers(); });

function loadUsers() {
    ajaxPost({ action: 'user_list' }).done(function(data) {
        var html = '';
        data.users.forEach(function(u) {
            var statusBadge = u.is_active == 1
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Disabled</span>';
            var roleBadge = u.role === 'admin'
                ? '<span class="badge bg-label-primary">Admin</span>'
                : '<span class="badge bg-label-info">User</span>';
            html += '<tr>' +
                '<td>' + u.id + '</td>' +
                '<td><strong>' + escapeHtml(u.username) + '</strong></td>' +
                '<td>' + escapeHtml(u.email || '-') + '</td>' +
                '<td>' + roleBadge + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td><small class="text-muted">' + (u.created_at || '-') + '</small></td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-icon btn-outline-primary me-1" onclick="openEditUser(' + u.id + ',\'' + escapeAttr(u.username) + '\',\'' + escapeAttr(u.email || '') + '\',\'' + u.role + '\',' + u.is_active + ')" title="Edit"><i class="bx bx-edit"></i></button>' +
                    '<button class="btn btn-sm btn-icon btn-outline-danger" onclick="removeUser(' + u.id + ',\'' + escapeAttr(u.username) + '\')" title="Delete"><i class="bx bx-trash"></i></button>' +
                '</td></tr>';
        });
        if (!html) html = '<tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>';
        $('#usersTableBody').html(html);
    });
}

function createUser() {
    var username = $('#newUsername').val().trim();
    var password = $('#newPassword').val();
    var email = $('#newEmail').val().trim();
    var role = $('#newRole').val();

    if (!username || !password) { showError('Username and password are required.'); return; }
    if (password.length < 8) { showError('Password must be at least 8 characters.'); return; }

    confirmAction('Create User', 'Create user "' + username + '"?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'user_create', username: username, password: password, email: email, role: role })
                .done(function(data) {
                    showSuccess(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                    $('#newUsername, #newPassword, #newEmail').val('');
                    loadUsers();
                })
                .fail(function(xhr) {
                    var msg = 'Failed.'; try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}

function openEditUser(id, username, email, role, active) {
    $('#editUserId').val(id);
    $('#editUsername').val(username);
    $('#editEmail').val(email);
    $('#editPassword').val('');
    $('#editRole').val(role);
    $('#editActive').val(active);
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function updateUser() {
    var data = {
        action: 'user_update',
        user_id: $('#editUserId').val(),
        username: $('#editUsername').val().trim(),
        email: $('#editEmail').val().trim(),
        role: $('#editRole').val(),
        is_active: $('#editActive').val(),
        password: $('#editPassword').val()
    };

    if (!data.username) { showError('Username is required.'); return; }

    confirmAction('Update User', 'Save changes?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost(data).done(function(res) {
                showSuccess(res.message);
                bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                loadUsers();
            }).fail(function(xhr) {
                var msg = 'Failed.'; try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                showError(msg);
            });
        }
    });
}

function removeUser(id, username) {
    confirmAction('Delete User', 'Are you sure you want to delete "' + username + '"? This cannot be undone.').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'user_delete', user_id: id }).done(function(data) {
                showSuccess(data.message);
                loadUsers();
            }).fail(function(xhr) {
                var msg = 'Failed.'; try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                showError(msg);
            });
        }
    });
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
function escapeAttr(str) {
    return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>
