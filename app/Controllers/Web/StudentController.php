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
        $this->view('student/dashboard', ['title' => 'Chuyên cần & Tương tác']);
    }

    public function schedule() {
        $this->view('student/schedule', ['title' => 'Lịch học']);
    }

    public function myCourses() {
        $this->view('student/my_courses', ['title' => 'Học phần của tôi']);
    }
}
