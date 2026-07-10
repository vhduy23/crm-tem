<?php
namespace App\Models;

use PDO;

class UserModel {
    protected $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function checkUsernameExists($username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return (bool)$stmt->fetchColumn();
    }

    public function register($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, name, phone, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $data['username'],
            $hashed,
            $data['name'],
            $data['phone'] ?? '',
            $data['role_id'],
            $data['status']
        ]);
    }

    public function updatePassword($username, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        return $stmt->execute([$hashed, $username]);
    }
}
