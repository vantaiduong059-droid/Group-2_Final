<?php
// app/Controllers/Web/StudentController.php
require_once '../core/Controller.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class StudentController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function dashboard() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/dashboard', [
            'title' => 'Trang chủ',
            'myCourses' => $myCourses
        ]);
    }

    public function schedule() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/schedule', [
            'title' => 'Lịch học',
            'myCourses' => $myCourses
        ]);
    }

    public function attendance() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/attendance', [
            'title' => 'Điểm danh',
            'myCourses' => $myCourses
        ]);
    }

    public function quiz() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/quiz', [
            'title' => 'Quiz & Thảo luận',
            'myCourses' => $myCourses
        ]);
    }

    public function history() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/history', [
            'title' => 'Lịch sử điểm danh & Tương tác',
            'myCourses' => $myCourses
        ]);
    }

    public function profile() {
        $this->view('student/profile', ['title' => 'Thông tin cá nhân']);
    }

    public function notifications() {
        $studentId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesForStudent($studentId);
        $this->view('student/notifications', [
            'title' => 'Thông báo',
            'myCourses' => $myCourses
        ]);
    }
}
