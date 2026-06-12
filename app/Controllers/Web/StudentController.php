<?php
// app/Controllers/Web/StudentController.php
require_once '../core/Controller.php';

class StudentController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function dashboard() {
        $this->view('student/dashboard', ['title' => 'Student Dashboard']);
    }
}
