<?php
// app/Helpers/AlertEngine.php

class AlertEngine {
    /**
     * Kiểm tra trạng thái học tập của sinh viên và trả về các cảnh báo nếu vi phạm ngưỡng.
     * Đồng thời tự động ghi nhận vào database nếu tham số $persist là true.
     */
    public static function checkStudent($studentId, $courseId, $persist = false) {
        $db = Database::getInstance()->getConnection();
        
        // 1. Lấy thống kê từ AttendanceStatsHelper
        $stats = AttendanceStatsHelper::getStudentStats($studentId, $courseId);
        if (!$stats) {
            return []; // Chưa có buổi học nào diễn ra
        }
        
        // 2. Lấy cấu hình ngưỡng cảnh báo
        $stmtCourse = $db->prepare("SELECT rule_absent_limit, rule_low_cpi_threshold FROM courses WHERE id = ?");
        $stmtCourse->execute([$courseId]);
        $courseRules = $stmtCourse->fetch(PDO::FETCH_ASSOC);

        $stmtDefault = $db->prepare("SELECT config_key, config_value FROM system_configs WHERE config_key IN ('default_absent_limit', 'default_low_cpi_threshold')");
        $stmtDefault->execute();
        $defaults = $stmtDefault->fetchAll(PDO::FETCH_KEY_PAIR);

        $absentLimit = ($courseRules['rule_absent_limit'] !== null) ? (int)$courseRules['rule_absent_limit'] : (int)($defaults['default_absent_limit'] ?? 3);
        $cpiThreshold = ($courseRules['rule_low_cpi_threshold'] !== null) ? (int)$courseRules['rule_low_cpi_threshold'] : (int)($defaults['default_low_cpi_threshold'] ?? 50);

        $alerts = [];

        // 3. Kiểm tra số buổi vắng
        if ($stats['absent'] > $absentLimit) {
            $msg = "Sinh viên vắng học quá nhiều: Đã vắng {$stats['absent']} buổi học trong lớp phần này!";
            $alerts[] = [
                'type' => 'absent',
                'message' => $msg,
                'current_value' => $stats['absent'],
                'threshold' => $absentLimit
            ];
            if ($persist) {
                self::createAlertIfNotExists($db, $studentId, $courseId, $msg);
            }
        }

        // 4. Kiểm tra điểm CPI
        if ($stats['cpi'] < $cpiThreshold) {
            $msg = "Cảnh báo học tập: Chỉ số tham gia lớp học (CPI) hiện tại quá thấp ({$stats['cpi']}/100). Cần cải thiện chuyên cần và phát biểu!";
            $alerts[] = [
                'type' => 'low_cpi',
                'message' => $msg,
                'current_value' => $stats['cpi'],
                'threshold' => $cpiThreshold
            ];
            if ($persist) {
                self::createAlertIfNotExists($db, $studentId, $courseId, $msg);
            }
        }

        return $alerts;
    }

    private static function createAlertIfNotExists($db, $studentId, $courseId, $message) {
        // Kiểm tra xem đã cảnh báo với thông điệp tương tự chưa để tránh spam
        $stmtCheck = $db->prepare("
            SELECT id FROM alerts 
            WHERE user_id = ? AND course_id = ? AND message = ? AND is_read = 0
        ");
        $stmtCheck->execute([$studentId, $courseId, $message]);
        if (!$stmtCheck->fetch()) {
            // 1. Chèn vào bảng alerts
            $stmtInsert = $db->prepare("
                INSERT INTO alerts (user_id, course_id, message, is_read, created_at) 
                VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)
            ");
            $stmtInsert->execute([$studentId, $courseId, $message]);

            // Lấy tên sinh viên
            $stmtStudent = $db->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmtStudent->execute([$studentId]);
            $studentName = $stmtStudent->fetchColumn() ?: 'Sinh viên';

            // Lấy mã học phần
            $stmtCourseCode = $db->prepare("SELECT code FROM courses WHERE id = ?");
            $stmtCourseCode->execute([$courseId]);
            $courseCode = $stmtCourseCode->fetchColumn() ?: 'Học phần';

            // 2. Chèn vào notifications cho sinh viên (link dẫn tới lịch sử)
            $stmtNotifStud = $db->prepare("
                INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
                VALUES (?, 'Cảnh báo học tập', ?, '/student/history', 0, CURRENT_TIMESTAMP)
            ");
            $stmtNotifStud->execute([$studentId, $message]);

            // 3. Gửi thông báo cho giảng viên dạy lớp
            $stmtTeacher = $db->prepare("SELECT teacher_id FROM courses WHERE id = ?");
            $stmtTeacher->execute([$courseId]);
            $teacherId = $stmtTeacher->fetchColumn();
            if ($teacherId) {
                $teacherMsg = "Sinh viên $studentName bị cảnh báo trong lớp phần $courseCode: $message";
                $stmtNotifTeacher = $db->prepare("
                    INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
                    VALUES (?, 'Sinh viên bị cảnh báo', ?, '/teacher/alerts', 0, CURRENT_TIMESTAMP)
                ");
                $stmtNotifTeacher->execute([$teacherId, $teacherMsg]);
            }

            // 4. Gửi thông báo cho admin (user_id = 1)
            $adminMsg = "Sinh viên $studentName bị cảnh báo trong lớp phần $courseCode: $message";
            $stmtNotifAdmin = $db->prepare("
                INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
                VALUES (1, 'Sinh viên bị cảnh báo', ?, '/admin/alerts', 0, CURRENT_TIMESTAMP)
            ");
            $stmtNotifAdmin->execute([$adminMsg]);
        }
    }
}
