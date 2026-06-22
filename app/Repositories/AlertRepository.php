<?php
// app/Repositories/AlertRepository.php
require_once 'BaseRepository.php';

class AlertRepository extends BaseRepository {
    public function getAllAlerts() {
        // Lấy tất cả cảnh báo kèm theo tên User, tên Course và tên Cố vấn (Advisor)
        $stmt = $this->model->db->prepare("
            SELECT a.*, u.full_name as user_name, u.role, u.username as student_code,
                   c.name as course_name, c.code as course_code, c.class_code as course_class_code,
                   adv.full_name as advisor_name
            FROM {$this->model->table} a
            JOIN users u ON a.user_id = u.id
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN users adv ON a.advisor_id = adv.id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAlertsByTeacher($teacherId) {
        // Lấy tất cả cảnh báo thuộc các lớp do giáo viên dạy
        $stmt = $this->model->db->prepare("
            SELECT a.*, u.full_name as user_name, u.username as student_code, u.email as student_email,
                   c.name as course_name, c.code as course_code, c.class_code as course_class_code,
                   adv.full_name as advisor_name
            FROM {$this->model->table} a
            JOIN users u ON a.user_id = u.id
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN users adv ON a.advisor_id = adv.id
            WHERE c.teacher_id = :teacher_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    public function updateAlertStatus($id, $status, $notes) {
        $stmt = $this->model->db->prepare("
            UPDATE {$this->model->table} 
            SET status = :status, notes = :notes 
            WHERE id = :id
        ");
        return $stmt->execute([
            'status' => $status,
            'notes' => $notes,
            'id' => $id
        ]);
    }

    public function assignAdvisor($id, $advisorId) {
        $stmt = $this->model->db->prepare("
            UPDATE {$this->model->table} 
            SET advisor_id = :advisor_id 
            WHERE id = :id
        ");
        return $stmt->execute([
            'advisor_id' => $advisorId,
            'id' => $id
        ]);
    }

    public function markAsRead($id) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET is_read = 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
