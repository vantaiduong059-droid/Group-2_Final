<?php
// app/Patterns/Observer/AlertObserver.php
require_once 'ObserverInterface.php';
require_once '../config/database.php';

class AlertObserver implements ObserverInterface {
    
    public function update($eventData) {
        $studentId = $eventData['student_id'] ?? null;
        $sessionId = $eventData['session_id'] ?? null;
        $status = $eventData['status'] ?? null;
        
        if (!$studentId || !$sessionId) return;
        
        $db = Database::getInstance()->getConnection();
        
        // 1. Tìm course_id từ session_id
        $stmt = $db->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $course = $stmt->fetch();
        if (!$course) return;
        $courseId = $course['course_id'];
        
        // 2. Kiểm tra cảnh báo số buổi vắng (vắng >= 3 buổi)
        if ($status === 'absent') {
            $stmtCount = $db->prepare("
                SELECT COUNT(*) as absent_count 
                FROM attendance_records ar
                JOIN class_sessions cs ON ar.session_id = cs.id
                WHERE cs.course_id = ? AND ar.student_id = ? AND ar.status = 'absent'
            ");
            $stmtCount->execute([$courseId, $studentId]);
            $countRow = $stmtCount->fetch();
            $absentCount = (int)$countRow['absent_count'];
            
            if ($absentCount >= 3) {
                // Lấy tên sinh viên
                $stmtName = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmtName->execute([$studentId]);
                $studentName = $stmtName->fetchColumn();
                
                $msgForStudent = "Cảnh báo học tập: Bạn đã vắng $absentCount buổi học trong học phần này. Hãy liên hệ giảng viên để được hỗ trợ!";
                $msgForTeacher = "Cảnh báo chuyên cần: Sinh viên $studentName đã vắng $absentCount buổi học trong học phần này!";
                
                // Gửi cảnh báo cho sinh viên
                $this->createAlertIfNotExists($db, $studentId, $courseId, $msgForStudent);
                // Gửi cảnh báo cho giảng viên (cố vấn học tập)
                $this->createTeacherAlert($db, $courseId, $msgForTeacher);
            }
        }

        // 3. Kiểm tra cảnh báo dựa trên điểm tích lũy (CPI < 50)
        // Lấy điểm tổng hợp từ engagement_scores (nếu có)
        $stmtScore = $db->prepare("SELECT total_score FROM engagement_scores WHERE course_id = ? AND student_id = ?");
        $stmtScore->execute([$courseId, $studentId]);
        $scoreRow = $stmtScore->fetch();
        if ($scoreRow) {
            $cpi = (int)$scoreRow['total_score'];
            if ($cpi < 50) {
                // Lấy tên sinh viên
                $stmtName = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmtName->execute([$studentId]);
                $studentName = $stmtName->fetchColumn();
                
                $msgForStudent = "Cảnh báo học tập: Chỉ số tham gia lớp học (CPI) hiện tại của bạn là $cpi/100 - quá thấp. Cần cải thiện chuyên cần và phát biểu!";
                $msgForTeacher = "Cảnh báo CPI thấp: Sinh viên $studentName có chỉ số tham gia (CPI) chỉ $cpi/100 trong học phần này.";
                
                $this->createAlertIfNotExists($db, $studentId, $courseId, $msgForStudent);
                $this->createTeacherAlert($db, $courseId, $msgForTeacher);
            }
        }
    }
    
    private function createAlertIfNotExists($db, $userId, $courseId, $message) {
        // Kiểm tra xem đã cảnh báo với thông điệp tương tự chưa để tránh spam
        $stmtCheck = $db->prepare("
            SELECT id FROM alerts 
            WHERE user_id = ? AND course_id = ? AND message = ? AND is_read = 0
        ");
        $stmtCheck->execute([$userId, $courseId, $message]);
        if (!$stmtCheck->fetch()) {
            $stmtInsert = $db->prepare("
                INSERT INTO alerts (user_id, course_id, message, is_read, created_at) 
                VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)
            ");
            $stmtInsert->execute([$userId, $courseId, $message]);
        }
    }

    private function createTeacherAlert($db, $courseId, $message) {
        // Lấy teacher_id của khóa học
        $stmt = $db->prepare("SELECT teacher_id FROM courses WHERE id = ? AND teacher_id IS NOT NULL");
        $stmt->execute([$courseId]);
        $teacherId = $stmt->fetchColumn();
        
        if ($teacherId) {
            $this->createAlertIfNotExists($db, $teacherId, $courseId, $message);
        }
    }
}
