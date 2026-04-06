<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dns.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"/>
    <title>INFINITUMIT DNS Server - Login</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css"/>
    <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css"/>
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css"/>
    <link rel="stylesheet" href="assets/css/demo.css"/>
    <link rel="stylesheet" href="assets/css/infinitum.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css"/>
    <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css"/>
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/js/config.js"></script>
</head>
<body>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-4">
                        <span class="app-brand-text demo text-body fw-bolder" style="font-size: 1.25rem;">UNBOUND DNS SERVER</span>
                    </div>
                    <p class="mb-4 text-center">Please sign in to your account.</p>

                    <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning alert-dismissible mb-3" role="alert">
                        Your session has expired. Please sign in again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form id="loginForm" class="mb-3">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" autofocus required/>
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required/>
                                <span class="input-group-text cursor-pointer" onclick="togglePassword()"><i class="bx bx-hide" id="toggleIcon"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit" id="loginBtn">
                                <span id="loginText">Sign in</span>
                                <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/libs/jquery/jquery.js"></script>
<script src="assets/vendor/libs/popper/popper.js"></script>
<script src="assets/vendor/js/bootstrap.js"></script>
<script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="assets/vendor/js/menu.js"></script>
<script src="assets/js/main.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bx-hide', 'bx-show');
    } else {
        input.type = 'password';
        icon.classList.replace('bx-show', 'bx-hide');
    }
}

$('#loginForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#loginBtn');
    btn.prop('disabled', true);
    $('#loginText').text('Signing in...');
    $('#loginSpinner').removeClass('d-none');

    $.ajax({
        type: 'POST',
        url: 'login.php',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                window.location = 'dns.php';
            }
        },
        error: function(xhr) {
            var msg = 'Login failed.';
            try { msg = JSON.parse(xhr.responseText).error; } catch(e) {}
            Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: '#1B8A4E' });
            btn.prop('disabled', false);
            $('#loginText').text('Sign in');
            $('#loginSpinner').addClass('d-none');
        }
    });
});
</script>
</body>
</html>
