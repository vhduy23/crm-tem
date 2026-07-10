<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-[#0B2558] mb-6">ĐỔI MẬT KHẨU</h2>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="/resetpass" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập</label>
                <input type="text" name="username" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#1a52b5] focus:border-[#1a52b5] py-2 px-3 border" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                <input type="password" name="newpass" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#1a52b5] focus:border-[#1a52b5] py-2 px-3 border" required minlength="6">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhập lại mật khẩu</label>
                <input type="password" name="repass" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#1a52b5] focus:border-[#1a52b5] py-2 px-3 border" required minlength="6">
            </div>
            <button type="submit" class="w-full bg-[#1a52b5] hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors">
                Đổi mật khẩu
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="/login" class="text-[#1a52b5] hover:underline font-medium">Quay lại Đăng nhập</a>
        </div>
    </div>
</body>
</html>
