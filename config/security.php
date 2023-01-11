<?php
require_once("login.php");
// not logged in
if (!isset($_SESSION['sessiondeger'])) {
    echo "<script type=\"text/javascript\">window.alert('You must log in for view this page.');
        window.location.href = 'index.php';</script>";
    exit();
}
$timeout = 60 * 5; // In seconds, i.e. 1 minute.
$fingerprint = hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'], hash('sha256', $_SERVER['REMOTE_ADDR'], true));
if ((isset($_SESSION['last_active']) && $_SESSION['last_active'] < (time() - $timeout))
    || (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] != $fingerprint)
    || isset($_GET['logout'])
) {

    setcookie(session_name(), "", time() - 3600, "", "", true, true);
    session_destroy();
}
session_regenerate_id();
$_SESSION['last_active'] = time();
$_SESSION['fingerprint'] = $fingerprint;
?>
