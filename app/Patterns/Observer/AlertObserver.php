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
        
        // 1b. Sử dụng AlertEngine chung để kiểm tra và lưu trữ cảnh báo
        AlertEngine::checkStudent($studentId, $courseId, true);
    }
}
