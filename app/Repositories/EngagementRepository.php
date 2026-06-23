<?php
// app/Repositories/EngagementRepository.php
require_once 'BaseRepository.php';

class EngagementRepository extends BaseRepository {

    public function getScore($courseId, $studentId) {
        $stmt = $this->model->db->prepare("
            SELECT * FROM {$this->model->table} 
            WHERE course_id = :course_id AND student_id = :student_id
        ");
        $stmt->execute(['course_id' => $courseId, 'student_id' => $studentId]);
        $score = $stmt->fetch();
        
        if (!$score) {
            // Khởi tạo mặc định nếu chưa tồn tại
            $this->model->db->prepare("
                INSERT INTO {$this->model->table} (course_id, student_id, attendance_points, interaction_points, total_score)
                VALUES (:course_id, :student_id, 0, 0, 100)
            ")->execute(['course_id' => $courseId, 'student_id' => $studentId]);
            
            return [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'attendance_points' => 0,
                'interaction_points' => 0,
                'total_score' => 100
            ];
        }
        return $score;
    }

    public function getScoresByCourse($courseId) {
        $stmt = $this->model->db->prepare("
            SELECT es.*, u.full_name as student_name, u.email as student_email
            FROM {$this->model->table} es
            JOIN users u ON es.student_id = u.id
            WHERE es.course_id = :course_id
            ORDER BY es.total_score DESC
        ");
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Tính toán lại và đồng bộ điểm số cho một sinh viên trong một khóa học
     */
    public function recalculateScore($courseId, $studentId) {
        $db = $this->model->db;
        
        // 1. Lấy thông tin cấu hình rules của khóa học
        $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();
        if (!$course) return false;

        // Lấy cấu hình mặc định từ hệ thống làm phương án dự phòng (fallback)
        $stmtConfig = $db->prepare("SELECT config_key, config_value FROM system_configs");
        $stmtConfig->execute();
        $sysConfigs = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);

        $defPPts = (int)($sysConfigs['default_rule_present_points'] ?? 2);
        $defLPts = (int)($sysConfigs['default_rule_late_points'] ?? 1);
        $defAPts = (int)($sysConfigs['default_rule_absent_points'] ?? 0);
        $defIPts = (int)($sysConfigs['default_rule_interaction_points'] ?? 1);
        $defAttW = (int)($sysConfigs['default_rule_attendance_weight'] ?? 50);
        $defQuizW = (int)($sysConfigs['default_rule_quiz_weight'] ?? 50);

        $pPts = isset($course['rule_present_points']) ? (int)$course['rule_present_points'] : $defPPts;
        $lPts = isset($course['rule_late_points']) ? (int)$course['rule_late_points'] : $defLPts;
        $aPts = isset($course['rule_absent_points']) ? (int)$course['rule_absent_points'] : $defAPts;
        $iPtsRule = isset($course['rule_interaction_points']) ? (int)$course['rule_interaction_points'] : $defIPts;
        $attWeight = isset($course['rule_attendance_weight']) ? (int)$course['rule_attendance_weight'] : $defAttW;
        $quizWeight = isset($course['rule_quiz_weight']) ? (int)$course['rule_quiz_weight'] : $defQuizW;

        // 2. Lấy số lượng buổi học của khóa học có trạng thái 'completed' hoặc 'active'
        $stmt = $db->prepare("SELECT COUNT(*) FROM class_sessions WHERE course_id = ? AND status IN ('completed', 'active')");
        $stmt->execute([$courseId]);
        $totalSessions = (int)$stmt->fetchColumn();

        // 3. Tính điểm danh thực tế
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN ar.status = 'present' THEN :present_pts 
                         WHEN ar.status = 'late' THEN :late_pts 
                         ELSE :absent_pts END) as actual_pts
            FROM class_sessions cs
            JOIN attendance_records ar ON cs.id = ar.session_id
            WHERE cs.course_id = :course_id AND ar.student_id = :student_id AND cs.status IN ('completed', 'active')
        ");
        $stmt->execute([
            'present_pts' => $pPts,
            'late_pts' => $lPts,
            'absent_pts' => $aPts,
            'course_id' => $courseId,
            'student_id' => $studentId
        ]);
        $actualAttPoints = (int)$stmt->fetchColumn();

        // Tính điểm chuyên cần chuẩn hóa (thang điểm 100)
        $maxAttPoints = $totalSessions * $pPts;
        $normalizedAttScore = ($maxAttPoints > 0) ? ($actualAttPoints / $maxAttPoints) * 100 : 100;

        // 4. Tính điểm phát biểu tương tác
        $stmt = $db->prepare("
            SELECT SUM(il.points_awarded) 
            FROM interaction_logs il
            JOIN class_sessions cs ON il.session_id = cs.id
            WHERE cs.course_id = ? AND il.student_id = ?
        ");
        $stmt->execute([$courseId, $studentId]);
        $interactionPoints = (int)$stmt->fetchColumn();

        // 5. Tính điểm Quiz trung bình
        $stmt = $db->prepare("
            SELECT SUM(qs.score) as student_total_score, SUM(qz.total_marks) as max_possible_score
            FROM quiz_submissions qs
            JOIN quiz_sessions qz ON qs.quiz_id = qz.id
            JOIN class_sessions cs ON qz.session_id = cs.id
            WHERE cs.course_id = ? AND qs.student_id = ?
        ");
        $stmt->execute([$courseId, $studentId]);
        $quizData = $stmt->fetch();
        
        $normalizedQuizScore = 100;
        if ($quizData && $quizData['max_possible_score'] > 0) {
            $normalizedQuizScore = ($quizData['student_total_score'] / $quizData['max_possible_score']) * 100;
        }

        // 6. Tính tổng điểm CPI (Class Participation Index)
        // CPI = Điểm danh hệ số * Trọng số + Điểm Quiz hệ số * Trọng số + Điểm thưởng phát biểu (mỗi điểm phát biểu +2%, tối đa 10%)
        $bonusPoints = $interactionPoints * $iPtsRule * 2; // Ví dụ: 1 lần phát biểu được thưởng 2% CPI
        if ($bonusPoints > 10) $bonusPoints = 10; // tối đa cộng 10%

        $totalScore = ($normalizedAttScore * ($attWeight / 100)) + ($normalizedQuizScore * ($quizWeight / 100)) + $bonusPoints;
        if ($totalScore > 100) $totalScore = 100;
        if ($totalScore < 0) $totalScore = 0;

        $totalScore = round($totalScore);

        // 7. Cập nhật vào bảng engagement_scores
        $stmtUpdate = $db->prepare("
            INSERT INTO {$this->model->table} (course_id, student_id, attendance_points, interaction_points, total_score)
            VALUES (:course_id, :student_id, :attendance_points, :interaction_points, :total_score)
            ON DUPLICATE KEY UPDATE 
                attendance_points = :att_points_up,
                interaction_points = :int_points_up,
                total_score = :total_score_up
        ");
        $res = $stmtUpdate->execute([
            'course_id' => $courseId,
            'student_id' => $studentId,
            'attendance_points' => $actualAttPoints,
            'interaction_points' => $interactionPoints,
            'total_score' => $totalScore,
            'att_points_up' => $actualAttPoints,
            'int_points_up' => $interactionPoints,
            'total_score_up' => $totalScore
        ]);

        if ($res) {
            require_once __DIR__ . '/../Helpers/AttendanceStatsHelper.php';
            require_once __DIR__ . '/../Helpers/AlertEngine.php';
            AlertEngine::checkStudent($studentId, $courseId, true);
        }

        return $res;
    }

    /**
     * Đồng bộ điểm chuyên cần cho toàn bộ sinh viên đăng ký lớp học phần đó
     */
    public function syncCourseEngagement($courseId) {
        $db = $this->model->db;
        $stmt = $db->prepare("SELECT student_id FROM course_students WHERE course_id = ?");
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll();
        
        $success = true;
        foreach ($students as $stud) {
            if (!$this->recalculateScore($courseId, $stud['student_id'])) {
                $success = false;
            }
        }
        return $success;
    }
}
