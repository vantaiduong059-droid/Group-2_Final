<?php
// app/Controllers/Api/CourseApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class CourseApiController extends Controller {
    private $courseRepo;

    public function __construct() {
        $courseModel = new Course();
        $this->courseRepo = new CourseRepository($courseModel);
    }

    // Lấy danh sách khóa học (Read)
    public function index() {
        $courses = $this->courseRepo->getAll();
        $this->jsonResponse(['status' => 'success', 'data' => $courses]);
    }

    // Lấy chi tiết 1 khóa học
    public function show($id) {
        $course = $this->courseRepo->getCourseDetails($id);
        if ($course) {
            $this->jsonResponse(['status' => 'success', 'data' => $course]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy khóa học'], 404);
        }
    }

    // Thêm mới khóa học (Create)
    public function store() {
        $data = $this->getJsonInput();
        
        // Basic Validation
        if (empty($data['code']) || empty($data['class_code']) || empty($data['name']) || empty($data['credits'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc'], 400);
        }

        // Tính số tiết tự động nếu chưa có
        if (empty($data['periods'])) {
            $data['periods'] = (int)$data['credits'] * 15;
        }

        try {
            $this->courseRepo->createCourse($data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo khóa học thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi: Mã khóa học có thể đã tồn tại.'], 500);
        }
    }

    // Sửa khóa học (Update)
    public function update($id) {
        $data = $this->getJsonInput();
        
        if (empty($data['code']) || empty($data['class_code']) || empty($data['name']) || empty($data['credits'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc'], 400);
        }

        // Tính số tiết tự động nếu chưa có
        if (empty($data['periods'])) {
            $data['periods'] = (int)$data['credits'] * 15;
        }

        try {
            $this->courseRepo->updateCourse($id, $data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật'], 500);
        }
    }

    // Xóa khóa học (Delete)
    public function destroy($id) {
        try {
            $this->courseRepo->delete($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa khóa học thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa (ràng buộc dữ liệu)'], 500);
        }
    }

    /**
     * Lấy danh sách sinh viên của lớp học phần
     */
    public function getStudents($courseId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.email 
            FROM users u
            JOIN course_students cs ON u.id = cs.student_id
            WHERE cs.course_id = ?
            ORDER BY u.full_name ASC
        ");
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll();
        $this->jsonResponse(['status' => 'success', 'data' => $students]);
    }

    /**
     * Thêm sinh viên vào lớp học phần
     */
    public function addStudent($courseId) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        
        $data = $this->getJsonInput();
        $studentId = $data['student_id'] ?? null;
        if (!$studentId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu ID sinh viên'], 400);
        }

        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO course_students (course_id, student_id) VALUES (?, ?)");
            $stmt->execute([$courseId, $studentId]);
            
            // Khởi tạo điểm chuyên cần tích lũy mặc định
            $stmtScore = $db->prepare("
                INSERT INTO engagement_scores (course_id, student_id, attendance_points, interaction_points, total_score)
                VALUES (?, ?, 0, 0, 100)
                ON DUPLICATE KEY UPDATE total_score = 100
            ");
            $stmtScore->execute([$courseId, $studentId]);

            $this->jsonResponse(['status' => 'success', 'message' => 'Thêm sinh viên vào lớp thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Sinh viên này đã tham gia lớp học rồi.'], 500);
        }
    }

    /**
     * Xóa sinh viên khỏi lớp học phần
     */
    public function removeStudent($courseId, $studentId) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM course_students WHERE course_id = ? AND student_id = ?");
            $stmt->execute([$courseId, $studentId]);
            
            $stmtScore = $db->prepare("DELETE FROM engagement_scores WHERE course_id = ? AND student_id = ?");
            $stmtScore->execute([$courseId, $studentId]);

            $this->jsonResponse(['status' => 'success', 'message' => 'Đã xóa sinh viên khỏi lớp học phần']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa sinh viên: ' . $e->getMessage()], 500);
        }
    }
}
