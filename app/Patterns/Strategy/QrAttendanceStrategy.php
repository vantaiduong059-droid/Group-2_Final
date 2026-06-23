<?php
// app/Patterns/Strategy/QrAttendanceStrategy.php
require_once 'AttendanceStrategyInterface.php';
require_once '../config/database.php';

class QrAttendanceStrategy implements AttendanceStrategyInterface {
    
    public function validateAndRecord($sessionId, $studentId, $data) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT qr_token, attendance_expires_at, status 
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

        $expectedToken = $session['qr_token'];
        $expiresAt = $session['attendance_expires_at'];
        $providedToken = isset($data['qr_token']) ? trim($data['qr_token']) : '';

        if (empty($expectedToken)) {
            return ['success' => false, 'message' => 'Buổi học chưa được cấu hình điểm danh bằng QR.'];
        }

        // Kiểm tra thời gian hết hạn bằng helper chung
        $statusInfo = AttendanceSessionHelper::getStatus($session);
        if ($statusInfo['status'] === 'da_dong') {
            return ['success' => false, 'message' => 'Mã QR điểm danh đã hết hạn.'];
        }

        if ($providedToken === $expectedToken) {
            // method_id = 1 là hình thức 'QR' theo bảng attendance_methods
            return [
                'success' => true, 
                'message' => 'Điểm danh thành công bằng cách Quét QR.', 
                'status' => 'present', 
                'method_id' => 1
            ];
        }

        return ['success' => false, 'message' => 'Mã QR không hợp lệ hoặc đã quá hạn.'];
    }
}
