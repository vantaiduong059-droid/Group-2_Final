<?php
// app/Repositories/UserRepository.php
require_once 'BaseRepository.php';

class UserRepository extends BaseRepository {
    
    public function getStudents() {
        $stmt = $this->model->db->prepare("
            SELECT u.id, u.username, u.last_name, u.first_name, u.full_name, u.email, u.cohort, u.major_id, m.name as major_name
            FROM {$this->model->table} u
            LEFT JOIN majors m ON u.major_id = m.id
            WHERE u.role = 'student'
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createStudent($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (username, password, last_name, first_name, email, role, cohort, major_id) VALUES (:username, :password, :last_name, :first_name, :email, 'student', :cohort, :major_id)");
        return $stmt->execute([
            'username' => $data['username'],
            'password' => password_hash('123456', PASSWORD_BCRYPT),
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'cohort' => $data['cohort'] ?? null,
            'major_id' => !empty($data['major_id']) ? $data['major_id'] : null
        ]);
    }

    public function updateStudent($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET username = :username, last_name = :last_name, first_name = :first_name, email = :email, cohort = :cohort, major_id = :major_id WHERE id = :id AND role = 'student'");
        return $stmt->execute([
            'username' => $data['username'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'cohort' => $data['cohort'] ?? null,
            'major_id' => !empty($data['major_id']) ? $data['major_id'] : null,
            'id' => $id
        ]);
    }

    // --- CÁC HÀM XỬ LÝ GIẢNG VIÊN ---
    public function getTeachers() {
        $stmt = $this->model->db->prepare("
            SELECT u.id, u.username, u.last_name, u.first_name, u.full_name, u.email, GROUP_CONCAT(CONCAT(c.id, '::', c.class_code) SEPARATOR '||') as teaching_classes
            FROM {$this->model->table} u
            LEFT JOIN courses c ON u.id = c.teacher_id
            WHERE u.role = 'teacher' 
            GROUP BY u.id, u.username, u.last_name, u.first_name, u.full_name, u.email
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createTeacher($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (username, password, last_name, first_name, email, role) VALUES (:username, :password, :last_name, :first_name, :email, 'teacher')");
        return $stmt->execute([
            'username' => $data['username'],
            'password' => password_hash('123456', PASSWORD_BCRYPT),
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email']
        ]);
    }

    public function updateTeacher($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET username = :username, last_name = :last_name, first_name = :first_name, email = :email WHERE id = :id AND role = 'teacher'");
        return $stmt->execute([
            'username' => $data['username'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'id' => $id
        ]);
    }

    // --- PROFILE ---
    public function getProfile($id) {
        $stmt = $this->model->db->prepare("SELECT id, username, last_name, first_name, full_name, email, phone, avatar_url, role, created_at FROM {$this->model->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateProfile($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        if (isset($data['last_name']))  { $fields[] = 'last_name = :last_name';   $params['last_name']  = $data['last_name']; }
        if (isset($data['first_name'])) { $fields[] = 'first_name = :first_name'; $params['first_name'] = $data['first_name']; }
        if (isset($data['phone']))      { $fields[] = 'phone = :phone';           $params['phone']      = $data['phone']; }
        if (isset($data['avatar_url'])) { $fields[] = 'avatar_url = :avatar_url'; $params['avatar_url'] = $data['avatar_url']; }
        if (empty($fields)) return true;
        $sql = "UPDATE {$this->model->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->model->db->prepare($sql)->execute($params);
    }

    public function changePassword($id, $oldPassword, $newPassword) {
        $stmt = $this->model->db->prepare("SELECT password FROM {$this->model->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($oldPassword, $row['password'])) {
            return ['ok' => false, 'message' => 'Mật khẩu cũ không đúng.'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt2 = $this->model->db->prepare("UPDATE {$this->model->table} SET password = :pw WHERE id = :id");
        $stmt2->execute(['pw' => $hash, 'id' => $id]);
        return ['ok' => true, 'message' => 'Đổi mật khẩu thành công.'];
    }

    public function getById($id) {
        return $this->getProfile($id);
    }
}
