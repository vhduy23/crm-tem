<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\UserModel;

class AuthController extends Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function loginForm() {
        if (isset($_SESSION['member'])) {
            header("Location: /");
            exit;
        }

        $success_msg = '';
        if (isset($_SESSION['register_success'])) {
            $success_msg = $_SESSION['register_success'];
            unset($_SESSION['register_success']);
        }
        
        $error = $_SESSION['login_error'] ?? '';
        unset($_SESSION['login_error']);

        $this->view('Auth/login', ['error' => $error, 'success_msg' => $success_msg]);
    }

    public function login() {
        if (isset($_SESSION['member'])) {
            header("Location: /");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
            header("Location: /login");
            exit;
        }

        $failKey = 'login_fail_' . md5($username);
        $failCount = $_SESSION[$failKey]['count'] ?? 0;
        $failTime  = $_SESSION[$failKey]['time']  ?? 0;

        if ($failCount >= 5 && (time() - $failTime) < 300) {
            $remaining = 300 - (time() - $failTime);
            $_SESSION['login_error'] = "Tài khoản tạm khóa. Vui lòng thử lại sau {$remaining} giây.";
            header("Location: /login");
            exit;
        }

        if ((time() - $failTime) >= 300) {
            $_SESSION[$failKey] = ['count' => 0, 'time' => time()];
        }

        $userModel = new UserModel();
        $user = $userModel->login($username, $password);

        if ($user) {
            if ((int)$user['status'] !== 1) {
                $_SESSION['login_error'] = 'Tài khoản của bạn hiện đang chờ quản trị viên phê duyệt hoặc đã bị khóa.';
            } else {
                unset($_SESSION[$failKey]);
                session_regenerate_id(true);

                $_SESSION['member'] = [
                    'id'       => (int) $user['id'],
                    'name'     => $user['name'] ?: $user['username'],
                    'username' => $user['username'],
                    'role_id'  => (int) $user['role_id'],
                    'status'   => (int) $user['status'],
                ];

                header("Location: /");
                exit;
            }
        } else {
            $_SESSION[$failKey]['count'] = $failCount + 1;
            $_SESSION[$failKey]['time'] = time();
            $_SESSION['login_error'] = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }

        header("Location: /login");
        exit;
    }

    public function registerForm() {
        if (isset($_SESSION['member'])) {
            header("Location: /");
            exit;
        }
        
        $error = $_SESSION['register_error'] ?? '';
        unset($_SESSION['register_error']);

        $this->view('Auth/register', ['error' => $error]);
    }

    public function register() {
        if (isset($_SESSION['member'])) {
            header("Location: /");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $repass   = $_POST['repass'] ?? '';
        $name     = trim($_POST['name'] ?? '');

        if ($username === '' || $password === '' || $repass === '') {
            $_SESSION['register_error'] = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
            header("Location: /register");
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $_SESSION['register_error'] = 'Tên đăng nhập chỉ gồm chữ, số, _, dài 3-20 ký tự.';
            header("Location: /register");
            exit;
        }
        if (strlen($password) < 6) {
            $_SESSION['register_error'] = 'Mật khẩu phải từ 6 ký tự trở lên.';
            header("Location: /register");
            exit;
        }
        if ($password !== $repass) {
            $_SESSION['register_error'] = 'Mật khẩu nhập lại không khớp.';
            header("Location: /register");
            exit;
        }

        $userModel = new UserModel();
        if ($userModel->checkUsernameExists($username)) {
            $_SESSION['register_error'] = 'Tên đăng nhập này đã tồn tại, vui lòng chọn tên khác.';
            header("Location: /register");
            exit;
        }

        $data = [
            'username' => $username,
            'password' => $password,
            'name'     => $name,
            'role_id'  => 9, // Customer
            'status'   => 0  // Pending approval
        ];

        if ($userModel->register($data)) {
            $_SESSION['register_success'] = 'Đăng ký thành công! Vui lòng chờ quản trị viên phê duyệt tài khoản.';
            header("Location: /login");
            exit;
        } else {
            $_SESSION['register_error'] = 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại sau.';
            header("Location: /register");
            exit;
        }
    }

    public function resetpassForm() {
        if (isset($_SESSION['member'])) {
            header("Location: /");
            exit;
        }
        $error = $_SESSION['reset_error'] ?? '';
        $success = $_SESSION['reset_success'] ?? '';
        unset($_SESSION['reset_error'], $_SESSION['reset_success']);

        $this->view('Auth/resetpass', ['error' => $error, 'success' => $success]);
    }

    public function resetpass() {
        // Implement simple reset logic based on old resetpass.php
        $username = trim($_POST['username'] ?? '');
        $newpass  = $_POST['newpass'] ?? '';
        $repass   = $_POST['repass'] ?? '';

        if ($username === '' || $newpass === '' || $repass === '') {
            $_SESSION['reset_error'] = 'Vui lòng nhập đủ các trường.';
        } elseif ($newpass !== $repass) {
            $_SESSION['reset_error'] = 'Mật khẩu mới không khớp.';
        } else {
            $userModel = new UserModel();
            if (!$userModel->checkUsernameExists($username)) {
                $_SESSION['reset_error'] = 'Không tìm thấy tên đăng nhập này trong hệ thống.';
            } else {
                $userModel->updatePassword($username, $newpass);
                $_SESSION['reset_success'] = 'Cập nhật mật khẩu thành công. Vui lòng đăng nhập lại!';
            }
        }
        header("Location: /resetpass");
        exit;
    }

    public function logout() {
        session_destroy();
        header("Location: /login");
        exit;
    }
}
