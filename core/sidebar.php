<body>
<script>
    window.onload = function () {
        var currentPage = window.location.pathname.split("/").slice(-1)[0];
        var menuLinks = document.querySelectorAll('.menu-inner .menu-item a');

        menuLinks.forEach(function (menuLink) {
            if (menuLink.getAttribute('href') === currentPage) {
                menuLink.parentElement.classList.add('active');
            }
        });
    };
</script>
<style>
    .logout:hover {
        cursor: pointer;
    }
</style>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
                <a href="dns.php" class="app-brand-link">
              <span class="app-brand-logo demo">
              </span>
                    <span class="app-brand-text demo menu-text fw-bolder ms-2">unbound</span>
                </a>

                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                    <i class="bx bx-chevron-left bx-sm align-middle"></i>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <!-- Dashboard -->
                <li class="menu-item">
                    <a href="dns.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-home-circle"></i>
                        <div data-i18n="Analytics">Dashboard</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="profile.php" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-face"></i>
                        <div data-i18n="Analytics">Profile</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link logout" onclick="logout()">
                        <i class="menu-icon tf-icons bx bx-log-out-circle"></i>
                        <div data-i18n="Analytics">Logout</div>
                    </a>
                </li>
            </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->

            <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                 id="layout-navbar">

                <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                    <ul class="navbar-nav flex-row align-items-center mr-auto">
                        <!-- User -->
                        <li>
                            <button style="color:black;"
                                    class="btn btn-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#newDNSRecord"
                            ">Add New DNS Rule</button>
                        </li>
                    </ul>
                    <ul class="navbar-nav flex-row align-items-center mx-auto">
                        <!-- User -->
                        <li>
                            <img src="beamlogo.png">
                        </li>
                    </ul>

                    <ul class="navbar-nav flex-row align-items-center ms-auto">
                        <!-- User -->
                        <li>
                            <button style="color:black;" <?php checkHostEntriesHash(); ?>
                                    class="btn btn-danger btn-buy-now"
                                    onclick="restartDNSService()">Apply Rules
                            </button>
                        </li>
                        <!--/ User -->
                    </ul>
                </div>
            </nav>

            <!-- / Navbar -->
