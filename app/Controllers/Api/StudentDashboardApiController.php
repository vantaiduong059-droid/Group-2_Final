<?php
// app/Controllers/Api/StudentDashboardApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/User.php';
require_once '../app/Repositories/UserRepository.php';
require_once '../app/Models/Attendance.php';
require_once '../app/Repositories/AttendanceRepository.php';
require_once '../app/Models/Engagement.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class StudentDashboardApiController extends Controller {
    private $attendanceRepo;
    private $engagementRepo;
    private $courseRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $this->attendanceRepo = new AttendanceRepository(new Attendance());
        $this->engagementRepo = new EngagementRepository(new Engagement());
        $this->courseRepo     = new CourseRepository(new Course());
    }

    /**
     * GET /api/student/dashboard - Tổng quan dashboard sinh viên
     */
    public function summary() {
        $studentId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        try {
            // 1. Thông tin cá nhân của SV
            $stmtInfo = $db->prepare("
                SELECT u.id, u.username as student_code, u.full_name, u.email, u.cohort, m.name as major_name, u.avatar_url
                FROM users u
                LEFT JOIN majors m ON u.major_id = m.id
                WHERE u.id = ?
            ");
            $stmtInfo->execute([$studentId]);
            $studentInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            // Tự động gán lớp mẫu hoặc lớp học phần đầu tiên
            $stmtClass = $db->prepare("
                SELECT c.class_code 
                FROM course_students cs 
                JOIN courses c ON cs.course_id = c.id 
                WHERE cs.student_id = ? 
                LIMIT 1
            ");
            $stmtClass->execute([$studentId]);
            $className = $stmtClass->fetchColumn();
            $studentInfo['class_name'] = $className ?: 'Lớp học phần';

            // Danh sách lớp của SV
            $courses = $this->courseRepo->getCoursesForStudent($studentId);

            // Tổng kê điểm danh và CPI tổng hợp từ Helper chung
            $totalSessions = 0;
            $present = 0;
            $late = 0;
            $absent = 0;
            $cpiSum = 0;
            $cpiCount = 0;
            
            foreach ($courses as &$c) {
                $stats = AttendanceStatsHelper::getStudentStats($studentId, $c['id']);
                if ($stats) {
                    $totalSessions += $stats['passed_sessions'];
                    $present += $stats['present'];
                    $late += $stats['late'];
                    $absent += $stats['absent'];
                    $cpiSum += $stats['cpi'];
                    $cpiCount++;
                    $c['stats'] = $stats;
                } else {
                    $c['stats'] = [
                        'passed_sessions' => 0,
                        'present' => 0,
                        'late' => 0,
                        'absent' => 0,
                        'attendance_rate' => null,
                        'cpi' => 100.0
                    ];
                }
            }
            unset($c);
            
            $attendanceRate = $totalSessions > 0 ? round(($present + $late) / $totalSessions * 100, 1) : null;
            $avgCpi = $cpiCount > 0 ? round($cpiSum / $cpiCount, 1) : null;

            // Cảnh báo của bản thân
            $stmtAlerts = $db->prepare("
                SELECT a.*, c.name as course_name, c.code as course_code
                FROM alerts a
                LEFT JOIN courses c ON a.course_id = c.id
                WHERE a.user_id = :sid AND a.is_read = 0
                ORDER BY a.created_at DESC
                LIMIT 5
            ");
            $stmtAlerts->execute(['sid' => $studentId]);
            $alerts = $stmtAlerts->fetchAll();

            // Buổi học sắp tới (7 ngày tới)
            $stmtUpcoming = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code,
                       u.full_name as teacher_name,
                       ar.status as my_attendance
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                JOIN course_students cstu ON c.id = cstu.course_id AND cstu.student_id = :sid
                LEFT JOIN users u ON c.teacher_id = u.id
                LEFT JOIN attendance_records ar ON cs.id = ar.session_id AND ar.student_id = :sid2
                WHERE cs.session_date >= CURDATE() AND cs.session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY cs.session_date ASC, cs.start_time ASC
                LIMIT 10
            ");
            $stmtUpcoming->execute(['sid' => $studentId, 'sid2' => $studentId]);
            $upcoming = $stmtUpcoming->fetchAll();

            // Buổi học đang hoạt động điểm danh (active_sessions)
            $stmtActive = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code,
                       u.full_name as teacher_name,
                       ar.status as my_attendance,
                       cs.attendance_expires_at
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                JOIN course_students cstu ON c.id = cstu.course_id AND cstu.student_id = :sid
                LEFT JOIN users u ON c.teacher_id = u.id
                LEFT JOIN attendance_records ar ON cs.id = ar.session_id AND ar.student_id = :sid2
                WHERE cs.status = 'active'
                ORDER BY cs.session_date DESC, cs.start_time DESC
                LIMIT 5
            ");
            $stmtActive->execute(['sid' => $studentId, 'sid2' => $studentId]);
            $activeSessions = $stmtActive->fetchAll(PDO::FETCH_ASSOC);
            foreach ($activeSessions as &$s) {
                $statusInfo = AttendanceSessionHelper::getStatus($s);
                $s['attendance_status'] = $statusInfo['status'];
                $s['remaining_minutes'] = $statusInfo['remaining_minutes'];
            }

            // Thông báo
            $stmtNoti = $db->prepare("
                SELECT id, title, message, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmtNoti->execute([$studentId]);
            $notificationsList = $stmtNoti->fetchAll(PDO::FETCH_ASSOC);

            // Dữ liệu biểu đồ 1: Tỷ lệ chuyên cần theo môn
            $stmtChartAttendance = $db->prepare("
                SELECT c.code as course_code, c.name as course_name,
                       COUNT(ar.id) as total,
                       SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended
                FROM courses c
                JOIN course_students cs ON c.id = cs.course_id
                JOIN class_sessions css ON c.id = css.course_id
                LEFT JOIN attendance_records ar ON css.id = ar.session_id AND ar.student_id = cs.student_id
                WHERE cs.student_id = ? AND CONCAT(css.session_date, ' ', css.end_time) < NOW()
                GROUP BY c.id
            ");
            $stmtChartAttendance->execute([$studentId]);
            $chartAttendanceData = $stmtChartAttendance->fetchAll(PDO::FETCH_ASSOC);

            // Dữ liệu biểu đồ 2: Số buổi vắng theo tháng
            $stmtChartAbsents = $db->prepare("
                SELECT DATE_FORMAT(css.session_date, '%m/%Y') as month_str,
                       COUNT(ar.id) as absent_count
                FROM class_sessions css
                JOIN attendance_records ar ON css.id = ar.session_id
                WHERE ar.student_id = ? AND ar.status = 'absent' AND CONCAT(css.session_date, ' ', css.end_time) < NOW()
                GROUP BY month_str
                ORDER BY css.session_date ASC
            ");
            $stmtChartAbsents->execute([$studentId]);
            $chartAbsentsData = $stmtChartAbsents->fetchAll(PDO::FETCH_ASSOC);

            // Dữ liệu biểu đồ 3: CPI theo thời gian của bản thân
            // Lấy configs hệ thống trước
            $stmtConfig = $db->prepare("SELECT config_key, config_value FROM system_configs");
            $stmtConfig->execute();
            $sysConfigs = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);

            $chartCpiData = $this->getStudentOverallCpiTrends($db, $studentId, $courses, $sysConfigs);
            if (empty($chartCpiData)) {
                $cpiDates = [];
                for ($i = 7; $i >= 0; $i--) {
                    $cpiDates[] = date('Y-m-d', strtotime("-$i days"));
                }
                foreach ($cpiDates as $date) {
                    $chartCpiData[] = [
                        'date' => $date,
                        'cpi_score' => null
                    ];
                }
            }

            $this->jsonResponse(['status' => 'success', 'data' => [
                'student_info'     => $studentInfo,
                'courses_count'    => count($courses),
                'total_sessions'   => $totalSessions,
                'present_count'    => $present,
                'late_count'       => $late,
                'absent_count'     => $absent,
                'attendance_rate'  => $attendanceRate,
                'avg_cpi'          => $avgCpi,
                'alerts'           => $alerts,
                'upcoming_sessions'=> $upcoming,
                'courses'          => $courses,
                'active_sessions'  => $activeSessions,
                'notifications'    => $notificationsList,
                'charts'           => [
                    'attendance_by_course' => $chartAttendanceData,
                    'absents_by_month' => $chartAbsentsData,
                    'cpi_trends' => $chartCpiData
                ]
            ]]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/student/sessions - Lịch học của SV
     */
    public function sessions() {
        $studentId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();
        $courseId = $_GET['course_id'] ?? $_GET['subject_id'] ?? null;
        $hasActivities = isset($_GET['has_activities']) && $_GET['has_activities'] == 1;
        $pastOnly = isset($_GET['past_only']) && $_GET['past_only'] == 1;

        $sql = "
            SELECT cs.*, c.name as course_name, c.code as course_code,
                   u.full_name as teacher_name, u.email as teacher_email, cs.room,
                   ar.status as my_attendance, ar.recorded_at as attended_at
            FROM class_sessions cs
            JOIN courses c ON cs.course_id = c.id
            JOIN course_students cstu ON c.id = cstu.course_id AND cstu.student_id = :sid
            LEFT JOIN users u ON c.teacher_id = u.id
            LEFT JOIN attendance_records ar ON cs.id = ar.session_id AND ar.student_id = :sid2
            WHERE 1=1
        ";
        $params = ['sid' => $studentId, 'sid2' => $studentId];

        if ($courseId) {
            $sql .= " AND cs.course_id = :course_id";
            $params['course_id'] = $courseId;
        }

        if ($pastOnly) {
            $sql .= " AND CONCAT(cs.session_date, ' ', cs.start_time) <= NOW()";
        }

        if ($hasActivities) {
            $sql .= " AND (
                EXISTS (SELECT 1 FROM quiz_sessions qz WHERE qz.session_id = cs.id)
                OR EXISTS (SELECT 1 FROM class_discussions cd WHERE cd.session_id = cs.id)
            )";
        }

        $sql .= " ORDER BY cs.session_date DESC, cs.start_time DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['status' => 'success', 'data' => $sessions]);
    }

    /**
     * GET /api/student/subjects - Danh sách các môn học (học phần) sinh viên đang học có buổi đã diễn ra
     */
    public function subjects() {
        $studentId = $_SESSION['user']['id'];
        $courses = $this->courseRepo->getCoursesForStudent($studentId);
        $this->jsonResponse(['status' => 'success', 'data' => $courses]);
    }

    /**
     * GET /api/student/history - Lịch sử điểm danh & tương tác
     */
    public function history() {
        $studentId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        // Lịch sử điểm danh
        $attendanceHistory = $this->attendanceRepo->getStudentAttendanceHistory($studentId);

        // Lịch sử tương tác (interaction_logs)
        $stmtLogs = $db->prepare("
            SELECT il.*, cs_name.name as course_name, cs_name.code as course_code,
                   CASE il.type WHEN 'question' THEN 'Đặt câu hỏi' WHEN 'answer' THEN 'Trả lời' WHEN 'discussion' THEN 'Thảo luận' ELSE il.type END as type_text
            FROM interaction_logs il
            LEFT JOIN class_sessions cls ON il.session_id = cls.id
            LEFT JOIN courses cs_name ON cls.course_id = cs_name.id
            WHERE il.student_id = :sid
            ORDER BY il.created_at DESC
            LIMIT 50
        ");
        $stmtLogs->execute(['sid' => $studentId]);
        $interactionHistory = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        // CPI theo từng môn học và Số liệu chuyên cần theo từng môn học từ Helper chung
        $courses = $this->courseRepo->getCoursesForStudent($studentId);
        $cpiByClass = [];
        $attendanceSummaryByCourse = [];

        // Lấy ngưỡng vắng mặc định hệ thống
        $stmtDefaultLimit = $db->prepare("SELECT config_value FROM system_configs WHERE config_key = 'default_absent_limit'");
        $stmtDefaultLimit->execute();
        $defaultAbsentLimit = (int)($stmtDefaultLimit->fetchColumn() ?: 3);

        foreach ($courses as $c) {
            $courseId = $c['id'];
            $stats = AttendanceStatsHelper::getStudentStats($studentId, $courseId);
            
            // Ngưỡng vắng
            $absentLimit = ($c['rule_absent_limit'] !== null) ? (int)$c['rule_absent_limit'] : $defaultAbsentLimit;
            
            if ($stats) {
                $isOverLimit = $stats['absent'] > $absentLimit;
                
                $attendanceSummaryByCourse[] = [
                    'course_id' => $courseId,
                    'course_code' => $c['code'],
                    'course_name' => $c['name'],
                    'passed_sessions' => $stats['passed_sessions'],
                    'present' => $stats['present'],
                    'late' => $stats['late'],
                    'absent' => $stats['absent'],
                    'attendance_rate' => $stats['attendance_rate'],
                    'absent_limit' => $absentLimit,
                    'is_over_limit' => $isOverLimit
                ];

                $cpiByClass[] = [
                    'course_id' => $courseId,
                    'course_code' => $c['code'],
                    'course_name' => $c['name'],
                    'total_score' => $stats['cpi'],
                    'passed_sessions' => $stats['passed_sessions']
                ];
            } else {
                $attendanceSummaryByCourse[] = [
                    'course_id' => $courseId,
                    'course_code' => $c['code'],
                    'course_name' => $c['name'],
                    'passed_sessions' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'attendance_rate' => null, // Chưa có dữ liệu
                    'absent_limit' => $absentLimit,
                    'is_over_limit' => false
                ];

                $cpiByClass[] = [
                    'course_id' => $courseId,
                    'course_code' => $c['code'],
                    'course_name' => $c['name'],
                    'total_score' => null, // Chưa có dữ liệu
                    'passed_sessions' => 0
                ];
            }
        }

        $this->jsonResponse(['status' => 'success', 'data' => [
            'attendance'            => $attendanceHistory,
            'interactions'          => $interactionHistory,
            'cpi_by_class'          => $cpiByClass,
            'attendance_by_course'  => $attendanceSummaryByCourse
        ]]);
    }

    /**
     * GET /api/student/active-session - Tìm buổi học đang mở điểm danh
     */
    public function activeSession() {
        $studentId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT cs.*, c.name as course_name, c.code as course_code,
                   u.full_name as teacher_name,
                   ar.status as my_attendance
            FROM class_sessions cs
            JOIN courses c ON cs.course_id = c.id
            JOIN course_students cstu ON c.id = cstu.course_id AND cstu.student_id = :sid
            LEFT JOIN users u ON c.teacher_id = u.id
            LEFT JOIN attendance_records ar ON cs.id = ar.session_id AND ar.student_id = :sid2
            WHERE cs.status = 'active'
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT 5
        ");
        $stmt->execute(['sid' => $studentId, 'sid2' => $studentId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sessions as &$s) {
            $statusInfo = AttendanceSessionHelper::getStatus($s);
            $s['attendance_status'] = $statusInfo['status'];
            $s['remaining_minutes'] = $statusInfo['remaining_minutes'];
        }
        $this->jsonResponse(['status' => 'success', 'data' => $sessions]);
    }

    /**
     * GET /api/student/quizzes?session_id=X - Lấy quiz của buổi học
     */
    public function quizzesBySession() {
        $studentId = $_SESSION['user']['id'];
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu session_id'], 400);
        }
        $db = Database::getInstance()->getConnection();

        if ($sessionId === 'common') {
            $courseId = $_GET['course_id'] ?? null;
            if (!$courseId) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu course_id cho thảo luận chung'], 400);
            }
            // Kiểm tra SV có trong lớp không
            $stmtCheck = $db->prepare("
                SELECT 1 FROM course_students 
                WHERE course_id = :cid AND student_id = :stud_id
            ");
            $stmtCheck->execute(['cid' => $courseId, 'stud_id' => $studentId]);
            if (!$stmtCheck->fetch()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không có quyền xem thảo luận của lớp này.'], 403);
            }

            // Lấy thảo luận chung của lớp học phần (session_id IS NULL)
            $stmtDisc = $db->prepare("
                SELECT cd.*, u.full_name as creator_name, u.role as creator_role,
                       (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = cd.id) as reply_count,
                       (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = cd.id AND dr.user_id = :stud_id) as my_replies
                FROM class_discussions cd
                JOIN users u ON cd.created_by = u.id
                WHERE cd.course_id = :course_id AND cd.session_id IS NULL
                ORDER BY cd.created_at DESC
            ");
            $stmtDisc->execute(['course_id' => $courseId, 'stud_id' => $studentId]);
            $discussions = $stmtDisc->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(['status' => 'success', 'data' => [
                'quizzes' => [],
                'discussions' => $discussions,
            ]]);
            return;
        }

        // Kiểm tra SV có trong lớp không
        $stmtCheck = $db->prepare("
            SELECT 1 FROM class_sessions cs
            JOIN course_students cstu ON cs.course_id = cstu.course_id
            WHERE cs.id = :sid AND cstu.student_id = :stud_id
        ");
        $stmtCheck->execute(['sid' => $sessionId, 'stud_id' => $studentId]);
        if (!$stmtCheck->fetch()) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không có quyền xem quiz này.'], 403);
        }

        // Lấy danh sách quiz sessions
        $stmtQuiz = $db->prepare("
            SELECT qs.*, 
                   (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = qs.id) as question_count,
                   (SELECT COUNT(*) FROM quiz_submissions qsub WHERE qsub.quiz_id = qs.id AND qsub.student_id = :stud_id) as my_submissions
            FROM quiz_sessions qs
            WHERE qs.session_id = :session_id
            ORDER BY qs.start_time ASC
        ");
        $stmtQuiz->execute(['session_id' => $sessionId, 'stud_id' => $studentId]);
        $quizzes = $stmtQuiz->fetchAll(PDO::FETCH_ASSOC);

        // Lấy thảo luận của buổi
        $stmtDisc = $db->prepare("
            SELECT cd.*, u.full_name as creator_name, u.role as creator_role,
                   (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = cd.id) as reply_count,
                   (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = cd.id AND dr.user_id = :stud_id) as my_replies
            FROM class_discussions cd
            JOIN users u ON cd.created_by = u.id
            WHERE cd.session_id = :session_id
            ORDER BY cd.created_at DESC
        ");
        $stmtDisc->execute(['session_id' => $sessionId, 'stud_id' => $studentId]);
        $discussions = $stmtDisc->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['status' => 'success', 'data' => [
            'quizzes' => $quizzes,
            'discussions' => $discussions,
        ]]);
    }

    /**
     * GET /api/student/dashboard/course-charts - Lấy thống kê chuyên cần tròn và CPI line theo môn học được chọn
     */
    public function courseCharts() {
        $studentId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu course_id'], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();

        try {
            // 1. Thống kê chi tiết chuyên cần (Doughnut Chart)
            $stmtAtt = $db->prepare("
                SELECT 
                    COALESCE(ar.status, 'absent') as status, 
                    COUNT(*) as count
                FROM class_sessions cs
                LEFT JOIN attendance_records ar ON cs.id = ar.session_id AND ar.student_id = ?
                WHERE cs.course_id = ? AND CONCAT(cs.session_date, ' ', cs.end_time) < NOW()
                GROUP BY status
            ");
            $stmtAtt->execute([$studentId, $courseId]);
            $attRows = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
            
            $attendance = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
            foreach ($attRows as $row) {
                $status = $row['status'];
                if ($status === null || $status === '') {
                    $status = 'absent';
                }
                if (array_key_exists($status, $attendance)) {
                    $attendance[$status] += (int)$row['count'];
                } else {
                    $attendance['absent'] += (int)$row['count'];
                }
            }

            // 2. Điểm CPI trung bình làm mốc cơ sở
            $stmtCpi = $db->prepare("
                SELECT total_score 
                FROM engagement_scores 
                WHERE student_id = ? AND course_id = ?
            ");
            $stmtCpi->execute([$studentId, $courseId]);
            $cpiVal = $stmtCpi->fetchColumn();
            $baseCpi = $cpiVal !== false ? (float)$cpiVal : 100.0;

            // 3. Biểu đồ đường: CPI theo thời gian của môn học đó
            // Lấy configs hệ thống trước
            $stmtConfig = $db->prepare("SELECT config_key, config_value FROM system_configs");
            $stmtConfig->execute();
            $sysConfigs = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);

            $chartCpiData = $this->getStudentCourseCpiTrends($db, $studentId, $courseId, $sysConfigs);
            if (empty($chartCpiData)) {
                $cpiDates = [];
                for ($i = 7; $i >= 0; $i--) {
                    $cpiDates[] = date('Y-m-d', strtotime("-$i days"));
                }
                foreach ($cpiDates as $date) {
                    $chartCpiData[] = [
                        'date' => $date,
                        'cpi_score' => null
                    ];
                }
            }

            $this->jsonResponse([
                'status' => 'success',
                'data' => [
                    'attendance' => $attendance,
                    'cpi_trends' => $chartCpiData
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tính toán xu hướng CPI tích lũy của một sinh viên trong một môn học cụ thể
     */
    private function getStudentCourseCpiTrends($db, $studentId, $courseId, $sysConfigs) {
        // 1. Lấy cấu hình môn học
        $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) return [];
        
        $pPts = (int)($course['rule_present_points'] ?? $sysConfigs['default_rule_present_points'] ?? 2);
        $lPts = (int)($course['rule_late_points'] ?? $sysConfigs['default_rule_late_points'] ?? 1);
        $aPts = (int)($course['rule_absent_points'] ?? $sysConfigs['default_rule_absent_points'] ?? 0);
        $iPtsRule = (int)($course['rule_interaction_points'] ?? $sysConfigs['default_rule_interaction_points'] ?? 1);
        $attWeight = (int)($course['rule_attendance_weight'] ?? $sysConfigs['default_rule_attendance_weight'] ?? 50);
        $quizWeight = (int)($course['rule_quiz_weight'] ?? $sysConfigs['default_rule_quiz_weight'] ?? 50);

        // 2. Lấy các buổi học đã diễn ra sắp xếp tăng dần theo thời gian
        $stmtSessions = $db->prepare("
            SELECT id, session_date 
            FROM class_sessions 
            WHERE course_id = ? AND CONCAT(session_date, ' ', end_time) < NOW()
            ORDER BY session_date ASC, start_time ASC
        ");
        $stmtSessions->execute([$courseId]);
        $sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);
        if (empty($sessions)) return [];

        // 3. Lấy tất cả attendance records của học sinh này trong các session completed
        $sessionIds = array_column($sessions, 'id');
        $inClause = implode(',', array_fill(0, count($sessionIds), '?'));
        
        $stmtAtt = $db->prepare("
            SELECT session_id, status 
            FROM attendance_records 
            WHERE student_id = ? AND session_id IN ($inClause)
        ");
        $stmtAtt->execute(array_merge([$studentId], $sessionIds));
        $attRecords = $stmtAtt->fetchAll(PDO::FETCH_KEY_PAIR); // session_id => status

        // 4. Lấy tất cả quiz submissions và total marks
        $stmtQuiz = $db->prepare("
            SELECT qz.session_id, qs.score, qz.total_marks
            FROM quiz_submissions qs
            JOIN quiz_sessions qz ON qs.quiz_id = qz.id
            WHERE qs.student_id = ? AND qz.session_id IN ($inClause)
        ");
        $stmtQuiz->execute(array_merge([$studentId], $sessionIds));
        $quizSubmissions = $stmtQuiz->fetchAll(PDO::FETCH_ASSOC);
        
        $quizData = [];
        foreach ($quizSubmissions as $q) {
            $quizData[$q['session_id']][] = [
                'score' => (float)$q['score'],
                'total_marks' => (float)$q['total_marks']
            ];
        }

        // 5. Lấy tất cả interaction logs
        $stmtInt = $db->prepare("
            SELECT session_id, points_awarded 
            FROM interaction_logs 
            WHERE student_id = ? AND session_id IN ($inClause)
        ");
        $stmtInt->execute(array_merge([$studentId], $sessionIds));
        $interactions = $stmtInt->fetchAll(PDO::FETCH_ASSOC);
        
        $intData = [];
        foreach ($interactions as $i) {
            $intData[$i['session_id']][] = (int)$i['points_awarded'];
        }

        // 6. Tính CPI tích lũy qua từng buổi
        $passedSessions = 0;
        $attPoints = 0;
        $quizScoreSum = 0;
        $quizMaxSum = 0;
        $intPoints = 0;
        
        $trends = [];
        foreach ($sessions as $sess) {
            $sessId = $sess['id'];
            $sessDate = $sess['session_date'];
            
            $passedSessions++;
            
            // Điểm danh
            $status = $attRecords[$sessId] ?? 'absent';
            $pts = $aPts;
            if ($status === 'present') $pts = $pPts;
            elseif ($status === 'late') $pts = $lPts;
            $attPoints += $pts;
            
            // Quiz
            if (isset($quizData[$sessId])) {
                foreach ($quizData[$sessId] as $q) {
                    $quizScoreSum += $q['score'];
                    $quizMaxSum += $q['total_marks'];
                }
            }
            
            // Tương tác
            if (isset($intData[$sessId])) {
                foreach ($intData[$sessId] as $ptsAwarded) {
                    $intPoints += $ptsAwarded;
                }
            }
            
            // Tính CPI
            $maxAttPoints = $passedSessions * $pPts;
            $normalizedAttScore = ($maxAttPoints > 0) ? ($attPoints / $maxAttPoints) * 100 : 100;

            $normalizedQuizScore = 100;
            if ($quizMaxSum > 0) {
                $normalizedQuizScore = ($quizScoreSum / $quizMaxSum) * 100;
            }

            $bonusPoints = $intPoints * $iPtsRule * 2;
            if ($bonusPoints > 10) $bonusPoints = 10;

            $cpi = ($normalizedAttScore * ($attWeight / 100)) + ($normalizedQuizScore * ($quizWeight / 100)) + $bonusPoints;
            if ($cpi > 100) $cpi = 100;
            if ($cpi < 0) $cpi = 0;
            
            $trends[] = [
                'session_id' => $sessId,
                'date' => $sessDate,
                'cpi_score' => round($cpi, 1)
            ];
        }
        
        return $trends;
    }

    /**
     * Tính toán xu hướng CPI tích lũy tổng thể của sinh viên qua tất cả các môn học
     */
    private function getStudentOverallCpiTrends($db, $studentId, $courses, $sysConfigs) {
        $allTrends = [];
        foreach ($courses as $c) {
            $courseTrends = $this->getStudentCourseCpiTrends($db, $studentId, $c['id'], $sysConfigs);
            if (!empty($courseTrends)) {
                $allTrends[$c['id']] = $courseTrends;
            }
        }
        
        if (empty($allTrends)) return [];
        
        // Gom tất cả các ngày
        $dates = [];
        foreach ($allTrends as $courseId => $trends) {
            foreach ($trends as $t) {
                $dates[] = $t['date'];
            }
        }
        $dates = array_unique($dates);
        sort($dates);
        
        // Lấy 8 điểm dữ liệu gần đây nhất (hoặc tất cả nếu ít hơn 8)
        if (count($dates) > 8) {
            $dates = array_slice($dates, -8);
        }
        
        $overallTrends = [];
        foreach ($dates as $date) {
            $cpiSum = 0;
            $cpiCount = 0;
            
            foreach ($allTrends as $courseId => $trends) {
                $lastCpi = null;
                foreach ($trends as $t) {
                    if ($t['date'] <= $date) {
                        $lastCpi = $t['cpi_score'];
                    } else {
                        break;
                    }
                }
                if ($lastCpi !== null) {
                    $cpiSum += $lastCpi;
                    $cpiCount++;
                }
            }
            
            if ($cpiCount > 0) {
                $overallTrends[] = [
                    'date' => $date,
                    'cpi_score' => round($cpiSum / $cpiCount, 1)
                ];
            }
        }
        
        return $overallTrends;
    }
}
