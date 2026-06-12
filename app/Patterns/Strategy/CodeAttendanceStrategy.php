<?php
// app/Patterns/Strategy/CodeAttendanceStrategy.php
require_once 'AttendanceStrategyInterface.php';
require_once '../config/database.php';

class CodeAttendanceStrategy implements AttendanceStrategyInterface {
    
    public function validateAndRecord($sessionId, $studentId, $data) {
        $db = Database::getInstance()->getConnection();
        
        // Query mã điểm danh hiện tại và thời gian hết hạn của buổi học
        $stmt = $db->prepare("
            SELECT attendance_code, attendance_expires_at, status 
            FROM class_sessions 
            WHERE id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin buổi học.'];
        }
        
        if ($session['status'] !== 'active') {
            return ['success' => false, 'message' => 'Buổi học này chưa được mở điểm danh.'];
        }

        $expectedCode = $session['attendance_code'];
        $expiresAt = $session['attendance_expires_at'];
        $providedCode = isset($data['code']) ? trim($data['code']) : '';

        if (empty($expectedCode)) {
            return ['success' => false, 'message' => 'Buổi học chưa được cấu hình điểm danh bằng mã code.'];
        }

        // Kiểm tra thời gian hết hạn
        if ($expiresAt && strtotime($expiresAt) < time()) {
            return ['success' => false, 'message' => 'Mã Code điểm danh đã hết hạn.'];
        }

        if ($providedCode === $expectedCode) {
            // Trả về thông tin trạng thái thành công để ghi nhận
            // method_id = 2 là hình thức 'Code' theo bảng attendance_methods
            return [
                'success' => true, 
                'message' => 'Điểm danh thành công bằng Mã Code.', 
                'status' => 'present', 
                'method_id' => 2
            ];
        }

        return ['success' => false, 'message' => 'Mã Code không chính xác. Vui lòng thử lại.'];
    }
}
