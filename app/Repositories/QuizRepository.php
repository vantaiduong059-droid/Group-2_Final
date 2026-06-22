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

    // ==========================================
    // CÁC PHƯƠNG THỨC MỚI CỦA PHASE 3
    // ==========================================

    // Lấy danh sách quiz theo khóa học
    public function getQuizzesByCourse($courseId) {
        $stmt = $this->model->db->prepare("
            SELECT qz.*, cs.session_date, cs.period
            FROM {$this->model->table} qz
            JOIN class_sessions cs ON qz.session_id = cs.id
            WHERE cs.course_id = :course_id
            ORDER BY qz.created_at DESC
        ");
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    // Cập nhật thông tin Quiz
    public function updateQuiz($id, $data) {
        $stmt = $this->model->db->prepare("
            UPDATE {$this->model->table} 
            SET title = :title, start_time = :start_time, end_time = :end_time, total_marks = :total_marks
            WHERE id = :id
        ");
        return $stmt->execute([
            'title' => $data['title'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'total_marks' => $data['total_marks'],
            'id' => $id
        ]);
    }

    // Xóa Quiz
    public function deleteQuiz($id) {
        $stmt = $this->model->db->prepare("DELETE FROM {$this->model->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Lấy câu hỏi trắc nghiệm của Quiz
    public function getQuestionsByQuiz($quizId) {
        $stmt = $this->model->db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll();
    }

    // Thêm câu hỏi trắc nghiệm
    public function addQuestion($data) {
        $stmt = $this->model->db->prepare("
            INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option)
            VALUES (:quiz_id, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option)
        ");
        return $stmt->execute([
            'quiz_id' => $data['quiz_id'],
            'question_text' => $data['question_text'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'],
            'option_d' => $data['option_d'],
            'correct_option' => $data['correct_option']
        ]);
    }

    // Sửa câu hỏi trắc nghiệm
    public function updateQuestion($id, $data) {
        $stmt = $this->model->db->prepare("
            UPDATE quiz_questions 
            SET question_text = :question_text, option_a = :option_a, option_b = :option_b, 
                option_c = :option_c, option_d = :option_d, correct_option = :correct_option
            WHERE id = :id
        ");
        return $stmt->execute([
            'question_text' => $data['question_text'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'],
            'option_d' => $data['option_d'],
            'correct_option' => $data['correct_option'],
            'id' => $id
        ]);
    }

    // Xóa câu hỏi trắc nghiệm
    public function deleteQuestion($id) {
        $stmt = $this->model->db->prepare("DELETE FROM quiz_questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Lấy danh sách thảo luận của khóa học
    public function getDiscussionsByCourse($courseId) {
        $stmt = $this->model->db->prepare("
            SELECT d.*, u.full_name as creator_name, u.role as creator_role
            FROM class_discussions d
            JOIN users u ON d.created_by = u.id
            WHERE d.course_id = :course_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    // Tạo thảo luận mới
    public function createDiscussion($data) {
        $stmt = $this->model->db->prepare("
            INSERT INTO class_discussions (course_id, title, content, created_by)
            VALUES (:course_id, :title, :content, :created_by)
        ");
        return $stmt->execute([
            'course_id' => $data['course_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'created_by' => $data['created_by']
        ]);
    }

    // Xóa thảo luận
    public function deleteDiscussion($id) {
        $stmt = $this->model->db->prepare("DELETE FROM class_discussions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Lấy danh sách câu trả lời của thảo luận
    public function getRepliesByDiscussion($discussionId) {
        $stmt = $this->model->db->prepare("
            SELECT r.*, u.full_name as user_name, u.role as user_role
            FROM discussion_replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.discussion_id = :discussion_id
            ORDER BY r.created_at ASC
        ");
        $stmt->execute(['discussion_id' => $discussionId]);
        return $stmt->fetchAll();
    }

    // Tạo phản hồi mới
    public function createReply($data) {
        $stmt = $this->model->db->prepare("
            INSERT INTO discussion_replies (discussion_id, user_id, content)
            VALUES (:discussion_id, :user_id, :content)
        ");
        return $stmt->execute([
            'discussion_id' => $data['discussion_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content']
        ]);
    }
}
