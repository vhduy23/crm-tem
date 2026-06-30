<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();
unset($_SESSION['member']);
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}
header("Location: /");
exit;
