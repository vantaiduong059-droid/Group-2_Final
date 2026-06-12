<?php
// app/Repositories/UserRepository.php
require_once 'BaseRepository.php';

class UserRepository extends BaseRepository {
    
    public function getStudents() {
        $stmt = $this->model->db->prepare("SELECT id, username, full_name, email FROM {$this->model->table} WHERE role = 'student' ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createStudent($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (username, password, full_name, email, role) VALUES (:username, :password, :full_name, :email, 'student')");
        return $stmt->execute([
            'username' => $data['username'],
            'password' => password_hash('123456', PASSWORD_BCRYPT),
            'full_name' => $data['full_name'],
            'email' => $data['email']
        ]);
    }

    public function updateStudent($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET username = :username, full_name = :full_name, email = :email WHERE id = :id AND role = 'student'");
        return $stmt->execute([
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'id' => $id
        ]);
    }

    // --- CÁC HÀM XỬ LÝ GIẢNG VIÊN ---
    public function getTeachers() {
        $stmt = $this->model->db->prepare("
            SELECT u.id, u.username, u.full_name, u.email, GROUP_CONCAT(CONCAT(c.id, '::', c.class_code) SEPARATOR '||') as teaching_classes
            FROM {$this->model->table} u
            LEFT JOIN courses c ON u.id = c.teacher_id
            WHERE u.role = 'teacher' 
            GROUP BY u.id, u.username, u.full_name, u.email
            ORDER BY u.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createTeacher($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (username, password, full_name, email, role) VALUES (:username, :password, :full_name, :email, 'teacher')");
        return $stmt->execute([
            'username' => $data['username'],
            'password' => password_hash('123456', PASSWORD_BCRYPT),
            'full_name' => $data['full_name'],
            'email' => $data['email']
        ]);
    }

    public function updateTeacher($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET username = :username, full_name = :full_name, email = :email WHERE id = :id AND role = 'teacher'");
        return $stmt->execute([
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'id' => $id
        ]);
    }
}
