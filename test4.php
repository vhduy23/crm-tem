<?php
require 'lib/db.php';
require 'lib/session.php';
$_SESSION['user']['role_id'] = 0;
$_GET['filter'] = 'month';
require 'admin/api/dashboard_chart.php';
