<?php
session_start();

// unset toàn bộ session
$_SESSION = [];

// destroy session
session_destroy();

// xóa cookie session (quan trọng)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// chống cache trang cũ
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// redirect về login
header("Location: /admin/login.php");
exit;