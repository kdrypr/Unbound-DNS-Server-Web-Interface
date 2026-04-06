<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"/>
    <title>INFINITUMIT Unbound DNS Server</title>
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
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/js/config.js"></script>
    <style>
        .dns-toolbar { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        .dns-toolbar .form-control, .dns-toolbar .form-select { max-width: 220px; }
        .pagination { margin-bottom: 0; }
        .pagination .page-link { min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; }
        .pagination .page-link i { font-size: 1rem; }
        .table th { font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .card { border: none; box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12); }
        .status-indicator { display: inline-flex; align-items: center; gap: 6px; }
        .status-indicator .dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-indicator .dot.green { background: #71dd37; }
        .status-indicator .dot.orange { background: #ffab00; }
        .swal2-popup { font-size: 0.9rem !important; }
        .swal2-container { z-index: 99999 !important; }
    </style>
</head>
