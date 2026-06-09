<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user'])) {
        header("Location: /admin/login.php");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1;
}
function getCurrentUserId() {
    if (isset($_SESSION['user'])) {
        return (int) $_SESSION['user']['id'];
    }
    return null;
}
function redirectIfLoggedIn() {
    if (isset($_SESSION['user'])) {
        header("Location: /admin/dashboard.php");
        exit;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function jsonError(string $message, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function jsonSuccess(mixed $data = null): never {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}