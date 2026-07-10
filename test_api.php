<?php
chdir('admin/api');
require '../../lib/session.php';
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];
$_GET['filter'] = 'month';
require 'dashboard_chart.php';
