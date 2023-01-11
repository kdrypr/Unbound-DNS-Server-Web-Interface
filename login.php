<?php
include_once("core/functions.php");
ini_set('session.cookie_samesite', 'Lax');
ini_set("session.cookie_httponly", True);
@session_start();
if (isset($_POST["username"]) && isset($_POST["password"])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $row = login($username);
    $user = $row[1];
    $pass = $row[2];

    if (!empty($username) && !empty($password)) {
        if (!empty($user) && !empty($pass)) {
            if (password_verify($password, $pass)) {
                $_SESSION['sessiondeger'] = $user;
                echo 'login';
            } else {
                echo 'Wrong Credentials';
            }
        } else {
            return 'Wrong Credentials';
        }
    } else {
        return 'Fill all fields!';
    }
}
?>
