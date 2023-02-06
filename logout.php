<?php
include_once("core/functions.php");
require_once("config/security.php");

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'loggedout') {
        session_destroy();
        echo "loggedout";
    }
} else {
    header('Location: ' . 'index.php');
}

?>
