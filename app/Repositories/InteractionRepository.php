<?php
// app/Repositories/InteractionRepository.php
require_once 'BaseRepository.php';

class InteractionRepository extends BaseRepository {

    public function getLogsBySession($sessionId) {
        $stmt = $this->model->db->prepare("
            SELECT il.*, u.full_name as student_name 
            FROM {$this->model->table} il
            JOIN users u ON il.student_id = u.id
            WHERE il.session_id = :session_id
            ORDER BY il.created_at DESC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function addLog($sessionId, $studentId, $type, $pointsAwarded) {
        $stmt = $this->model->db->prepare("
            INSERT INTO {$this->model->table} (session_id, student_id, type, points_awarded, created_at)
            VALUES (:session_id, :student_id, :type, :points_awarded, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'type' => $type,
            'points_awarded' => $pointsAwarded
        ]);
    }

    public function getStudentInteractionPoints($studentId, $courseId) {
        $stmt = $this->model->db->prepare("
            SELECT SUM(il.points_awarded) as total_points
            FROM {$this->model->table} il
            JOIN class_sessions cs ON il.session_id = cs.id
            WHERE il.student_id = :student_id AND cs.course_id = :course_id
        ");
        $stmt->execute(['student_id' => $studentId, 'course_id' => $courseId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total_points'] : 0;
    }
}
