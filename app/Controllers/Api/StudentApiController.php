<?php
// app/Controllers/Api/StudentApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/User.php';
require_once '../app/Repositories/UserRepository.php';
require_once '../app/Repositories/AttendanceRepository.php';
require_once '../app/Models/Attendance.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Engagement.php';

class StudentApiController extends Controller {
    private $userRepo;
    private $attendanceRepo;
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $userModel = new User();
        $this->userRepo = new UserRepository($userModel);
        $this->attendanceRepo = new AttendanceRepository(new Attendance());
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    private function checkAdmin() {
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
            exit;
        }
    }

    public function index() {
        $this->checkAdmin();
        $students = $this->userRepo->getStudents();
        $this->jsonResponse(['status' => 'success', 'data' => $students]);
    }

    public function store() {
        $this->checkAdmin();
        $data = $this->getJsonInput();
        
        if (empty($data['username']) || empty($data['full_name']) || empty($data['email'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'], 400);
        }

        try {
            $this->userRepo->createStudent($data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Thêm sinh viên thành công. Mật khẩu mặc định là 123456.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi: Username hoặc Email có thể đã tồn tại.'], 500);
        }
    }

    public function update($id) {
        $this->checkAdmin();
        $data = $this->getJsonInput();
        
        if (empty($data['username']) || empty($data['full_name']) || empty($data['email'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'], 400);
        }

        try {
            $this->userRepo->updateStudent($id, $data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật sinh viên thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật. Username/Email có thể bị trùng.'], 500);
        }
    }

    public function destroy($id) {
        $this->checkAdmin();
        try {
            $this->userRepo->delete($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa sinh viên thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa (có thể do ràng buộc dữ liệu).'], 500);
        }
    }

    /**
     * Lấy toàn bộ dữ liệu cần hiển thị cho Dashboard của Sinh viên
     */
    public function dashboardData() {
        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
            return;
        }

        $studentId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        // 1. Danh sách lớp học phần sinh viên tham gia
        $stmtCourses = $db->prepare("
            SELECT c.id, c.code, c.class_code, c.name, u.full_name as teacher_name,
                   c.rule_present_points, c.rule_late_points, c.rule_absent_points, c.rule_interaction_points,
                   c.rule_attendance_weight, c.rule_quiz_weight
            FROM courses c
            JOIN course_students cs ON c.id = cs.course_id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE cs.student_id = ?
        ");
        $stmtCourses->execute([$studentId]);
        $courses = $stmtCourses->fetchAll();

        // 2. Điểm chuyên cần và CPI tổng hợp cho từng lớp học
        $courseIds = array_column($courses, 'id');
        $scores = [];
        if (!empty($courseIds)) {
            foreach ($courseIds as $cId) {
                $this->engagementRepo->recalculateScore($cId, $studentId);
            }
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $stmtScores = $db->prepare("
                SELECT * FROM engagement_scores 
                WHERE student_id = ? AND course_id IN ($placeholders)
            ");
            $params = array_merge([$studentId], $courseIds);
            $stmtScores->execute($params);
            $scores = $stmtScores->fetchAll();
        }

        // 3. Lịch sử điểm danh chi tiết (kèm course_id để đếm vắng theo môn)
        $stmtHistory = $db->prepare("
            SELECT cs.id as session_id, cs.session_date, cs.start_time, cs.end_time,
                   c.id as course_id, c.name as course_name, c.code as course_code,
                   ar.status, ar.recorded_at, am.name as method_name
            FROM class_sessions cs
            JOIN courses c ON cs.course_id = c.id
            JOIN course_students cstud ON c.id = cstud.course_id AND cstud.student_id = :sid
            LEFT JOIN attendance_records ar ON ar.session_id = cs.id AND ar.student_id = :sid2
            LEFT JOIN attendance_methods am ON ar.method_id = am.id
            WHERE cs.status IN ('active', 'completed')
            ORDER BY cs.session_date DESC, cs.start_time DESC
        ");
        $stmtHistory->execute([':sid' => $studentId, ':sid2' => $studentId]);
        $history = $stmtHistory->fetchAll();

        // 3b. Tổng hợp số liệu điểm danh server-side
        $attendanceSummary = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
        $absentByCourse = [];
        foreach ($history as $h) {
            $attendanceSummary['total']++;
            $st = $h['status'] ?? 'absent';
            if (array_key_exists($st, $attendanceSummary)) {
                $attendanceSummary[$st]++;
            } else {
                $attendanceSummary['absent']++;
            }
            if ($st === 'absent') {
                $cid = $h['course_id'];
                $absentByCourse[$cid] = ($absentByCourse[$cid] ?? 0) + 1;
            }
        }
        $attendanceSummary['absentByCourse'] = $absentByCourse;

        // 4. Các buổi học đang active (kèm student_already_attended)
        $activeSessions = [];
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $stmtSessions = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.course_id IN ($placeholders) AND cs.status = 'active'
                ORDER BY cs.session_date ASC, cs.start_time ASC
            ");
            $stmtSessions->execute($courseIds);
            $rawSessions = $stmtSessions->fetchAll();

            foreach ($rawSessions as $sess) {
                $stmtAtt = $db->prepare("SELECT status FROM attendance_records WHERE session_id = ? AND student_id = ?");
                $stmtAtt->execute([$sess['id'], $studentId]);
                $attRecord = $stmtAtt->fetch();
                $sess['student_already_attended'] = $attRecord ? true : false;
                $sess['my_attendance_status'] = $attRecord ? $attRecord['status'] : null;
                $activeSessions[] = $sess;
            }
        }

        // 5. Cảnh báo học tập của sinh viên
        $stmtAlerts = $db->prepare("
            SELECT a.*, c.name as course_name 
            FROM alerts a
            JOIN courses c ON a.course_id = c.id
            WHERE a.user_id = ? AND a.is_read = 0
            ORDER BY a.created_at DESC
        ");
        $stmtAlerts->execute([$studentId]);
        $alerts = $stmtAlerts->fetchAll();

        // 6. Lịch sử làm bài Quiz
        $stmtQuizHistory = $db->prepare("
            SELECT qs.id, qs.score, qs.submitted_at, qz.title as quiz_title, qz.total_marks,
                   c.name as course_name, c.code as course_code
            FROM quiz_submissions qs
            JOIN quiz_sessions qz ON qs.quiz_id = qz.id
            JOIN class_sessions cs ON qz.session_id = cs.id
            JOIN courses c ON cs.course_id = c.id
            WHERE qs.student_id = ?
            ORDER BY qs.submitted_at DESC
        ");
        $stmtQuizHistory->execute([$studentId]);
        $quizHistory = $stmtQuizHistory->fetchAll();

        // 7. Lịch học sắp tới (7 ngày tới)
        $upcomingSchedule = [];
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $stmtUpcoming = $db->prepare("
                SELECT cs.id, cs.session_date, cs.start_time, cs.end_time, cs.status, cs.room, cs.period,
                       c.name as course_name, c.code as course_code, u.full_name as teacher_name
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE cs.course_id IN ($placeholders)
                  AND cs.session_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND cs.status != 'completed'
                ORDER BY cs.session_date ASC, cs.start_time ASC
                LIMIT 10
            ");
            $stmtUpcoming->execute($courseIds);
            $upcomingSchedule = $stmtUpcoming->fetchAll();
        }

        // 8. Lịch sử đơn xin phép vắng
        $stmtLeave = $db->prepare("
            SELECT lr.*, cs.session_date, cs.start_time, c.name as course_name, c.code as course_code
            FROM leave_requests lr
            JOIN class_sessions cs ON lr.session_id = cs.id
            JOIN courses c ON cs.course_id = c.id
            WHERE lr.student_id = ?
            ORDER BY lr.created_at DESC
            LIMIT 10
        ");
        $stmtLeave->execute([$studentId]);
        $myLeaveRequests = $stmtLeave->fetchAll();

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'courses'           => $courses,
                'scores'            => $scores,
                'history'           => $history,
                'attendanceSummary' => $attendanceSummary,
                'activeSessions'    => $activeSessions,
                'alerts'            => $alerts,
                'quizHistory'       => $quizHistory,
                'upcomingSchedule'  => $upcomingSchedule,
                'myLeaveRequests'   => $myLeaveRequests,
            ]
        ]);
    }
}
