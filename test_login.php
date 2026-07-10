<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['url'] = 'login';
$_POST['username'] = 'superadmin';
$_POST['password'] = '123456';
require 'index.php';
