<?php
// app/Controllers/Web/TeacherController.php
require_once '../core/Controller.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';
require_once '../app/Models/ClassSession.php';
require_once '../app/Repositories/SessionRepository.php';

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

    public function myCourses() {
        $teacherId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesByTeacher($teacherId);
        $this->view('teacher/my_courses', [
            'title' => 'Lớp học của tôi',
            'myCourses' => $myCourses
        ]);
    }

    public function courseStudents($courseId) {
        $teacherId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $course = $courseRepo->getCourseDetails($courseId);
        if (!$course || $course['teacher_id'] != $teacherId) {
            header('Location: ' . BASE_URL . '/teacher/my-courses');
            exit;
        }
        $students = $courseRepo->getStudentsByCourse($courseId);
        $this->view('teacher/course_students', [
            'title' => 'Sinh viên lớp ' . $course['code'],
            'course' => $course,
            'students' => $students
        ]);
    }

    public function sessions() {
        $this->view('teacher/sessions', ['title' => 'Lịch dạy của tôi']);
    }

    public function sessionDetail($sessionId) {
        $teacherId = $_SESSION['user']['id'];
        $sessionRepo = new SessionRepository(new ClassSession());
        $session = $sessionRepo->getSessionDetails($sessionId);
        if (!$session) {
            header('Location: ' . BASE_URL . '/teacher/sessions');
            exit;
        }
        // Kiểm tra quyền sở hữu lớp
        $db = Database::getInstance()->getConnection();
        $stmtCheck = $db->prepare("SELECT teacher_id FROM courses WHERE id = ?");
        $stmtCheck->execute([$session['course_id']]);
        $courseRow = $stmtCheck->fetch();
        if (!$courseRow || $courseRow['teacher_id'] != $teacherId) {
            header('Location: ' . BASE_URL . '/teacher/sessions');
            exit;
        }
        $this->view('teacher/session_detail', [
            'title' => 'Buổi học - ' . $session['course_name'],
            'session' => $session
        ]);
    }

    public function currentSession($courseId) {
        $teacherId = $_SESSION['user']['id'];
        
        // Kiểm tra quyền sở hữu lớp
        $db = Database::getInstance()->getConnection();
        $stmtCheck = $db->prepare("SELECT id, teacher_id FROM courses WHERE id = ?");
        $stmtCheck->execute([$courseId]);
        $course = $stmtCheck->fetch();
        if (!$course || $course['teacher_id'] != $teacherId) {
            header('Location: ' . BASE_URL . '/teacher/my-courses');
            exit;
        }

        // 1. Tìm buổi học đang hoạt động (active)
        $stmt = $db->prepare("
            SELECT id FROM class_sessions 
            WHERE course_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$courseId]);
        $sessionId = $stmt->fetchColumn();

        // 2. Nếu không có, tìm buổi học sắp diễn ra (scheduled) gần nhất
        if (!$sessionId) {
            $stmt = $db->prepare("
                SELECT id FROM class_sessions 
                WHERE course_id = ? AND status = 'scheduled' AND session_date >= CURDATE()
                ORDER BY session_date ASC, start_time ASC
                LIMIT 1
            ");
            $stmt->execute([$courseId]);
            $sessionId = $stmt->fetchColumn();
        }

        // 3. Nếu vẫn không có, tìm buổi học gần đây nhất đã kết thúc
        if (!$sessionId) {
            $stmt = $db->prepare("
                SELECT id FROM class_sessions 
                WHERE course_id = ?
                ORDER BY session_date DESC, start_time DESC
                LIMIT 1
            ");
            $stmt->execute([$courseId]);
            $sessionId = $stmt->fetchColumn();
        }

        // Nếu tìm thấy buổi học, chuyển hướng đến chi tiết buổi học đó
        if ($sessionId) {
            header('Location: ' . BASE_URL . '/teacher/session-detail/' . $sessionId);
            exit;
        } else {
            // Nếu lớp học chưa có buổi nào, chuyển hướng sang trang lịch dạy của lớp đó
            header('Location: ' . BASE_URL . '/teacher/sessions?course_id=' . $courseId);
            exit;
        }
    }

    public function profile() {
        $this->view('teacher/profile', ['title' => 'Thông tin cá nhân']);
    }

    public function getMyCourses() {
        $teacherId = $_SESSION['user']['id'];
        $courseRepo = new CourseRepository(new Course());
        $myCourses = $courseRepo->getCoursesByTeacher($teacherId);
        $this->jsonResponse(['status' => 'success', 'data' => $myCourses]);
    }

    public function notifications() {
        $this->view('teacher/notifications', [
            'title' => 'Thông báo'
        ]);
    }
}
