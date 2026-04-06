<?php
declare(strict_types=1);
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/config/security.php';
include 'core/header.php';
include 'core/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="oldPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (min 8 characters)</label>
                            <input type="password" class="form-control" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                        <button class="btn btn-primary" onclick="doChangePassword()">
                            <i class="bx bx-save me-1"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-user me-2"></i>Account Info</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><td class="text-muted" style="width:120px;">Username</td><td><strong><?= htmlspecialchars(currentUsername()) ?></strong></td></tr>
                            <tr><td class="text-muted">Role</td><td><span class="badge bg-label-<?= isAdmin() ? 'primary' : 'info' ?>"><?= htmlspecialchars(currentUserRole()) ?></span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
<script>
function doChangePassword() {
    var oldPw = $('#oldPassword').val();
    var newPw = $('#newPassword').val();
    var confirmPw = $('#confirmPassword').val();

    if (!oldPw || !newPw || !confirmPw) { showError('Please fill in all fields.'); return; }
    if (newPw !== confirmPw) { showError('New passwords do not match.'); return; }
    if (newPw.length < 8) { showError('Password must be at least 8 characters.'); return; }

    confirmAction('Change Password', 'Are you sure you want to change your password?').then(function(result) {
        if (result.isConfirmed) {
            ajaxPost({ action: 'change_password', old_password: oldPw, new_password: newPw })
                .done(function(data) {
                    showSuccess(data.message);
                    $('#oldPassword, #newPassword, #confirmPassword').val('');
                })
                .fail(function(xhr) {
                    var msg = 'Failed.'; try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
                    showError(msg);
                });
        }
    });
}
</script>
