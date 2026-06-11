<?php
require '../lib/db.php';
require 'auth.php';

redirectIfLoggedIn();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$username || !$password) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } else {

        $failKey = 'login_fail_' . md5($username);
        $failCount = $_SESSION[$failKey]['count'] ?? 0;
        $failTime  = $_SESSION[$failKey]['time']  ?? 0;
        if ($failCount >= 5 && (time() - $failTime) < 300) {
            $remaining = 300 - (time() - $failTime);
            $error = "Quá nhiều lần thất bại. Vui lòng thử lại sau {$remaining} giây.";
        } else {

            if ((time() - $failTime) >= 300) {
                $_SESSION[$failKey] = ['count' => 0, 'time' => time()];
            }
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['status']) && (int)$user['status'] !== 1) {
                    $error = "Tài khoản của bạn chưa được kích hoạt hoặc đã bị khóa.";
                } else {
                    unset($_SESSION[$failKey]);

                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id'       => (int) $user['id'],
                        'name'     => $user['name'],
                        'username' => $user['username'],
                        'role_id'  => (int) $user['role_id'],
                    ];
                    header("Location: /admin/dashboard.php");
                    exit;
                }
            } else {

                $_SESSION[$failKey]['count'] = $failCount + 1;
                $_SESSION[$failKey]['time']  = time();
                $error = "Sai tài khoản hoặc mật khẩu";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
<div class="bg-white p-8 rounded-xl shadow-lg w-96">
    <!-- TITLE -->
    <h2 class="text-2xl font-bold mb-6 text-center">Admin Login</h2>
    <!-- ERROR -->
    <?php if($error): ?>

        <div class="bg-red-100 text-red-600 p-2 mb-4 rounded text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">

        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <!-- USERNAME -->
        <input name="username"
            placeholder="Username"
            autocomplete="username"
            class="w-full border p-3 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
        <!-- PASSWORD -->
        <input name="password" type="password"
            placeholder="Password"
            autocomplete="current-password"
            class="w-full border p-3 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
        <!-- BUTTON -->
        <button type="submit"
            class="w-full bg-blue-500 text-white py-3 rounded hover:bg-blue-600 transition">
            Đăng nhập
        </button>
    </form>
</div>
</body>
</html>