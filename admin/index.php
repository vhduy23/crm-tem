<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (isset($_SESSION['user'])) {
    header("Location: /admin/dashboard.php");
} else {
    header("Location: /admin/login.php");
}
exit;