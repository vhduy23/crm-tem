<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: /admin/dashboard.php");
} else {
    header("Location: /admin/login.php");
}
exit;