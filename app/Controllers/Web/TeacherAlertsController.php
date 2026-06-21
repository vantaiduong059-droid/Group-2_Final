<?php
// app/Controllers/Web/TeacherAlertsController.php
require_once '../core/Controller.php';
require_once '../app/Models/Alert.php';
require_once '../app/Repositories/AlertRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class TeacherAlertsController extends Controller {
    private $alertRepo;
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
        $this->alertRepo = new AlertRepository(new Alert());
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

    private function checkAlertOwnership($alertId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT course_id FROM alerts WHERE id = ?");
        $stmt->execute([$alertId]);
        $courseId = $stmt->fetchColumn();
        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy cảnh báo.'], 404);
            exit;
        }
        $this->checkCourseOwnership($courseId);
        return $alertId;
    }

    public function index() {
        $teacherId = $_SESSION['user']['id'];
        $myCourses = $this->courseRepo->getCoursesByTeacher($teacherId);

        $this->view('teacher/alerts', [
            'title' => 'Cảnh báo lớp học',
            'myCourses' => $myCourses
        ]);
    }

    public function getAlerts($courseId) {
        $this->checkCourseOwnership($courseId);
        
        // Lấy tất cả cảnh báo thuộc lớp học này
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, u.full_name as user_name, u.username as student_code, u.email as student_email,
                   c.name as course_name, c.code as course_code, c.class_code as course_class_code,
                   adv.full_name as advisor_name
            FROM alerts a
            JOIN users u ON a.user_id = u.id
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN users adv ON a.advisor_id = adv.id
            WHERE a.course_id = :course_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute(['course_id' => $courseId]);
        $alerts = $stmt->fetchAll();

        $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
    }

    public function resolveAlert($alertId) {
        $this->checkAlertOwnership($alertId);
        $data = $this->getJsonInput();
        $notes = $data['notes'] ?? '';
        $status = $data['status'] ?? 'resolved'; // resolved hoặc pending

        if (empty($notes)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập ghi chú xử lý.'], 400);
        }

        try {
            $this->alertRepo->updateAlertStatus($alertId, $status, $notes);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã cập nhật trạng thái xử lý cảnh báo thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật: ' . $e->getMessage()], 500);
        }
    }
}
