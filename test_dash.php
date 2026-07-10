<?php
chdir('admin');
require '../lib/session.php';
$_SESSION['user'] = ['id' => 1, 'role_id' => 1, 'name' => 'Admin'];
require 'dashboard.php';
