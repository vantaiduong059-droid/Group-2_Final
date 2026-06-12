<?php
// app/Repositories/QuizRepository.php
require_once 'BaseRepository.php';

class QuizRepository extends BaseRepository {

    public function getQuizzesBySession($sessionId) {
        $stmt = $this->model->db->prepare("
            SELECT * FROM {$this->model->table} 
            WHERE session_id = :session_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function createQuiz($data) {
        $stmt = $this->model->db->prepare("
            INSERT INTO {$this->model->table} (session_id, title, start_time, end_time, total_marks) 
            VALUES (:session_id, :title, :start_time, :end_time, :total_marks)
        ");
        return $stmt->execute([
            'session_id' => $data['session_id'],
            'title' => $data['title'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'total_marks' => $data['total_marks']
        ]);
    }

    public function submitQuiz($quizId, $studentId, $score) {
        $stmt = $this->model->db->prepare("
            INSERT INTO quiz_submissions (quiz_id, student_id, score, submitted_at)
            VALUES (:quiz_id, :student_id, :score, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
                score = :score_update, 
                submitted_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([
            'quiz_id' => $quizId,
            'student_id' => $studentId,
            'score' => $score,
            'score_update' => $score
        ]);
    }

    public function getSubmissions($quizId) {
        $stmt = $this->model->db->prepare("
            SELECT qs.*, u.full_name as student_name, u.email as student_email
            FROM quiz_submissions qs
            JOIN users u ON qs.student_id = u.id
            WHERE qs.quiz_id = :quiz_id
            ORDER BY qs.score DESC, qs.submitted_at ASC
        ");
        $stmt->execute(['quiz_id' => $quizId]);
        return $stmt->fetchAll();
    }

    public function getStudentSubmission($quizId, $studentId) {
        $stmt = $this->model->db->prepare("
            SELECT * FROM quiz_submissions 
            WHERE quiz_id = :quiz_id AND student_id = :student_id
        ");
        $stmt->execute(['quiz_id' => $quizId, 'student_id' => $studentId]);
        return $stmt->fetch();
    }

    public function getStudentSubmissionsInCourse($studentId, $courseId) {
        $stmt = $this->model->db->prepare("
            SELECT qs.*, qz.title as quiz_title, qz.total_marks
            FROM quiz_submissions qs
            JOIN quiz_sessions qz ON qs.quiz_id = qz.id
            JOIN class_sessions cs ON qz.session_id = cs.id
            WHERE qs.student_id = :student_id AND cs.course_id = :course_id
            ORDER BY qs.submitted_at DESC
        ");
        $stmt->execute(['student_id' => $studentId, 'course_id' => $courseId]);
        return $stmt->fetchAll();
    }
}
