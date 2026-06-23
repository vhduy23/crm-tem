<?php
session_start();
unset($_SESSION['member']);
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}
header("Location: /");
exit;
