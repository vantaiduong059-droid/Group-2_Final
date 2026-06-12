<?php
// app/Patterns/Strategy/ManualAttendanceStrategy.php
require_once 'AttendanceStrategyInterface.php';

class ManualAttendanceStrategy implements AttendanceStrategyInterface {
    
    public function validateAndRecord($sessionId, $studentId, $data) {
        $status = isset($data['status']) ? trim($data['status']) : '';
        
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái điểm danh không hợp lệ.'];
        }

        // method_id = 3 là hình thức 'Manual' (điểm danh tay) theo bảng attendance_methods
        return [
            'success' => true,
            'message' => 'Cập nhật điểm danh thủ công thành công.',
            'status' => $status,
            'method_id' => 3
        ];
    }
}
