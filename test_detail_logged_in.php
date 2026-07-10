<?php
require 'lib/session.php';
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['url'] = 'thiet-ke/m0991';
require 'index.php';
