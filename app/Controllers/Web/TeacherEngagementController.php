<?php
// app/Controllers/Web/TeacherEngagementController.php
require_once '../core/Controller.php';
require_once '../app/Models/Engagement.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class TeacherEngagementController extends Controller {
    private $engagementRepo;
    private $courseRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            } else {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }
        $this->engagementRepo = new EngagementRepository(new Engagement());
        $this->courseRepo = new CourseRepository(new Course());
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
            || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    private function checkCourseOwnership($courseId) {
        $course = $this->courseRepo->getById($courseId);
        if (!$course || $course['teacher_id'] != $_SESSION['user']['id']) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Quyền truy cập bị từ chối. Lớp học này không thuộc quyền phụ trách của bạn.'], 403);
            exit;
        }
        return $course;
    }

    public function index() {
        $teacherId = $_SESSION['user']['id'];
        $myCourses = $this->courseRepo->getCoursesByTeacher($teacherId);

        $this->view('teacher/engagement', [
            'title' => 'Điểm tương tác lớp học (CPI)',
            'myCourses' => $myCourses
        ]);
    }

    public function getEngagement($courseId) {
        $course = $this->checkCourseOwnership($courseId);
        
        // 1. Lấy bảng điểm CPI sinh viên trong lớp
        $scores = $this->engagementRepo->getScoresByCourse($courseId);

        // 2. Lấy quy tắc tính điểm hiện tại của khóa học
        $rules = [
            'rule_present_points' => $course['rule_present_points'],
            'rule_late_points' => $course['rule_late_points'],
            'rule_absent_points' => $course['rule_absent_points'],
            'rule_interaction_points' => $course['rule_interaction_points'],
            'rule_attendance_weight' => $course['rule_attendance_weight'],
            'rule_quiz_weight' => $course['rule_quiz_weight'],
            'rule_absent_limit' => $course['rule_absent_limit'],
            'rule_low_cpi_threshold' => $course['rule_low_cpi_threshold']
        ];

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'scores' => $scores,
                'rules' => $rules
            ]
        ]);
    }

    public function updateRules($courseId) {
        $this->checkCourseOwnership($courseId);
        $data = $this->getJsonInput();

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE courses 
            SET rule_present_points = :present,
                rule_late_points = :late,
                rule_absent_points = :absent,
                rule_interaction_points = :interaction,
                rule_attendance_weight = :att_weight,
                rule_quiz_weight = :quiz_weight,
                rule_absent_limit = :absent_limit,
                rule_low_cpi_threshold = :cpi_threshold
            WHERE id = :course_id
        ");

        $res = $stmt->execute([
            'present' => isset($data['rule_present_points']) && $data['rule_present_points'] !== '' ? (int)$data['rule_present_points'] : null,
            'late' => isset($data['rule_late_points']) && $data['rule_late_points'] !== '' ? (int)$data['rule_late_points'] : null,
            'absent' => isset($data['rule_absent_points']) && $data['rule_absent_points'] !== '' ? (int)$data['rule_absent_points'] : null,
            'interaction' => isset($data['rule_interaction_points']) && $data['rule_interaction_points'] !== '' ? (int)$data['rule_interaction_points'] : null,
            'att_weight' => isset($data['rule_attendance_weight']) && $data['rule_attendance_weight'] !== '' ? (int)$data['rule_attendance_weight'] : null,
            'quiz_weight' => isset($data['rule_quiz_weight']) && $data['rule_quiz_weight'] !== '' ? (int)$data['rule_quiz_weight'] : null,
            'absent_limit' => isset($data['rule_absent_limit']) && $data['rule_absent_limit'] !== '' ? (int)$data['rule_absent_limit'] : null,
            'cpi_threshold' => isset($data['rule_low_cpi_threshold']) && $data['rule_low_cpi_threshold'] !== '' ? (int)$data['rule_low_cpi_threshold'] : null,
            'course_id' => $courseId
        ]);

        if ($res) {
            // Đồng bộ tính toán lại điểm CPI cho cả lớp sau khi sửa quy tắc
            $this->engagementRepo->syncCourseEngagement($courseId);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã cập nhật quy tắc và tính toán lại CPI thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật quy tắc.'], 500);
        }
    }
}
