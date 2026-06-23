<?php
// app/Controllers/Api/LeaveRequestApiController.php
require_once '../core/Controller.php';
require_once '../config/database.php';

class LeaveRequestApiController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
            exit;
        }
    }

    /**
     * Student gửi đơn xin phép vắng cho một buổi học
     */
    public function store() {
        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chỉ sinh viên mới có thể gửi đơn xin phép.'], 403);
            return;
        }

        $data = $this->getJsonInput();
        $studentId = $_SESSION['user']['id'];
        $sessionId = $data['session_id'] ?? null;
        $reason = trim($data['reason'] ?? '');

        if (!$sessionId || empty($reason)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng chọn buổi học và nhập lý do xin phép.'], 400);
            return;
        }

        if (mb_strlen($reason) < 10) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lý do xin phép phải có ít nhất 10 ký tự để giảng viên xem xét.'], 400);
            return;
        }

        if (mb_strlen($reason) > 500) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lý do xin phép không được vượt quá 500 ký tự.'], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();

        // Kiểm tra sinh viên có đăng ký học phần này không
        $stmtCheck = $db->prepare("
            SELECT cs.id 
            FROM class_sessions cs
            JOIN course_students cstud ON cs.course_id = cstud.course_id
            WHERE cs.id = ? AND cstud.student_id = ?
        ");
        $stmtCheck->execute([$sessionId, $studentId]);
        if (!$stmtCheck->fetch()) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không đăng ký học phần của buổi học này.'], 403);
            return;
        }

        // Kiểm tra buổi học chưa diễn ra hoặc đang diễn ra
        $stmtSession = $db->prepare("SELECT session_date, status FROM class_sessions WHERE id = ?");
        $stmtSession->execute([$sessionId]);
        $session = $stmtSession->fetch();

        if (!$session) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy buổi học.'], 404);
            return;
        }

        if ($session['status'] === 'completed') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không thể xin phép buổi học đã kết thúc.'], 400);
            return;
        }

        // Kiểm tra xem đã có đơn chưa
        $stmtExist = $db->prepare("SELECT id, status FROM leave_requests WHERE student_id = ? AND session_id = ?");
        $stmtExist->execute([$studentId, $sessionId]);
        $existing = $stmtExist->fetch();
        if ($existing) {
            $statusText = $existing['status'] === 'pending' ? 'đang chờ duyệt' : ($existing['status'] === 'approved' ? 'đã được duyệt' : 'đã bị từ chối');
            $this->jsonResponse(['status' => 'error', 'message' => "Bạn đã gửi đơn xin phép cho buổi học này (trạng thái: $statusText)."], 400);
            return;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO leave_requests (student_id, session_id, reason, status, created_at)
                VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$studentId, $sessionId, $reason]);

            $this->jsonResponse(['status' => 'success', 'message' => 'Đã gửi đơn xin phép vắng thành công! Vui lòng chờ giảng viên phê duyệt.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi khi gửi đơn: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sinh viên xem lịch sử đơn xin phép của mình
     * Giảng viên xem đơn xin phép của học sinh trong lớp mình dạy
     */
    public function index() {
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user']['id'];
        $role = $_SESSION['user']['role'];

        if ($role === 'student') {
            $stmt = $db->prepare("
                SELECT lr.*, cs.session_date, cs.start_time, c.name as course_name, c.code as course_code
                FROM leave_requests lr
                JOIN class_sessions cs ON lr.session_id = cs.id
                JOIN courses c ON cs.course_id = c.id
                WHERE lr.student_id = ?
                ORDER BY lr.created_at DESC
            ");
            $stmt->execute([$userId]);
        } elseif ($role === 'teacher') {
            $stmt = $db->prepare("
                SELECT lr.*, cs.session_date, cs.start_time, c.name as course_name, c.code as course_code,
                       u.full_name as student_name, u.email as student_email
                FROM leave_requests lr
                JOIN class_sessions cs ON lr.session_id = cs.id
                JOIN courses c ON cs.course_id = c.id
                JOIN users u ON lr.student_id = u.id
                WHERE c.teacher_id = ? AND lr.status = 'pending'
                ORDER BY lr.created_at DESC
            ");
            $stmt->execute([$userId]);
        } else {
            // Admin xem tất cả
            $stmt = $db->prepare("
                SELECT lr.*, cs.session_date, cs.start_time, c.name as course_name, c.code as course_code,
                       u.full_name as student_name
                FROM leave_requests lr
                JOIN class_sessions cs ON lr.session_id = cs.id
                JOIN courses c ON cs.course_id = c.id
                JOIN users u ON lr.student_id = u.id
                ORDER BY lr.created_at DESC
            ");
            $stmt->execute();
        }

        $requests = $stmt->fetchAll();
        $this->jsonResponse(['status' => 'success', 'data' => $requests]);
    }

    /**
     * Giảng viên phê duyệt hoặc từ chối đơn xin phép
     */
    public function update($id) {
        if ($_SESSION['user']['role'] !== 'teacher' && $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chỉ giảng viên mới có thể phê duyệt đơn.'], 403);
            return;
        }

        $data = $this->getJsonInput();
        $newStatus = $data['status'] ?? null;
        $teacherNote = trim($data['teacher_note'] ?? '');

        if (!in_array($newStatus, ['approved', 'rejected'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Trạng thái không hợp lệ. Chỉ chấp nhận: approved hoặc rejected.'], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();

        // Lấy thông tin đơn
        $stmtGet = $db->prepare("
            SELECT lr.*, cs.course_id
            FROM leave_requests lr
            JOIN class_sessions cs ON lr.session_id = cs.id
            WHERE lr.id = ?
        ");
        $stmtGet->execute([$id]);
        $leaveReq = $stmtGet->fetch();

        if (!$leaveReq) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy đơn xin phép.'], 404);
            return;
        }

        // Kiểm tra GV có quyền duyệt không (phải là GV của lớp học phần đó)
        if ($_SESSION['user']['role'] === 'teacher') {
            $stmtCheckTeacher = $db->prepare("SELECT teacher_id FROM courses WHERE id = ?");
            $stmtCheckTeacher->execute([$leaveReq['course_id']]);
            $teacherId = $stmtCheckTeacher->fetchColumn();
            if ($teacherId != $_SESSION['user']['id']) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không có quyền phê duyệt đơn này.'], 403);
                return;
            }
        }

        try {
            // Cập nhật trạng thái đơn
            $stmtUpdate = $db->prepare("
                UPDATE leave_requests SET status = ?, teacher_note = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?
            ");
            $stmtUpdate->execute([$newStatus, $teacherNote, $id]);

            // Nếu approved → cập nhật attendance_records thành 'excused'
            if ($newStatus === 'approved') {
                // Kiểm tra đã có bản ghi điểm danh chưa
                $stmtCheckAtt = $db->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
                $stmtCheckAtt->execute([$leaveReq['session_id'], $leaveReq['student_id']]);
                $existingAtt = $stmtCheckAtt->fetch();

                if ($existingAtt) {
                    // Cập nhật bản ghi hiện có thành excused
                    $stmtUpdateAtt = $db->prepare("
                        UPDATE attendance_records SET status = 'excused', method_id = 3, recorded_at = CURRENT_TIMESTAMP
                        WHERE session_id = ? AND student_id = ?
                    ");
                    $stmtUpdateAtt->execute([$leaveReq['session_id'], $leaveReq['student_id']]);
                } else {
                    // Tạo bản ghi mới với trạng thái excused (manual = method 3)
                    $stmtInsertAtt = $db->prepare("
                        INSERT INTO attendance_records (session_id, student_id, method_id, status, recorded_at)
                        VALUES (?, ?, 3, 'excused', CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE status = 'excused', recorded_at = CURRENT_TIMESTAMP
                    ");
                    $stmtInsertAtt->execute([$leaveReq['session_id'], $leaveReq['student_id']]);
                }

                $msg = 'Đã duyệt đơn xin phép. Điểm danh sinh viên được cập nhật thành "Có phép".';
            } else {
                $msg = 'Đã từ chối đơn xin phép.';
            }

            $this->jsonResponse(['status' => 'success', 'message' => $msg]);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xử lý đơn: ' . $e->getMessage()], 500);
        }
    }
}
