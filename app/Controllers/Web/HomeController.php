<?php
// app/Controllers/Web/HomeController.php
require_once '../core/Controller.php';

class HomeController extends Controller {
    public function index() {
        if (isset($_SESSION['user'])) {
            if ($_SESSION['user']['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/student/dashboard'); // Có thể phân biệt teacher sau
            }
        } else {
            header('Location: ' . BASE_URL . '/login');
        }
    }
}
