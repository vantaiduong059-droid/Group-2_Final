<?php
// app/Controllers/Web/TeacherQuizzesController.php
require_once '../core/Controller.php';
require_once '../app/Models/Quiz.php';
require_once '../app/Repositories/QuizRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';
require_once '../app/Models/ClassSession.php';
require_once '../app/Repositories/SessionRepository.php';

class TeacherQuizzesController extends Controller {
    private $quizRepo;
    private $courseRepo;
    private $sessionRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            } else {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }
        $this->quizRepo = new QuizRepository(new Quiz());
        $this->courseRepo = new CourseRepository(new Course());
        $this->sessionRepo = new SessionRepository(new ClassSession());
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
            || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    // Bảo mật: Kiểm tra quyền sở hữu khoá học của giảng viên
    private function checkCourseOwnership($courseId) {
        $course = $this->courseRepo->getById($courseId);
        if (!$course || $course['teacher_id'] != $_SESSION['user']['id']) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Quyền truy cập bị từ chối. Lớp học này không thuộc quyền phụ trách của bạn.'], 403);
            exit;
        }
        return $course;
    }

    // Bảo mật: Kiểm tra quyền sở hữu buổi học qua khoá học
    private function checkSessionOwnership($sessionId) {
        $session = $this->sessionRepo->getById($sessionId);
        if (!$session) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy buổi học.'], 404);
            exit;
        }
        $this->checkCourseOwnership($session['course_id']);
        return $session;
    }

    // Bảo mật: Kiểm tra quyền sở hữu Quiz
    private function checkQuizOwnership($quizId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT session_id FROM quiz_sessions WHERE id = ?");
        $stmt->execute([$quizId]);
        $sessionId = $stmt->fetchColumn();
        if (!$sessionId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy Quiz.'], 404);
            exit;
        }
        $this->checkSessionOwnership($sessionId);
        return $quizId;
    }

    // Bảo mật: Kiểm tra quyền sở hữu Câu hỏi
    private function checkQuestionOwnership($questionId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $quizId = $stmt->fetchColumn();
        if (!$quizId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy câu hỏi.'], 404);
            exit;
        }
        $this->checkQuizOwnership($quizId);
        return $questionId;
    }

    // Bảo mật: Kiểm tra quyền sở hữu thảo luận
    private function checkDiscussionOwnership($discussionId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT course_id FROM class_discussions WHERE id = ?");
        $stmt->execute([$discussionId]);
        $courseId = $stmt->fetchColumn();
        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy thảo luận.'], 404);
            exit;
        }
        $this->checkCourseOwnership($courseId);
        return $discussionId;
    }

    public function index() {
        $teacherId = $_SESSION['user']['id'];
        $myCourses = $this->courseRepo->getCoursesByTeacher($teacherId);

        $this->view('teacher/quizzes_discussions', [
            'title' => 'Quản lý Quiz & Thảo luận',
            'myCourses' => $myCourses
        ]);
    }

    // ==========================================
    // API QUIZ
    // ==========================================
    
    public function getQuizzes($courseId) {
        $this->checkCourseOwnership($courseId);
        $quizzes = $this->quizRepo->getQuizzesByCourse($courseId);
        $this->jsonResponse(['status' => 'success', 'data' => $quizzes]);
    }

    public function getQuestions($quizId) {
        $this->checkQuizOwnership($quizId);
        $questions = $this->quizRepo->getQuestionsByQuiz($quizId);
        $this->jsonResponse(['status' => 'success', 'data' => $questions]);
    }

    public function createQuiz($courseId) {
        $this->checkCourseOwnership($courseId);
        $data = $this->getJsonInput();
        
        if (empty($data['title']) || empty($data['session_id']) || empty($data['start_time']) || empty($data['end_time'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin.'], 400);
        }
        
        $this->checkSessionOwnership($data['session_id']);

        $res = $this->quizRepo->createQuiz($data);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo Quiz thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không thể tạo Quiz.'], 500);
        }
    }

    public function updateQuiz($quizId) {
        $this->checkQuizOwnership($quizId);
        $data = $this->getJsonInput();
        
        if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin.'], 400);
        }

        $res = $this->quizRepo->updateQuiz($quizId, $data);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật Quiz thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật.'], 500);
        }
    }

    public function deleteQuiz($quizId) {
        $this->checkQuizOwnership($quizId);
        $res = $this->quizRepo->deleteQuiz($quizId);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa Quiz thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa Quiz.'], 500);
        }
    }

    // ==========================================
    // API CÂU HỎI TRẮC NGHIỆM
    // ==========================================

    public function addQuestion($quizId) {
        $this->checkQuizOwnership($quizId);
        $data = $this->getJsonInput();
        $data['quiz_id'] = $quizId;

        if (empty($data['question_text']) || empty($data['option_a']) || empty($data['option_b']) || empty($data['correct_option'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ câu hỏi, các lựa chọn và đáp án.'], 400);
        }

        $res = $this->quizRepo->addQuestion($data);
        if ($res) {
            // Lấy danh sách câu hỏi mới
            $questions = $this->quizRepo->getQuestionsByQuiz($quizId);
            $this->jsonResponse(['status' => 'success', 'message' => 'Thêm câu hỏi thành công.', 'data' => $questions]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không thể thêm câu hỏi.'], 500);
        }
    }

    public function updateQuestion($questionId) {
        $this->checkQuestionOwnership($questionId);
        $data = $this->getJsonInput();

        if (empty($data['question_text']) || empty($data['option_a']) || empty($data['option_b']) || empty($data['correct_option'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ câu hỏi và các lựa chọn.'], 400);
        }

        $res = $this->quizRepo->updateQuestion($questionId, $data);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật câu hỏi thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật câu hỏi.'], 500);
        }
    }

    public function deleteQuestion($questionId) {
        $this->checkQuestionOwnership($questionId);
        $res = $this->quizRepo->deleteQuestion($questionId);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa câu hỏi thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa câu hỏi.'], 500);
        }
    }

    // ==========================================
    // API THẢO LUẬN LỚP HỌC (DISCUSSIONS)
    // ==========================================

    public function getDiscussions($courseId) {
        $this->checkCourseOwnership($courseId);
        $discussions = $this->quizRepo->getDiscussionsByCourse($courseId);
        
        // Trả về kèm danh sách câu hỏi của từng quiz luôn để vẽ cây thư mục hoặc tab
        $db = Database::getInstance()->getConnection();
        // Lấy chi tiết log nộp bài của sinh viên trong lớp để hiển thị logs
        $stmtLogs = $db->prepare("
            SELECT il.*, u.full_name as student_name, u.username as student_code, cs.session_date
            FROM interaction_logs il
            JOIN users u ON il.student_id = u.id
            JOIN class_sessions cs ON il.session_id = cs.id
            WHERE cs.course_id = ?
            ORDER BY il.created_at DESC
            LIMIT 50
        ");
        $stmtLogs->execute([$courseId]);
        $logs = $stmtLogs->fetchAll();

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'discussions' => $discussions,
                'logs' => $logs
            ]
        ]);
    }

    public function createDiscussion($courseId) {
        $this->checkCourseOwnership($courseId);
        $data = $this->getJsonInput();
        $data['course_id'] = $courseId;
        $data['created_by'] = $_SESSION['user']['id'];

        if (empty($data['title']) || empty($data['content'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập tiêu đề và nội dung thảo luận.'], 400);
        }

        $res = $this->quizRepo->createDiscussion($data);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo chủ đề thảo luận thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không thể tạo thảo luận.'], 500);
        }
    }

    public function deleteDiscussion($discussionId) {
        $this->checkDiscussionOwnership($discussionId);
        $res = $this->quizRepo->deleteDiscussion($discussionId);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa chủ đề thảo luận thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa chủ đề.'], 500);
        }
    }

    public function getReplies($discussionId) {
        $this->checkDiscussionOwnership($discussionId);
        $replies = $this->quizRepo->getRepliesByDiscussion($discussionId);
        $this->jsonResponse(['status' => 'success', 'data' => $replies]);
    }

    public function createReply($discussionId) {
        $this->checkDiscussionOwnership($discussionId);
        $data = $this->getJsonInput();
        $data['discussion_id'] = $discussionId;
        $data['user_id'] = $_SESSION['user']['id'];

        if (empty($data['content'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập nội dung phản hồi.'], 400);
        }

        $res = $this->quizRepo->createReply($data);
        if ($res) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã gửi phản hồi thành công.']);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không thể gửi phản hồi.'], 500);
        }
    }

    /**
     * GET /api/teacher/session/{id}/quizzes-discussions
     * Lấy tất cả quiz và thảo luận của một buổi học cụ thể
     */
    public function getBySession($sessionId) {
        $db = Database::getInstance()->getConnection();

        // Tìm course_id từ session để đếm tổng số sinh viên
        $stmtSess = $db->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
        $stmtSess->execute([$sessionId]);
        $courseId = $stmtSess->fetchColumn();

        $totalStudents = 0;
        if ($courseId) {
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM course_students WHERE course_id = ?");
            $stmtCount->execute([$courseId]);
            $totalStudents = (int)$stmtCount->fetchColumn();
        }

        $stmtQuiz = $db->prepare("
            SELECT qs.*, 
                   (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = qs.id) as question_count,
                   (SELECT COUNT(*) FROM quiz_submissions sub WHERE sub.quiz_id = qs.id) as submission_count
            FROM quiz_sessions qs
            WHERE qs.session_id = :session_id
            ORDER BY qs.start_time ASC
        ");
        $stmtQuiz->execute(['session_id' => $sessionId]);
        $quizzes = $stmtQuiz->fetchAll();

        $stmtDisc = $db->prepare("
            SELECT cd.*, u.full_name as creator_name,
                   (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = cd.id) as reply_count
            FROM class_discussions cd
            JOIN users u ON cd.created_by = u.id
            WHERE cd.session_id = :session_id
            ORDER BY cd.created_at DESC
        ");
        $stmtDisc->execute(['session_id' => $sessionId]);
        $discussions = $stmtDisc->fetchAll();

        $this->jsonResponse(['status' => 'success', 'data' => [
            'quizzes' => $quizzes,
            'discussions' => $discussions,
            'total_students' => $totalStudents
        ]]);
    }

    /**
     * POST /api/teacher/quizzes - Tạo quiz + câu hỏi trong 1 request
     */
    public function createQuizWithQuestions() {
        $teacherId = $_SESSION['user']['id'];
        $data = $this->getJsonInput();

        $sessionId = $data['session_id'] ?? null;
        $title = trim($data['title'] ?? '');
        $duration = (int)($data['duration_minutes'] ?? 10);
        $questions = $data['questions'] ?? [];

        if (!$sessionId || !$title || empty($questions)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu session_id, title hoặc câu hỏi.'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            // Tạo quiz session
            $stmtQ = $db->prepare("INSERT INTO quiz_sessions (session_id, title, start_time, end_time, total_marks) VALUES (:sid, :title, NOW(), DATE_ADD(NOW(), INTERVAL :dur MINUTE), 10.0)");
            $stmtQ->execute(['sid' => $sessionId, 'title' => $title, 'dur' => $duration]);
            $quizId = $db->lastInsertId();

            // Tạo câu hỏi
            foreach ($questions as $q) {
                if (empty(trim($q['question_text'] ?? ''))) continue;
                $stmtQq = $db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (:qid, :text, :a, :b, :c, :d, :ans)");
                $stmtQq->execute([
                    'qid' => $quizId,
                    'text' => $q['question_text'],
                    'a' => $q['option_a'] ?? null,
                    'b' => $q['option_b'] ?? null,
                    'c' => $q['option_c'] ?? null,
                    'd' => $q['option_d'] ?? null,
                    'ans' => $q['correct_answer'] ?? 'A'
                ]);
            }

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo quiz thành công!', 'quiz_id' => $quizId]);
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi tạo quiz: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/teacher/discussions - Tạo thảo luận cho buổi học cụ thể
     */
    public function createDiscussionInSession() {
        $teacherId = $_SESSION['user']['id'];
        $data = $this->getJsonInput();

        $sessionId = $data['session_id'] ?? null;
        $title = trim($data['title'] ?? '');
        $content = trim($data['description'] ?? ''); // Map description từ client thành content

        if (!$sessionId || !$title) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu session_id hoặc tiêu đề.'], 400);
        }

        $db = Database::getInstance()->getConnection();

        // Lấy course_id từ class_sessions
        $stmtSession = $db->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
        $stmtSession->execute([$sessionId]);
        $courseId = $stmtSession->fetchColumn();

        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy thông tin lớp học của buổi học này.'], 400);
            return;
        }

        $stmt = $db->prepare("INSERT INTO class_discussions (session_id, course_id, created_by, title, content, created_at) VALUES (:sid, :cid, :uid, :title, :content, NOW())");
        $stmt->execute(['sid' => $sessionId, 'cid' => $courseId, 'uid' => $teacherId, 'title' => $title, 'content' => $content]);
        $discId = $db->lastInsertId();

        $this->jsonResponse(['status' => 'success', 'message' => 'Tạo chủ đề thảo luận thành công!', 'discussion_id' => $discId]);
    }

    /**
     * POST /api/discussions/{id}/reply - Cả GV và SV đều có thể reply
     */
    public function addReplyByAny($discussionId) {
        // Cho phép cả teacher và student
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getJsonInput();
        $content = trim($data['content'] ?? '');
        if (!$content) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Nội dung không được để trống.'], 400);
        }

        $userId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO discussion_replies (discussion_id, user_id, content, created_at) VALUES (:did, :uid, :content, NOW())");
        $stmt->execute(['did' => $discussionId, 'uid' => $userId, 'content' => $content]);

        // Nếu SV reply → ghi interaction log
        if ($_SESSION['user']['role'] === 'student') {
            $stmtD = $db->prepare("SELECT cd.session_id, cs.course_id FROM class_discussions cd JOIN class_sessions cs ON cd.session_id = cs.id WHERE cd.id = ?");
            $stmtD->execute([$discussionId]);
            $row = $stmtD->fetch();
            if ($row) {
                $stmtLog = $db->prepare("INSERT INTO interaction_logs (student_id, session_id, course_id, type, points, created_at) VALUES (:sid, :sesid, :cid, 'discussion', 2, NOW())");
                $stmtLog->execute(['sid' => $userId, 'sesid' => $row['session_id'], 'cid' => $row['course_id']]);
            }
        }

        $this->jsonResponse(['status' => 'success', 'message' => 'Đã đăng bình luận thành công!']);
    }
}
