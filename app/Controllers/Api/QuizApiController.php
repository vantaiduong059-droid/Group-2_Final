<?php
// app/Controllers/Api/QuizApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Quiz.php';
require_once '../app/Repositories/QuizRepository.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Engagement.php';

class QuizApiController extends Controller {
    private $quizRepo;
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $this->quizRepo = new QuizRepository(new Quiz());
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    /**
     * Lấy các bài quiz của buổi học
     */
    public function index() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu session_id.'], 400);
        }

        $quizzes = $this->quizRepo->getQuizzesBySession($sessionId);
        
        // Nếu là sinh viên, kiểm tra xem mình đã làm bài chưa để trả về thông tin đã nộp bài
        if ($_SESSION['user']['role'] === 'student') {
            $studentId = $_SESSION['user']['id'];
            foreach ($quizzes as &$quiz) {
                $sub = $this->quizRepo->getStudentSubmission($quiz['id'], $studentId);
                $quiz['is_submitted'] = $sub ? true : false;
                $quiz['my_score'] = $sub ? $sub['score'] : null;
            }
        }

        $this->jsonResponse(['status' => 'success', 'data' => $quizzes]);
    }

    /**
     * Giảng viên tạo bài quiz mới
     */
    public function store() {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $this->getJsonInput();
        if (empty($data['session_id']) || empty($data['title']) || empty($data['total_marks'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin.'], 400);
        }

        // Đặt mặc định thời gian làm quiz từ bây giờ tới +10 phút nếu giáo viên không chọn
        $startTime = $data['start_time'] ?? date('Y-m-d H:i:s');
        $endTime = $data['end_time'] ?? date('Y-m-d H:i:s', time() + 600);

        $quizData = [
            'session_id' => $data['session_id'],
            'title' => $data['title'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_marks' => $data['total_marks']
        ];

        try {
            $this->quizRepo->createQuiz($quizData);
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo Quiz thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi tạo Quiz: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sinh viên làm bài và nộp quiz
     */
    public function submit($quizId) {
        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $studentId = $_SESSION['user']['id'];
        $data = $this->getJsonInput();
        
        // Điểm số ngẫu nhiên hoặc do sinh viên nộp (đáp án đúng)
        // Trong đồ án mẫu, ta cho phép truyền điểm trực tiếp hoặc giả lập điểm thi
        $score = isset($data['score']) ? floatval($data['score']) : rand(5, 10);

        $quiz = $this->quizRepo->getById($quizId);
        if (!$quiz) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy bài Quiz.'], 404);
        }

        // Kiểm tra thời gian hết hạn quiz
        if (strtotime($quiz['end_time']) < time()) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bài Quiz đã hết hạn nộp bài.'], 400);
        }

        try {
            $this->quizRepo->submitQuiz($quizId, $studentId, $score);
            
            // Tìm courseId của buổi học chứa bài Quiz này để cập nhật lại CPI
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT cs.course_id 
                FROM quiz_sessions qz
                JOIN class_sessions cs ON qz.session_id = cs.id
                WHERE qz.id = ?
            ");
            $stmt->execute([$quizId]);
            $courseId = $stmt->fetchColumn();

            if ($courseId) {
                // Tính toán lại CPI
                $this->engagementRepo->recalculateScore($courseId, $studentId);
            }

            $this->jsonResponse([
                'status' => 'success', 
                'message' => 'Nộp bài Quiz thành công!', 
                'data' => [
                    'score' => $score,
                    'total_marks' => $quiz['total_marks']
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi nộp bài: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Giảng viên xem các bài nộp của Quiz
     */
    public function submissions($quizId) {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        try {
            $subs = $this->quizRepo->getSubmissions($quizId);
            $this->jsonResponse(['status' => 'success', 'data' => $subs]);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi truy xuất bài nộp.'], 500);
        }
    }
}
