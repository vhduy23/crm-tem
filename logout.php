<?php
require_once __DIR__ . '/lib/session.php';

// unset toàn bộ session
$_SESSION = [];

// destroy session
session_destroy();

// xóa cookie session
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

header("Location: /");
exit;
