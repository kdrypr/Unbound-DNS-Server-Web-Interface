            <footer class="content-footer footer bg-footer-theme">
                <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                    <div class="mb-2 mb-md-0">
                        &copy; <script>document.write(new Date().getFullYear());</script> INFINITUMIT
                    </div>
                </div>
            </footer>
            <div class="content-backdrop fade"></div>
        </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
</div>

<script src="assets/vendor/libs/jquery/jquery.js"></script>
<script src="assets/vendor/libs/popper/popper.js"></script>
<script src="assets/vendor/js/bootstrap.js"></script>
<script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="assets/vendor/js/menu.js"></script>
<script src="assets/js/main.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = '<?= htmlspecialchars(generateCsrfToken()) ?>';

const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
});

function showSuccess(msg) {
    Toast.fire({ icon: 'success', title: msg });
}

function showError(msg) {
    Toast.fire({ icon: 'error', title: msg });
}

function ajaxPost(data) {
    data.csrf_token = CSRF_TOKEN;
    return $.ajax({ type: 'POST', url: 'controller.php', data: data, dataType: 'json' });
}

function confirmAction(title, text) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1B8A4E',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    });
}

function doLogout() {
    confirmAction('Logout', 'Are you sure you want to logout?').then(function(result) {
        if (result.isConfirmed) {
            $.post('logout.php', { csrf_token: CSRF_TOKEN }, function() {
                window.location = 'index.php';
            }, 'json');
        }
    });
}
</script>
</body>
</html>
