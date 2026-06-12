<?php
// app/Repositories/SessionRepository.php
require_once 'BaseRepository.php';

class SessionRepository extends BaseRepository {
    
    public function getAllSessions() {
        $stmt = $this->model->db->prepare("
            SELECT cs.*, c.name as course_name, c.code as course_code, c.class_code as course_class_code, u.full_name as teacher_name
            FROM {$this->model->table} cs 
            JOIN courses c ON cs.course_id = c.id
            LEFT JOIN users u ON c.teacher_id = u.id AND u.role = 'teacher'
            ORDER BY cs.session_date DESC, cs.start_time DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createSession($data) {
        $stmt = $this->model->db->prepare("INSERT INTO {$this->model->table} (course_id, session_date, start_time, end_time, status, room, period, note) VALUES (:course_id, :session_date, :start_time, :end_time, :status, :room, :period, :note)");
        return $stmt->execute([
            'course_id' => $data['course_id'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'room' => isset($data['room']) ? $data['room'] : 'Phòng học 102, số 1 Phan Tây Nhạc',
            'period' => isset($data['period']) ? $data['period'] : '1 - 3',
            'note' => isset($data['note']) ? $data['note'] : ''
        ]);
    }

    public function updateSession($id, $data) {
        $stmt = $this->model->db->prepare("UPDATE {$this->model->table} SET course_id = :course_id, session_date = :session_date, start_time = :start_time, end_time = :end_time, status = :status, room = :room, period = :period, note = :note WHERE id = :id");
        return $stmt->execute([
            'course_id' => $data['course_id'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'room' => isset($data['room']) ? $data['room'] : 'Phòng học 102, số 1 Phan Tây Nhạc',
            'period' => isset($data['period']) ? $data['period'] : '1 - 3',
            'note' => isset($data['note']) ? $data['note'] : '',
            'id' => $id
        ]);
    }
}
