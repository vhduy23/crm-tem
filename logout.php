<?php
session_start();
unset($_SESSION['member']);
header("Location: /");
exit;
