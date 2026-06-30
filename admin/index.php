<?php
require_once __DIR__ . '/../lib/session.php';

if (isset($_SESSION['user'])) {
    header("Location: /admin/dashboard.php");
} else {
    header("Location: /admin/login.php");
}
exit;