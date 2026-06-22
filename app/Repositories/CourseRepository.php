<?php
// app/Repositories/CourseRepository.php
require_once 'BaseRepository.php';

class CourseRepository extends BaseRepository {
    
    // Override hàm getAll để lấy thêm tên Giảng viên
    public function getAll() {
        $stmt = $this->model->db->prepare("
            SELECT c.*, u.full_name as teacher_name 
            FROM {$this->model->table} c
            LEFT JOIN users u ON c.teacher_id = u.id AND u.role = 'teacher'
            ORDER BY c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCoursesByTeacher($teacherId) {
        $stmt = $this->model->db->prepare("SELECT * FROM {$this->model->table} WHERE teacher_id = :teacher_id ORDER BY name ASC");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    // Lấy chi tiết khóa học kèm số lượng sinh viên
    public function getCourseDetails($id) {
        $stmt = $this->model->db->prepare("
            SELECT c.*, u.full_name as teacher_name, COUNT(cs.student_id) as student_count
            FROM {$this->model->table} c
            LEFT JOIN users u ON c.teacher_id = u.id AND u.role = 'teacher'
            LEFT JOIN course_students cs ON c.id = cs.course_id
            WHERE c.id = :id
            GROUP BY c.id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createCourse($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (code, class_code, name, credits, periods, total_sessions, description, teacher_id) VALUES (:code, :class_code, :name, :credits, :periods, :total_sessions, :description, :teacher_id)");
        $stmt->execute([
            'code' => $data['code'],
            'class_code' => $data['class_code'],
            'name' => $data['name'],
            'credits' => $data['credits'],
            'periods' => $data['periods'],
            'total_sessions' => $data['total_sessions'] ?? 15,
            'description' => $data['description'],
            'teacher_id' => $data['teacher_id']
        ]);
        return $this->model->db->lastInsertId();
    }

    public function updateCourse($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET code = :code, class_code = :class_code, name = :name, credits = :credits, periods = :periods, total_sessions = :total_sessions, description = :description, teacher_id = :teacher_id WHERE id = :id");
        return $stmt->execute([
            'code' => $data['code'],
            'class_code' => $data['class_code'],
            'name' => $data['name'],
            'credits' => $data['credits'],
            'periods' => $data['periods'],
            'total_sessions' => $data['total_sessions'] ?? 15,
            'description' => $data['description'],
            'teacher_id' => $data['teacher_id'],
            'id' => $id
        ]);
    }

    public function getStudentsByCourse($courseId) {
        $stmt = $this->model->db->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.phone, u.avatar_url
            FROM users u
            JOIN course_students cs ON u.id = cs.student_id
            WHERE cs.course_id = :course_id AND u.role = 'student'
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    public function getCoursesForStudent($studentId) {
        $stmt = $this->model->db->prepare("
            SELECT c.*, u.full_name as teacher_name
            FROM {$this->model->table} c
            JOIN course_students cs ON c.id = cs.course_id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE cs.student_id = :student_id
            ORDER BY c.name ASC
        ");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        return $this->getCourseDetails($id);
    }
}
