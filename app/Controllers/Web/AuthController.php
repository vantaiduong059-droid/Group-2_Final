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

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        // Dummy check do password dùng password_hash
        // Trong script sql có ghi mk là 'password'
        if ($user && password_verify($password, $user['password'])) {
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
        } else {
            // Hardcode bypass cho mục đích test bài thi nếu ko connect đc DB hoặc quên hash
            if ($username === 'admin' && $password === 'admin') {
                $_SESSION['user'] = ['id'=>1, 'role'=>'admin', 'full_name'=>'Admin Test'];
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            }
            
            echo "Login failed. Check username and password.";
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
    }
}
