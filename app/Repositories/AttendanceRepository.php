<?php
// app/Repositories/AttendanceRepository.php
require_once 'BaseRepository.php';

class AttendanceRepository extends BaseRepository {

    public function getRecordsBySession($sessionId) {
        $stmt = $this->model->db->prepare("
            SELECT u.id as student_id, u.full_name, u.email, ar.status, ar.recorded_at, am.name as method_name
            FROM users u
            JOIN course_students cs ON u.id = cs.student_id
            JOIN class_sessions s ON cs.course_id = s.course_id
            LEFT JOIN {$this->model->table} ar ON ar.session_id = s.id AND ar.student_id = u.id
            LEFT JOIN attendance_methods am ON ar.method_id = am.id
            WHERE s.id = :session_id AND u.role = 'student'
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function getRecordBySessionAndStudent($sessionId, $studentId) {
        $stmt = $this->model->db->prepare("
            SELECT * FROM {$this->model->table} 
            WHERE session_id = :session_id AND student_id = :student_id
        ");
        $stmt->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        return $stmt->fetch();
    }

    public function saveRecord($sessionId, $studentId, $methodId, $status) {
        // Sử dụng INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $this->model->db->prepare("
            INSERT INTO {$this->model->table} (session_id, student_id, method_id, status, recorded_at)
            VALUES (:session_id, :student_id, :method_id, :status, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
                status = :status_update, 
                method_id = :method_update, 
                recorded_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'method_id' => $methodId,
            'status' => $status,
            'status_update' => $status,
            'method_update' => $methodId
        ]);
    }

    public function getStudentAttendanceHistory($studentId, $courseId = null) {
        $sql = "
            SELECT cs.session_date, cs.start_time, cs.end_time, c.name as course_name, c.code as course_code,
                   ar.status, ar.recorded_at, am.name as method_name
            FROM class_sessions cs
            JOIN courses c ON cs.course_id = c.id
            JOIN course_students cstud ON c.id = cstud.course_id AND cstud.student_id = :student_id
            LEFT JOIN {$this->model->table} ar ON ar.session_id = cs.id AND ar.student_id = :student_id_att
            LEFT JOIN attendance_methods am ON ar.method_id = am.id
            WHERE CONCAT(cs.session_date, ' ', cs.end_time) < NOW()
        ";
        
        $params = [
            'student_id' => $studentId,
            'student_id_att' => $studentId
        ];

        if ($courseId) {
            $sql .= " AND c.id = :course_id";
            $params['course_id'] = $courseId;
        }

        $sql .= " ORDER BY cs.session_date DESC, cs.start_time DESC";
        
        $stmt = $this->model->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAbsentCount($studentId, $courseId) {
        $stmt = $this->model->db->prepare("
            SELECT COUNT(*) as absent_count 
            FROM {$this->model->table} ar
            JOIN class_sessions cs ON ar.session_id = cs.id
            WHERE cs.course_id = :course_id AND ar.student_id = :student_id AND ar.status = 'absent'
        ");
        $stmt->execute(['course_id' => $courseId, 'student_id' => $studentId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['absent_count'] : 0;
    }

    public function logChange($sessionId, $studentId, $changedBy, $oldStatus, $newStatus, $reason) {
        $stmt = $this->model->db->prepare("
            INSERT INTO attendance_change_logs (session_id, student_id, changed_by, old_status, new_status, reason)
            VALUES (:session_id, :student_id, :changed_by, :old_status, :new_status, :reason)
        ");
        return $stmt->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'changed_by' => $changedBy,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason
        ]);
    }

    public function getChangeLogs($sessionId) {
        $stmt = $this->model->db->prepare("
            SELECT acl.*, u_stud.full_name as student_name, u_chg.full_name as changed_by_name
            FROM attendance_change_logs acl
            JOIN users u_stud ON acl.student_id = u_stud.id
            JOIN users u_chg ON acl.changed_by = u_chg.id
            WHERE acl.session_id = :session_id
            ORDER BY acl.changed_at DESC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function submitComplaint($studentId, $sessionId, $description) {
        $stmt = $this->model->db->prepare("
            INSERT INTO student_complaints (student_id, session_id, description) VALUES (:sid, :sesid, :desc)
        ");
        return $stmt->execute(['sid' => $studentId, 'sesid' => $sessionId, 'desc' => $description]);
    }

    public function getComplaintsBySession($sessionId) {
        $stmt = $this->model->db->prepare("
            SELECT sc.*, u.full_name as student_name, u.email as student_email
            FROM student_complaints sc
            JOIN users u ON sc.student_id = u.id
            WHERE sc.session_id = :session_id
            ORDER BY sc.created_at DESC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function resolveComplaint($complaintId, $teacherNote) {
        $stmt = $this->model->db->prepare("
            UPDATE student_complaints SET status = 'resolved', teacher_note = :note WHERE id = :id
        ");
        return $stmt->execute(['note' => $teacherNote, 'id' => $complaintId]);
    }

    public function getStudentAttendanceSummary($studentId) {
        $stmt = $this->model->db->prepare("
            SELECT 
                c.id as course_id, c.name as course_name, c.code as course_code,
                COUNT(cs.id) as total_sessions,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count
            FROM courses c
            JOIN course_students cstu ON c.id = cstu.course_id AND cstu.student_id = :student_id
            LEFT JOIN class_sessions cs ON c.id = cs.course_id
            LEFT JOIN {$this->model->table} ar ON cs.id = ar.session_id AND ar.student_id = :student_id2
            GROUP BY c.id, c.name, c.code
        ");
        $stmt->execute(['student_id' => $studentId, 'student_id2' => $studentId]);
        return $stmt->fetchAll();
    }
}
