<?php
// app/Controllers/Api/TeacherDashboardApiController.php
require_once '../core/Controller.php';

class TeacherDashboardApiController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
    }

    /**
     * GET /api/teacher/dashboard
     * Lấy dữ liệu thống kê tổng quan cho Dashboard Giảng viên
     */
    public function summary() {
        $teacherId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        try {
            // 1. Số lớp đang phụ trách
            $stmtCourses = $db->prepare("SELECT COUNT(*) as cnt FROM courses WHERE teacher_id = ?");
            $stmtCourses->execute([$teacherId]);
            $coursesCount = (int)$stmtCourses->fetchColumn();

            // 2. Tổng số sinh viên duy nhất
            $stmtTotalStudents = $db->prepare("
                SELECT COUNT(DISTINCT student_id) 
                FROM course_students cs 
                JOIN courses c ON cs.course_id = c.id 
                WHERE c.teacher_id = ?
            ");
            $stmtTotalStudents->execute([$teacherId]);
            $totalStudentsCount = (int)$stmtTotalStudents->fetchColumn();

            // 3. Số buổi học hôm nay
            $stmtTodaySessions = $db->prepare("
                SELECT COUNT(*) 
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                WHERE c.teacher_id = ? AND cs.session_date = CURDATE()
            ");
            $stmtTodaySessions->execute([$teacherId]);
            $todaySessionsCount = (int)$stmtTodaySessions->fetchColumn();

            // 4. Số SV đã điểm danh hôm nay / tổng số SV cần điểm danh
            $stmtTodaySessIds = $db->prepare("
                SELECT cs.id, cs.course_id 
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                WHERE c.teacher_id = ? AND cs.session_date = CURDATE()
            ");
            $stmtTodaySessIds->execute([$teacherId]);
            $todaySessions = $stmtTodaySessIds->fetchAll(PDO::FETCH_ASSOC);

            $todayAttendedCount = 0;
            $todayTotalNeededCount = 0;

            foreach ($todaySessions as $ts) {
                // SV cần điểm danh của lớp này
                $stmtNeeded = $db->prepare("SELECT COUNT(*) FROM course_students WHERE course_id = ?");
                $stmtNeeded->execute([$ts['course_id']]);
                $todayTotalNeededCount += (int)$stmtNeeded->fetchColumn();

                // SV đã có bản ghi điểm danh (có mặt, đi muộn, vắng) của session này
                $stmtAtt = $db->prepare("SELECT COUNT(*) FROM attendance_records WHERE session_id = ?");
                $stmtAtt->execute([$ts['id']]);
                $todayAttendedCount += (int)$stmtAtt->fetchColumn();
            }

            // Đếm số buổi học thực tế đã diễn ra của giảng viên
            $stmtPassedSessions = $db->prepare("
                SELECT COUNT(*) 
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                WHERE c.teacher_id = ? AND CONCAT(cs.session_date, ' ', cs.end_time) < NOW()
            ");
            $stmtPassedSessions->execute([$teacherId]);
            $passedSessionsCount = (int)$stmtPassedSessions->fetchColumn();

            // 5. Tỷ lệ chuyên cần trung bình & 6. CPI trung bình tổng hợp từ Helper chung
            $avgAttendanceRate = null;
            $avgCpi = null;
            
            if ($passedSessionsCount > 0) {
                $stmtMyCourses = $db->prepare("SELECT id FROM courses WHERE teacher_id = ?");
                $stmtMyCourses->execute([$teacherId]);
                $myCourseIds = $stmtMyCourses->fetchAll(PDO::FETCH_COLUMN);

                $totalPassedSessions = 0;
                $totalAttended = 0;
                $cpiSum = 0;
                $cpiCount = 0;

                foreach ($myCourseIds as $cid) {
                    $stmtStuds = $db->prepare("SELECT student_id FROM course_students WHERE course_id = ?");
                    $stmtStuds->execute([$cid]);
                    $students = $stmtStuds->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($students as $sid) {
                        $stats = AttendanceStatsHelper::getStudentStats($sid, $cid);
                        if ($stats) {
                            $totalPassedSessions += $stats['passed_sessions'];
                            $totalAttended += ($stats['present'] + $stats['late']);
                            $cpiSum += $stats['cpi'];
                            $cpiCount++;
                        }
                    }
                }
                $avgAttendanceRate = $totalPassedSessions > 0 ? round(($totalAttended / $totalPassedSessions) * 100, 1) : 100.0;
                $avgCpi = $cpiCount > 0 ? round($cpiSum / $cpiCount, 1) : 0.0;
            }

            // 7. Số SV duy nhất đang bị cảnh báo chưa xử lý
            $stmtFlagged = $db->prepare("
                SELECT COUNT(DISTINCT a.user_id) 
                FROM alerts a 
                JOIN courses c ON a.course_id = c.id 
                WHERE c.teacher_id = ? AND a.status = 'pending'
            ");
            $stmtFlagged->execute([$teacherId]);
            $flaggedCount = (int)$stmtFlagged->fetchColumn();

            // 8. Buổi học đang diễn ra (ongoing) hôm nay
            $stmtOngoing = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code 
                FROM class_sessions cs 
                JOIN courses c ON cs.course_id = c.id 
                WHERE c.teacher_id = ? 
                  AND cs.session_date = CURDATE()
                  AND cs.status != 'completed'
                ORDER BY cs.start_time ASC
            ");
            $stmtOngoing->execute([$teacherId]);
            $ongoingSessionsList = $stmtOngoing->fetchAll(PDO::FETCH_ASSOC);

            // 9. Danh sách phiên điểm danh đang mở (active session)
            $stmtActive = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code 
                FROM class_sessions cs 
                JOIN courses c ON cs.course_id = c.id 
                WHERE c.teacher_id = ? AND cs.status = 'active'
                ORDER BY cs.session_date DESC, cs.start_time DESC
            ");
            $stmtActive->execute([$teacherId]);
            $activeSessionsList = $stmtActive->fetchAll(PDO::FETCH_ASSOC);
            foreach ($activeSessionsList as &$s) {
                $statusInfo = AttendanceSessionHelper::getStatus($s);
                $s['attendance_status'] = $statusInfo['status'];
                $s['remaining_minutes'] = $statusInfo['remaining_minutes'];
            }

            // 10. Top 5 SV cần chú ý
            $stmtTopRisk = $db->prepare("
                SELECT es.student_id, u.full_name as student_name, u.username as student_code,
                       c.code as course_code, c.name as course_name, es.total_score as cpi_score,
                       (
                           SELECT COUNT(ar.id)
                           FROM attendance_records ar
                           JOIN class_sessions cs ON ar.session_id = cs.id
                           WHERE ar.student_id = es.student_id AND cs.course_id = es.course_id AND ar.status = 'absent'
                       ) as absent_count
                FROM engagement_scores es
                JOIN users u ON es.student_id = u.id
                JOIN courses c ON es.course_id = c.id
                WHERE c.teacher_id = ?
                HAVING absent_count > 0 OR cpi_score < 75
                ORDER BY es.total_score ASC, absent_count DESC
                LIMIT 5
            ");
            $stmtTopRisk->execute([$teacherId]);
            $topRiskList = $stmtTopRisk->fetchAll(PDO::FETCH_ASSOC);

            // 11. Buổi học sắp tới
            $stmtUpcoming = $db->prepare("
                SELECT cs.*, c.name as course_name, c.code as course_code 
                FROM class_sessions cs 
                JOIN courses c ON cs.course_id = c.id 
                WHERE c.teacher_id = ? 
                  AND (cs.session_date > CURDATE() OR (cs.session_date = CURDATE() AND cs.end_time >= CURTIME())) 
                ORDER BY cs.session_date ASC, cs.start_time ASC 
                LIMIT 1
            ");
            $stmtUpcoming->execute([$teacherId]);
            $upcomingSession = $stmtUpcoming->fetch(PDO::FETCH_ASSOC);

            // 12. Thông báo
            $stmtNoti = $db->prepare("
                SELECT id, title, message, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmtNoti->execute([$teacherId]);
            $notificationsList = $stmtNoti->fetchAll(PDO::FETCH_ASSOC);

            // 13. Biểu đồ 1: Tỷ lệ chuyên cần theo lớp
            $stmtChartAttendance = $db->prepare("
                SELECT c.code as course_code, c.name as course_name,
                       COUNT(ar.id) as total,
                       SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended
                FROM courses c
                JOIN class_sessions cs ON c.id = cs.course_id
                LEFT JOIN attendance_records ar ON cs.id = ar.session_id
                WHERE c.teacher_id = ? AND CONCAT(cs.session_date, ' ', cs.end_time) < NOW()
                GROUP BY c.id
            ");
            $stmtChartAttendance->execute([$teacherId]);
            $chartAttendanceData = $stmtChartAttendance->fetchAll(PDO::FETCH_ASSOC);

            // 14. Biểu đồ 2: Số SV đi học theo từng buổi (10 buổi gần nhất)
            $stmtChartSessions = $db->prepare("
                SELECT cs.session_date, cs.start_time, c.code as course_code,
                       SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended_count
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                LEFT JOIN attendance_records ar ON cs.id = ar.session_id
                WHERE c.teacher_id = ? AND CONCAT(cs.session_date, ' ', cs.end_time) < NOW()
                GROUP BY cs.id
                ORDER BY cs.session_date DESC, cs.start_time DESC
                LIMIT 10
            ");
            $stmtChartSessions->execute([$teacherId]);
            $chartSessionsData = array_reverse($stmtChartSessions->fetchAll(PDO::FETCH_ASSOC));

            // 15. Biểu đồ 3: CPI trung bình theo thời gian
            $chartCpiData = [];
            
            // Lấy configs hệ thống trước
            $stmtConfig = $db->prepare("SELECT config_key, config_value FROM system_configs");
            $stmtConfig->execute();
            $sysConfigs = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $stmtMyCourses = $db->prepare("SELECT id FROM courses WHERE teacher_id = ?");
            $stmtMyCourses->execute([$teacherId]);
            $myCourseIds = $stmtMyCourses->fetchAll(PDO::FETCH_COLUMN);
            
            $allCourseTrends = [];
            foreach ($myCourseIds as $cid) {
                $trends = $this->getCourseCpiTrends($db, $cid, $sysConfigs);
                if (!empty($trends)) {
                    $allCourseTrends[$cid] = $trends;
                }
            }
            
            if (empty($allCourseTrends)) {
                // Tạo danh sách 8 ngày gần đây nhưng giá trị CPI đều là null để không vẽ đường giả
                $cpiDates = [];
                for ($i = 7; $i >= 0; $i--) {
                    $cpiDates[] = date('Y-m-d', strtotime("-$i days"));
                }
                foreach ($cpiDates as $date) {
                    $chartCpiData[] = [
                        'date' => $date,
                        'avg_cpi' => null
                    ];
                }
            } else {
                // Gom tất cả các ngày có buổi học completed
                $dates = [];
                foreach ($allCourseTrends as $cid => $trends) {
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
                
                foreach ($dates as $date) {
                    $cpiSum = 0;
                    $cpiCount = 0;
                    
                    foreach ($allCourseTrends as $cid => $trends) {
                        $lastCpi = null;
                        foreach ($trends as $t) {
                            if ($t['date'] <= $date) {
                                $lastCpi = $t['avg_cpi'];
                            } else {
                                break;
                            }
                        }
                        if ($lastCpi !== null) {
                            $cpiSum += $lastCpi;
                            $cpiCount++;
                        }
                    }
                    
                    $val = $cpiCount > 0 ? round($cpiSum / $cpiCount, 1) : null;
                    $chartCpiData[] = [
                        'date' => $date,
                        'avg_cpi' => $val
                    ];
                }
            }

            // Phản hồi JSON thành công
            $this->jsonResponse([
                'status' => 'success',
                'data' => [
                    'teacher_info' => [
                        'full_name' => $_SESSION['user']['full_name'],
                        'username' => $_SESSION['user']['username'],
                        'email' => $_SESSION['user']['email'] ?? 'gv@example.com',
                        'avatar_url' => $_SESSION['user']['avatar_url'] ?? null,
                        'semester' => 'Học kỳ hè (2025-2026)',
                        'courses_count' => $coursesCount
                    ],
                    'reminders' => [
                        'pending_alerts_count' => $flaggedCount,
                        'upcoming_session' => $upcomingSession ?: null
                    ],
                    'stats' => [
                        'total_students' => $totalStudentsCount,
                        'today_sessions_count' => $todaySessionsCount,
                        'today_attended_students' => $todayAttendedCount,
                        'today_total_students_needed' => $todayTotalNeededCount,
                        'avg_attendance_rate' => $avgAttendanceRate,
                        'avg_cpi' => $avgCpi,
                        'flagged_students_count' => $flaggedCount,
                        'passed_sessions_count' => $passedSessionsCount
                    ],
                    'active_sessions' => $activeSessionsList,
                    'ongoing_sessions' => $ongoingSessionsList,
                    'top_risk_students' => $topRiskList,
                    'notifications' => $notificationsList,
                    'charts' => [
                        'attendance_by_course' => $chartAttendanceData,
                        'attended_by_session' => $chartSessionsData,
                        'cpi_trends' => $chartCpiData
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tính toán xu hướng CPI tích lũy của một khóa học qua các buổi học đã hoàn thành
     */
    private function getCourseCpiTrends($db, $courseId, $sysConfigs) {
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

        // 2. Lấy danh sách SV
        $stmtStuds = $db->prepare("SELECT student_id FROM course_students WHERE course_id = ?");
        $stmtStuds->execute([$courseId]);
        $studentIds = $stmtStuds->fetchAll(PDO::FETCH_COLUMN);
        if (empty($studentIds)) return [];

        // 3. Lấy danh sách các buổi học đã diễn ra sắp xếp tăng dần theo thời gian
        $stmtSessions = $db->prepare("
            SELECT id, session_date 
            FROM class_sessions 
            WHERE course_id = ? AND CONCAT(session_date, ' ', end_time) < NOW()
            ORDER BY session_date ASC, start_time ASC
        ");
        $stmtSessions->execute([$courseId]);
        $sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);
        if (empty($sessions)) return [];

        // 4. Lấy tất cả attendance records của các session completed này
        $sessionIds = array_column($sessions, 'id');
        $inClause = implode(',', array_fill(0, count($sessionIds), '?'));
        
        $stmtAtt = $db->prepare("
            SELECT session_id, student_id, status 
            FROM attendance_records 
            WHERE session_id IN ($inClause)
        ");
        $stmtAtt->execute($sessionIds);
        $attRows = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
        $attRecords = [];
        foreach ($attRows as $r) {
            $attRecords[$r['session_id']][$r['student_id']] = $r['status'];
        }

        // 5. Lấy tất cả quiz submissions và total marks của quiz
        $stmtQuiz = $db->prepare("
            SELECT qz.session_id, qs.student_id, qs.score, qz.total_marks
            FROM quiz_submissions qs
            JOIN quiz_sessions qz ON qs.quiz_id = qz.id
            WHERE qz.session_id IN ($inClause)
        ");
        $stmtQuiz->execute($sessionIds);
        $quizSubmissions = $stmtQuiz->fetchAll(PDO::FETCH_ASSOC);
        $quizData = [];
        foreach ($quizSubmissions as $q) {
            $quizData[$q['session_id']][$q['student_id']][] = [
                'score' => (float)$q['score'],
                'total_marks' => (float)$q['total_marks']
            ];
        }

        // 6. Lấy tất cả interaction logs
        $stmtInt = $db->prepare("
            SELECT session_id, student_id, points_awarded 
            FROM interaction_logs 
            WHERE session_id IN ($inClause)
        ");
        $stmtInt->execute($sessionIds);
        $interactions = $stmtInt->fetchAll(PDO::FETCH_ASSOC);
        $intData = [];
        foreach ($interactions as $i) {
            $intData[$i['session_id']][$i['student_id']][] = (int)$i['points_awarded'];
        }

        // 7. Tính CPI tích lũy qua từng buổi học cho từng học sinh
        $studAccum = [];
        foreach ($studentIds as $sid) {
            $studAccum[$sid] = [
                'passed_sessions' => 0,
                'att_points' => 0,
                'quiz_score_sum' => 0,
                'quiz_max_sum' => 0,
                'int_points' => 0
            ];
        }

        $sessionTrends = [];
        foreach ($sessions as $sess) {
            $sessId = $sess['id'];
            $sessDate = $sess['session_date'];
            
            $cpiSum = 0;
            $cpiCount = 0;

            foreach ($studentIds as $sid) {
                $accum = &$studAccum[$sid];
                $accum['passed_sessions']++;

                // Điểm danh
                $status = $attRecords[$sessId][$sid] ?? 'absent';
                $pts = $aPts;
                if ($status === 'present') $pts = $pPts;
                elseif ($status === 'late') $pts = $lPts;
                $accum['att_points'] += $pts;

                // Quiz
                if (isset($quizData[$sessId][$sid])) {
                    foreach ($quizData[$sessId][$sid] as $q) {
                        $accum['quiz_score_sum'] += $q['score'];
                        $accum['quiz_max_sum'] += $q['total_marks'];
                    }
                }

                // Tương tác
                if (isset($intData[$sessId][$sid])) {
                    foreach ($intData[$sessId][$sid] as $ptsAwarded) {
                        $accum['int_points'] += $ptsAwarded;
                    }
                }

                // Tính CPI
                $maxAttPoints = $accum['passed_sessions'] * $pPts;
                $normalizedAttScore = ($maxAttPoints > 0) ? ($accum['att_points'] / $maxAttPoints) * 100 : 100;

                $normalizedQuizScore = 100;
                if ($accum['quiz_max_sum'] > 0) {
                    $normalizedQuizScore = ($accum['quiz_score_sum'] / $accum['quiz_max_sum']) * 100;
                }

                $bonusPoints = $accum['int_points'] * $iPtsRule * 2;
                if ($bonusPoints > 10) $bonusPoints = 10;

                $cpi = ($normalizedAttScore * ($attWeight / 100)) + ($normalizedQuizScore * ($quizWeight / 100)) + $bonusPoints;
                if ($cpi > 100) $cpi = 100;
                if ($cpi < 0) $cpi = 0;

                $cpiSum += $cpi;
                $cpiCount++;
            }

            $avgCpiVal = $cpiCount > 0 ? round($cpiSum / $cpiCount, 1) : 0.0;
            $sessionTrends[] = [
                'session_id' => $sessId,
                'date' => $sessDate,
                'avg_cpi' => $avgCpiVal
            ];
        }

        return $sessionTrends;
    }
}
