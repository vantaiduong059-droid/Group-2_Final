<?php
// app/Helpers/AttendanceStatsHelper.php

class AttendanceStatsHelper {
    /**
     * Tính toán các số liệu thống kê chuyên cần và CPI của một sinh viên trong một môn học
     * Trả về array chứa các chỉ số hoặc null nếu chưa có buổi học nào diễn ra.
     */
    public static function getStudentStats($studentId, $courseId) {
        $db = Database::getInstance()->getConnection();

        // 1. Số buổi đã diễn ra (passed sessions)
        $stmtPassed = $db->prepare("
            SELECT COUNT(*) 
            FROM class_sessions 
            WHERE course_id = ? AND status IN ('completed', 'active')
        ");
        $stmtPassed->execute([$courseId]);
        $passedSessions = (int)$stmtPassed->fetchColumn();

        if ($passedSessions === 0) {
            return null; // Chưa có dữ liệu
        }

        // 2. Số buổi có mặt (present)
        $stmtPres = $db->prepare("
            SELECT COUNT(*) 
            FROM attendance_records ar
            JOIN class_sessions cs ON ar.session_id = cs.id
            WHERE cs.course_id = ? AND ar.student_id = ? AND ar.status = 'present'
              AND cs.status IN ('completed', 'active')
        ");
        $stmtPres->execute([$courseId, $studentId]);
        $present = (int)$stmtPres->fetchColumn();

        // 3. Số buổi đi muộn (late)
        $stmtLate = $db->prepare("
            SELECT COUNT(*) 
            FROM attendance_records ar
            JOIN class_sessions cs ON ar.session_id = cs.id
            WHERE cs.course_id = ? AND ar.student_id = ? AND ar.status = 'late'
              AND cs.status IN ('completed', 'active')
        ");
        $stmtLate->execute([$courseId, $studentId]);
        $late = (int)$stmtLate->fetchColumn();

        // 4. Số buổi vắng (absent)
        $absent = $passedSessions - $present - $late;

        // 5. Tỷ lệ chuyên cần (%)
        $attendanceRate = round(($present + $late) / $passedSessions * 100, 1);

        // 6. Điểm CPI (%) lấy từ bảng engagement_scores
        $stmtCpi = $db->prepare("
            SELECT total_score 
            FROM engagement_scores 
            WHERE course_id = ? AND student_id = ?
        ");
        $stmtCpi->execute([$courseId, $studentId]);
        $cpiVal = $stmtCpi->fetchColumn();
        
        $cpi = $cpiVal !== false ? (float)$cpiVal : 100.0;

        return [
            'passed_sessions' => $passedSessions,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'attendance_rate' => $attendanceRate,
            'cpi' => $cpi
        ];
    }
}
