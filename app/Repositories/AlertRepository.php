<?php
// app/Repositories/AlertRepository.php
require_once 'BaseRepository.php';

class AlertRepository extends BaseRepository {
    public function getAllAlerts() {
        // Lấy tất cả cảnh báo kèm theo tên User và tên Course
        $stmt = $this->model->db->prepare("
            SELECT a.*, u.full_name as user_name, u.role, c.name as course_name 
            FROM {$this->model->table} a
            JOIN users u ON a.user_id = u.id
            JOIN courses c ON a.course_id = c.id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markAsRead($id) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET is_read = 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
