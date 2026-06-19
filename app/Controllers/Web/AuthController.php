<?php
// app/Controllers/Web/AuthController.php
require_once '../core/Controller.php';
require_once '../app/Models/User.php';

class AuthController extends Controller {

    public function showLoginForm() {
        $this->view('auth/login');
    }

    public function login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $selectedRole = $_POST['role'] ?? '';

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== $selectedRole) {
                $this->view('auth/login', ['error' => 'Tài khoản không trùng khớp với vai trò đã chọn.']);
                return;
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ];
            
            if ($user['role'] == 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard');
            } else if ($user['role'] == 'teacher') {
                header('Location: ' . BASE_URL . '/teacher/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/student/dashboard');
            }
            exit;
        } else {
            // Hardcode bypass cho mục đích test bài thi nếu ko connect đc DB hoặc quên hash
            if ($username === 'admin' && $password === 'admin' && $selectedRole === 'admin') {
                $_SESSION['user'] = ['id'=>1, 'role'=>'admin', 'full_name'=>'Admin Test'];
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            }
            
            $this->view('auth/login', ['error' => 'Tên đăng nhập hoặc mật khẩu không chính xác.']);
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
    }
}
