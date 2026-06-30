<?php
require 'lib/db.php';
try {
    $pdo->exec('ALTER TABLE products ADD COLUMN approval_status TINYINT DEFAULT 0 AFTER status');
    echo 'Column added.';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'Column already exists.';
    } else {
        echo $e->getMessage();
    }
}
