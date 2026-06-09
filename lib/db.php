<?php

$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'achau1_qltem';
$db_user = $_ENV['DB_USER'] ?? 'achau1_qltem';
$db_pass = $_ENV['DB_PASS'] ?? 'FP8SmO*Zv,Oa';

// $db_host = $_ENV['DB_HOST'] ?? 'localhost';
// $db_name = $_ENV['DB_NAME'] ?? 'crmtem';
// $db_user = $_ENV['DB_USER'] ?? 'root';
// $db_pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",

            PDO::ATTR_EMULATE_PREPARES   => false,

            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.']));
}