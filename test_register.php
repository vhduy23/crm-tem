<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['url'] = 'register';
$_POST['username'] = 'testuser123';
$_POST['password'] = '123456';
$_POST['repass'] = '123456';
$_POST['name'] = 'Test User';
require 'index.php';
