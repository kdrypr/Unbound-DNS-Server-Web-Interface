<body>
<script>
window.onload = function () {
    var currentPage = window.location.pathname.split("/").slice(-1)[0];
    document.querySelectorAll('.menu-inner .menu-item a').forEach(function (link) {
        if (link.getAttribute('href') === currentPage) {
            link.parentElement.classList.add('active');
        }
    });
};
</script>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
                <a href="dns.php" class="app-brand-link">
                    <span class="app-brand-text demo menu-text fw-bolder ms-2">Unbound DNS</span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                    <i class="bx bx-chevron-left bx-sm align-middle"></i>
                </a>
            </div>
            <div class="menu-inner-shadow"></div>
            <ul class="menu-inner py-1">
                <li class="menu-header small text-uppercase"><span class="menu-header-text">DNS</span></li>
                <li class="menu-item">
                    <a href="dns.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-server"></i>
                        <div>DNS Records</div>
                    </a>
                </li>
                <li class="menu-header small text-uppercase"><span class="menu-header-text">System</span></li>
                <?php if (isAdmin()): ?>
                <li class="menu-item">
                    <a href="users.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user"></i>
                        <div>Users</div>
                    </a>
                </li>
                <?php endif; ?>
                <li class="menu-item">
                    <a href="logs.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-list-ul"></i>
                        <div>Audit Logs</div>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="menu-item">
                    <a href="siem.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-transfer"></i>
                        <div>SIEM Config</div>
                    </a>
                </li>
                <?php endif; ?>
                <li class="menu-item">
                    <a href="profile.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-cog"></i>
                        <div>Profile</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link" onclick="doLogout()">
                        <i class="menu-icon tf-icons bx bx-log-out"></i>
                        <div>Logout</div>
                    </a>
                </li>
            </ul>
        </aside>
        <div class="layout-page">
            <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                    <div class="d-flex align-items-center">
                        <div class="nav-item d-flex align-items-center me-3">
                            <span class="text-muted small">Signed in as <strong><?= htmlspecialchars(currentUsername()) ?></strong>
                            <?php if (isAdmin()): ?><span class="badge bg-label-primary ms-1">Admin</span><?php endif; ?></span>
                        </div>
                    </div>
                    <ul class="navbar-nav flex-row align-items-center ms-auto">
                        <li class="nav-item me-2">
                            <img src="infinitumit-logo-blackfont.png" alt="InfinitumIT" style="height: 36px; width: auto;">
                        </li>
                    </ul>
                </div>
            </nav>
