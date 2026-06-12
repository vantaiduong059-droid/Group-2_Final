<?php
// app/Controllers/Web/TeacherController.php
require_once '../core/Controller.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class TeacherController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function dashboard() {
        $teacherId = $_SESSION['user']['id'];
        $courseModel = new Course();
        $courseRepo = new CourseRepository($courseModel);
        $myCourses = $courseRepo->getCoursesByTeacher($teacherId);

        $this->view('teacher/dashboard', [
            'title' => 'Teacher Dashboard',
            'myCourses' => $myCourses
        ]);
    }
}
