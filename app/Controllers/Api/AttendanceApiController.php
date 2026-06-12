<?php
// app/Controllers/Api/AttendanceApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Attendance.php';
require_once '../app/Models/ClassSession.php';
require_once '../app/Repositories/AttendanceRepository.php';
require_once '../app/Repositories/SessionRepository.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Engagement.php';
require_once '../app/Patterns/Strategy/CodeAttendanceStrategy.php';
require_once '../app/Patterns/Strategy/QrAttendanceStrategy.php';
require_once '../app/Patterns/Strategy/ManualAttendanceStrategy.php';
require_once '../app/Patterns/Observer/AttendanceSubject.php';
require_once '../app/Patterns/Observer/AlertObserver.php';

class AttendanceApiController extends Controller {
    private $attendanceRepo;
    private $sessionRepo;
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        
        $this->attendanceRepo = new AttendanceRepository(new Attendance());
        $this->sessionRepo = new SessionRepository(new ClassSession());
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    /**
     * Lấy danh sách điểm danh của một buổi học (Giảng viên)
     */
    public function getAttendance($sessionId) {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }
        
        $records = $this->attendanceRepo->getRecordsBySession($sessionId);
        $this->jsonResponse(['status' => 'success', 'data' => $records]);
    }

    /**
     * Giảng viên mở phiên điểm danh (Code hoặc QR)
     */
    public function startAttendance($sessionId) {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $this->getJsonInput();
        $method = $data['method'] ?? 'Code'; // 'Code' hoặc 'QR'
        $minutes = (int)($data['minutes'] ?? 5);

        $session = $this->sessionRepo->getById($sessionId);
        if (!$session) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy buổi học.'], 404);
        }

        $code = null;
        $qrToken = null;
        $expiresAt = date('Y-m-d H:i:s', time() + ($minutes * 60));

        if ($method === 'Code') {
            $code = strval(rand(100000, 999999));
        } else {
            $qrToken = bin2hex(random_bytes(16));
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE class_sessions 
            SET status = 'active', attendance_code = ?, qr_token = ?, attendance_expires_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$code, $qrToken, $expiresAt, $sessionId]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Đã mở điểm danh thành công.',
            'data' => [
                'method' => $method,
                'code' => $code,
                'qr_token' => $qrToken,
                'expires_at' => $expiresAt
            ]
        ]);
    }

    /**
     * Giảng viên đóng phiên điểm danh
     */
    public function stopAttendance($sessionId) {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $db = Database::getInstance()->getConnection();
        
        // Cập nhật tất cả học sinh chưa điểm danh là vắng mặt (absent)
        // Lấy tất cả sinh viên thuộc khóa học
        $stmtSession = $db->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
        $stmtSession->execute([$sessionId]);
        $session = $stmtSession->fetch();
        if ($session) {
            $courseId = $session['course_id'];
            
            // Lấy danh sách SV lớp học
            $stmtStud = $db->prepare("SELECT student_id FROM course_students WHERE course_id = ?");
            $stmtStud->execute([$courseId]);
            $students = $stmtStud->fetchAll();
            
            // Bắt đầu transaction điểm danh tự động vắng cho các bạn chưa có bản ghi
            $db->beginTransaction();
            try {
                $subject = new AttendanceSubject();
                $subject->attach(new AlertObserver());
                
                foreach ($students as $stud) {
                    $studentId = $stud['student_id'];
                    
                    // Kiểm tra xem đã điểm danh chưa
                    $stmtCheck = $db->prepare("SELECT status FROM attendance_records WHERE session_id = ? AND student_id = ?");
                    $stmtCheck->execute([$sessionId, $studentId]);
                    if (!$stmtCheck->fetch()) {
                        // Chưa có bản ghi -> đánh dấu là vắng (absent), method = 3 (Manual)
                        $this->attendanceRepo->saveRecord($sessionId, $studentId, 3, 'absent');
                        
                        // Notify Observer
                        $subject->recordAttendance($sessionId, $studentId, 'absent');
                    }
                }
                
                // Đóng buổi học
                $stmtClose = $db->prepare("
                    UPDATE class_sessions 
                    SET status = 'completed', attendance_code = NULL, qr_token = NULL, attendance_expires_at = NULL 
                    WHERE id = ?
                ");
                $stmtClose->execute([$sessionId]);
                
                $db->commit();
                
                // Tính toán lại CPI cho cả lớp
                $this->engagementRepo->syncCourseEngagement($courseId);

                $this->jsonResponse(['status' => 'success', 'message' => 'Đã đóng điểm danh và hoàn tất buổi học.']);
            } catch (Exception $e) {
                $db->rollBack();
                $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi đóng điểm danh: ' . $e->getMessage()], 500);
            }
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy buổi học.'], 404);
        }
    }

    /**
     * Giảng viên cập nhật điểm danh thủ công (Manual)
     */
    public function updateAttendance($sessionId) {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $this->getJsonInput();
        $studentId = $data['student_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$studentId || !$status) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu dữ liệu cập nhật.'], 400);
        }

        $strategy = new ManualAttendanceStrategy();
        $result = $strategy->validateAndRecord($sessionId, $studentId, ['status' => $status]);

        if ($result['success']) {
            $this->attendanceRepo->saveRecord($sessionId, $studentId, $result['method_id'], $result['status']);
            
            // Kích hoạt Observer để kiểm tra cảnh báo
            $subject = new AttendanceSubject();
            $subject->attach(new AlertObserver());
            $subject->recordAttendance($sessionId, $studentId, $result['status']);

            // Tính toán lại CPI
            $stmtSession = Database::getInstance()->getConnection()->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
            $stmtSession->execute([$sessionId]);
            $courseId = $stmtSession->fetchColumn();
            if ($courseId) {
                $this->engagementRepo->recalculateScore($courseId, $studentId);
            }

            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => $result['message']], 400);
        }
    }

    /**
     * Sinh viên nộp điểm danh trực tuyến (Code hoặc QR)
     */
    public function submit() {
        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chức năng dành cho Sinh viên.'], 403);
        }

        $data = $this->getJsonInput();
        $sessionId = $data['session_id'] ?? null;
        $studentId = $_SESSION['user']['id'];

        if (!$sessionId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu ID buổi học.'], 400);
        }

        // Kiểm tra xem sinh viên có thuộc lớp này không
        $db = Database::getInstance()->getConnection();
        $stmtCheckEnroll = $db->prepare("
            SELECT cs.course_id 
            FROM class_sessions cs
            JOIN course_students cstud ON cs.course_id = cstud.course_id
            WHERE cs.id = ? AND cstud.student_id = ?
        ");
        $stmtCheckEnroll->execute([$sessionId, $studentId]);
        $courseId = $stmtCheckEnroll->fetchColumn();

        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không đăng ký học phần này.'], 403);
        }

        // Xác định hình thức và Strategy tương ứng
        $stmtSession = $db->prepare("SELECT qr_token, attendance_code FROM class_sessions WHERE id = ?");
        $stmtSession->execute([$sessionId]);
        $sessInfo = $stmtSession->fetch();
        
        $strategy = null;
        if (isset($data['qr_token']) && !empty($data['qr_token'])) {
            $strategy = new QrAttendanceStrategy();
        } else if (isset($data['code']) && !empty($data['code'])) {
            $strategy = new CodeAttendanceStrategy();
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng cung cấp mã Code hoặc QR token để điểm danh.'], 400);
        }

        $result = $strategy->validateAndRecord($sessionId, $studentId, $data);

        if ($result['success']) {
            // Lưu bản ghi điểm danh
            $this->attendanceRepo->saveRecord($sessionId, $studentId, $result['method_id'], $result['status']);
            
            // Kích hoạt Observer để kiểm tra cảnh báo
            $subject = new AttendanceSubject();
            $subject->attach(new AlertObserver());
            $subject->recordAttendance($sessionId, $studentId, $result['status']);

            // Tính toán lại CPI
            $this->engagementRepo->recalculateScore($courseId, $studentId);

            $this->jsonResponse([
                'status' => 'success', 
                'message' => $result['message'],
                'data' => [
                    'status' => $result['status'],
                    'recorded_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => $result['message']], 400);
        }
    }
}
