<?php
require_once __DIR__ . '/../lib/session.php';

// Chỉ xóa session của admin, giữ lại session frontend ('member')
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// chống cache trang cũ
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// redirect về login
header("Location: /admin/login.php");
exit;