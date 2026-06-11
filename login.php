<?php
require 'lib/db.php';
session_start();

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['member'])) {
    header("Location: /");
    exit;
}

$error = '';
$success_msg = '';

if (isset($_SESSION['register_success'])) {
    $success_msg = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['status'] !== 1) {
                $error = 'Tài khoản của bạn hiện đang chờ quản trị viên phê duyệt hoặc đã bị khóa.';
            } else {
                // Đăng nhập thành công làm thành viên frontend
                $_SESSION['member'] = [
                    'id'       => (int) $user['id'],
                    'name'     => $user['name'] ?: $user['username'],
                    'username' => $user['username'],
                    'role_id'  => (int) $user['role_id'],
                ];

                header("Location: /");
                exit;
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập thành viên — AChau Group</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Lexend', sans-serif;
        }
    </style>
</head>
<body class="bg-[#F7F8FB] min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Nền trang trí hạt gradient -->
    <div class="absolute top-[-20%] left-[-20%] w-[60vw] h-[60vw] rounded-full bg-blue-100/40 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-20%] right-[-20%] w-[60vw] h-[60vw] rounded-full bg-indigo-100/50 blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md bg-white rounded-3xl border border-gray-100 shadow-[0_12px_40px_rgba(11,37,88,0.08)] overflow-hidden relative z-10">
        <!-- Banner đầu trang -->
        <div class="bg-gradient-to-r from-blue-900 to-[#0B2558] p-8 text-center relative">
            <a href="/" class="inline-block mb-3">
                <img src="/uploads/260515.webp" alt="A Chau Group Logo" class="h-10 mx-auto filter brightness-[1.05]">
            </a>
            <h2 class="text-white text-lg font-bold">Đăng nhập thành viên</h2>
            <p class="text-blue-200/80 text-xs mt-1">Truy cập bộ sưu tập thiết kế và tính năng in ấn</p>
        </div>

        <!-- Nội dung Form -->
        <div class="p-8">
            <!-- Thông báo thành công nếu có -->
            <?php if ($success_msg): ?>
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl text-sm mb-5 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-emerald-500"></i>
                    <span><?= htmlspecialchars($success_msg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Thông báo lỗi nếu có -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-2xl text-sm mb-5 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-red-500"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên đăng nhập</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-regular fa-user text-sm"></i>
                        </span>
                        <input type="text" name="username" required
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            class="w-full border border-gray-200 rounded-2xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all placeholder-gray-400"
                            placeholder="Nhập tên đăng nhập của bạn">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full border border-gray-200 rounded-2xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all placeholder-gray-400"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-2xl text-sm shadow-md shadow-blue-600/15 transition-all active:scale-[0.98]">
                    Đăng nhập
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500">
                    Chưa có tài khoản thành viên? 
                    <a href="/register.php" class="text-blue-600 hover:underline font-semibold">Đăng ký ngay</a>
                </p>
                <a href="/" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-600 transition-colors mt-4">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>
