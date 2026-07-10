<?php
/**
 * Script tạo hash bcrypt cho password mới
 * CÁCH DÙNG: Chạy file này 1 lần để lấy hash, sau đó XÓA file này đi
 * 
 * Chạy trên trình duyệt: http://your-domain/reset_pass_tool.php
 * Hoặc chạy trên terminal: php reset_pass_tool.php
 */
// =============================================
// ⚠️  ĐỔI MẬT KHẨU MỚI Ở ĐÂY TRƯỚC KHI CHẠY
// =============================================
$newPassword = 'admin@123';   // <-- ĐỔI THÀNH MẬT KHẨU BẠN MUỐN
// Dùng PASSWORD_DEFAULT để PHP tự chọn thuật toán tốt nhất
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
echo "=== HASH MẬT KHẨU MỚI ===\n\n";
echo "Mật khẩu gốc : " . $newPassword . "\n";
echo "Hash bcrypt   : " . $hash . "\n\n";
echo "=== CHẠY CÂU SQL NÀY TRONG PHPMYADMIN ===\n\n";
echo "UPDATE users SET password = '" . $hash . "' WHERE username = 'admin';\n\n";
echo "=== XÁC NHẬN HASH HỢP LỆ ===\n";
echo password_verify($newPassword, $hash) ? "✅ Hash hợp lệ — có thể dùng được!\n" : "❌ Hash lỗi!\n";
echo "\n⚠️  Hãy XÓA file này sau khi dùng xong!\n";
