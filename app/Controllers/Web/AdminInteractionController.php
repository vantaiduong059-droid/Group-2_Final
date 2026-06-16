<?php
// app/Controllers/Web/AdminInteractionController.php
require_once '../core/Controller.php';
require_once '../app/Models/Quiz.php';
require_once '../app/Repositories/QuizRepository.php';
require_once '../app/Models/Interaction.php';
require_once '../app/Repositories/InteractionRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class AdminInteractionController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            } else {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
            || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    public function index() {
        $courseModel = new Course();
        $courseRepo = new CourseRepository($courseModel);
        $courses = $courseRepo->getAll();

        $this->view('admin/interactions', [
            'title' => 'Tương tác lớp học (Giám sát)',
            'courses' => $courses
        ]);
    }

    public function getSummary() {
        $db = Database::getInstance()->getConnection();
        $courseId = $_GET['course_id'] ?? null;
        
        // 1. Lấy logs tương tác mới nhất
        $sqlLogs = "
            SELECT il.*, u.full_name as student_name, u.username as student_code, 
                   cs.session_date, c.name as course_name, c.code as course_code
            FROM interaction_logs il
            JOIN users u ON il.student_id = u.id
            JOIN class_sessions cs ON il.session_id = cs.id
            JOIN courses c ON cs.course_id = c.id
        ";
        if ($courseId) {
            $sqlLogs .= " WHERE cs.course_id = :course_id";
        }
        $sqlLogs .= " ORDER BY il.created_at DESC LIMIT 50";
        
        $stmtLogs = $db->prepare($sqlLogs);
        if ($courseId) {
            $stmtLogs->execute(['course_id' => $courseId]);
        } else {
            $stmtLogs->execute();
        }
        $logs = $stmtLogs->fetchAll();

        // 2. Lấy danh sách quiz
        $sqlQuizzes = "
            SELECT qz.*, cs.session_date, c.name as course_name, c.code as course_code,
                   (SELECT COUNT(*) FROM quiz_submissions WHERE quiz_id = qz.id) as submission_count
            FROM quiz_sessions qz
            JOIN class_sessions cs ON qz.session_id = cs.id
            JOIN courses c ON cs.course_id = c.id
        ";
        if ($courseId) {
            $sqlQuizzes .= " WHERE cs.course_id = :course_id";
        }
        $sqlQuizzes .= " ORDER BY qz.created_at DESC";
        
        $stmtQuizzes = $db->prepare($sqlQuizzes);
        if ($courseId) {
            $stmtQuizzes->execute(['course_id' => $courseId]);
        } else {
            $stmtQuizzes->execute();
        }
        $quizzes = $stmtQuizzes->fetchAll();

        // 3. Lấy danh sách câu hỏi thảo luận
        $sqlDiscussions = "
            SELECT cd.*, c.name as course_name, c.code as course_code, u.full_name as creator_name,
                   (SELECT COUNT(*) FROM discussion_replies WHERE discussion_id = cd.id) as reply_count
            FROM class_discussions cd
            JOIN courses c ON cd.course_id = c.id
            JOIN users u ON cd.created_by = u.id
        ";
        if ($courseId) {
            $sqlDiscussions .= " WHERE cd.course_id = :course_id";
        }
        $sqlDiscussions .= " ORDER BY cd.created_at DESC";
        
        $stmtDiscussions = $db->prepare($sqlDiscussions);
        if ($courseId) {
            $stmtDiscussions->execute(['course_id' => $courseId]);
        } else {
            $stmtDiscussions->execute();
        }
        $discussions = $stmtDiscussions->fetchAll();

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'logs' => $logs,
                'quizzes' => $quizzes,
                'discussions' => $discussions
            ]
        ]);
    }
}
