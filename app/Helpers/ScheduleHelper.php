<?php
// app/Helpers/ScheduleHelper.php

class ScheduleHelper {
    /**
     * Kiểm tra trùng lịch của một buổi học (class_session)
     * Trả về thông tin buổi học bị trùng hoặc null nếu không trùng
     */
    public static function checkSessionConflict($date, $startTime, $endTime, $room, $excludeSessionId = null) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT cs.*, c.name as course_name, c.code as course_code 
                FROM class_sessions cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.session_date = ? AND cs.room = ?";
        $params = [$date, trim($room)];
        
        if ($excludeSessionId) {
            $sql .= " AND cs.id != ?";
            $params[] = $excludeSessionId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $existingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $s1 = strtotime($startTime);
        $e1 = strtotime($endTime);
        
        foreach ($existingSessions as $ex) {
            $s2 = strtotime($ex['start_time']);
            $e2 = strtotime($ex['end_time']);
            
            if ($s1 < $e2 && $e1 > $s2) {
                return $ex; // Trả về buổi học bị trùng
            }
        }
        return null;
    }

    /**
     * Kiểm tra trùng lịch của một danh sách thời khóa biểu cố định (course_schedules)
     * Trả về thông tin lịch bị trùng hoặc null nếu không trùng
     */
    public static function checkScheduleConflict($schedules, $excludeCourseId = null) {
        if (empty($schedules)) {
            return null;
        }
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT cs.*, c.name as course_name 
                FROM course_schedules cs 
                JOIN courses c ON cs.course_id = c.id";
        $params = [];
        if ($excludeCourseId) {
            $sql .= " WHERE cs.course_id != ?";
            $params[] = $excludeCourseId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $existingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($schedules as $newSched) {
            $day = (int)$newSched['day_of_week'];
            $start = $newSched['start_time'];
            $end = $newSched['end_time'];
            $room = trim($newSched['room']);
            
            foreach ($existingSchedules as $exSched) {
                if ((int)$exSched['day_of_week'] === $day && strcasecmp(trim($exSched['room']), $room) === 0) {
                    $s1 = strtotime($start);
                    $e1 = strtotime($end);
                    $s2 = strtotime($exSched['start_time']);
                    $e2 = strtotime($exSched['end_time']);
                    
                    if ($s1 < $e2 && $e1 > $s2) {
                        return [
                            'room' => $room,
                            'day_of_week' => $day,
                            'start_time' => $exSched['start_time'],
                            'end_time' => $exSched['end_time'],
                            'course_name' => $exSched['course_name']
                        ];
                    }
                }
            }
        }
        return null;
    }
}
