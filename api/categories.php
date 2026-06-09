<?php
require '../lib/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode($pdo->query("SELECT * FROM categories")->fetchAll());
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$data['name'], $data['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories(name) VALUES(?)");
        $stmt->execute([$data['name']]);
    }

    echo "ok";
}

if ($method === 'DELETE') {
    $id = $_GET['id'];
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    echo "deleted";
}