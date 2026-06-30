<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 28800);
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'domain' => $cookieParams['domain'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
