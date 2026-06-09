<?php
require '../lib/db.php';
require '../admin/auth.php';

checkLogin();

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
// ===== GET USERS =====
if ($method === 'GET') {
    //Chỉ admin mới được xem danh sách users
    if (!isAdmin()) {
        jsonError('Bạn không có quyền truy cập', 403);
    }
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.username, u.role_id, r.name as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.id ASC
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}
// ===== CREATE / UPDATE =====
if ($method === 'POST') {
    // Chỉ admin mới được tạo/sửa users
    if (!isAdmin()) {
        jsonError('Bạn không có quyền truy cập', 403);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        jsonError('Dữ liệu không hợp lệ');
    }
    // Validate bắt buộc trường name
    // $name    = trim($data['name'] ?? '');
    $username    = trim($data['username'] ?? '');
    $role_id = (int)($data['role_id'] ?? 0);
    if (empty($username)) {
        jsonError('Tên đăng nhập không được để trống');
    }
    // role_id phải là 1 hoặc 2 (hợp lệ)
    // if (!in_array($role_id, [1, 2], true)) {
    //     jsonError('Role không hợp lệ');
    // }
    // UPDATE
    if (!empty($data['id'])) {
        $id = (int)$data['id'];
        if (!empty($data['password'])) {

            $pass = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username=?, role_id=?, password=? 
                WHERE id=?
            ");
            $stmt->execute([$username, $role_id, $pass, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username=?, role_id=? 
                WHERE id=?
            ");
            $stmt->execute([$username, $role_id, $id]);
        }
    }
    // CREATE
    else {
        // Bắt buộc phải có password khi tạo mới
        if (empty($data['password'])) {
            jsonError('Mật khẩu không được để trống khi tạo tài khoản');
        }
        // Validate độ dài password tối thiểu
        if (strlen($data['password']) < 8) {
            jsonError('Mật khẩu phải có ít nhất 8 ký tự');
        }
        $pass = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users(username, password, role_id)
            VALUES(?, ?, ?)
        ");
        $stmt->execute([$username, $pass, $role_id]);
    }
    // Trả về JSON thành công thay vì chuỗi "ok"
    jsonSuccess('Thao tác thành công');
}
// ===== DELETE =====
if ($method === 'DELETE') {
    // Chỉ admin mới được xóa users
    if (!isAdmin()) {
        jsonError('Bạn không có quyền truy cập', 403);
    }
    // Cast sang int để tránh SQL injection dù đã dùng prepared statement
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonError('ID không hợp lệ');
    }
    // Không cho xóa chính mình
    if (getCurrentUserId() === $id) {
        jsonError('Không thể xóa chính bạn');
    }
    // Không cho xóa admin cuối cùng
    $count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=1")->fetchColumn();
    $user = $pdo->prepare("SELECT role_id FROM users WHERE id=?");
    $user->execute([$id]);
    $targetRole = (int)$user->fetchColumn();
    if ($targetRole === 1 && (int)$count <= 1) {
        jsonError('Phải có ít nhất 1 admin');
    }
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    // Trả về JSON thành công
    jsonSuccess('Xóa người dùng thành công');
}