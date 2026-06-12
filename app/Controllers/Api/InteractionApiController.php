<?php
// app/Controllers/Api/InteractionApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Interaction.php';
require_once '../app/Repositories/InteractionRepository.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Engagement.php';

class InteractionApiController extends Controller {
    private $interactionRepo;
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        
        $this->interactionRepo = new InteractionRepository(new Interaction());
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    /**
     * Giảng viên cộng điểm phát biểu/tương tác cho sinh viên
     */
    public function store() {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $this->getJsonInput();
        $sessionId = $data['session_id'] ?? null;
        $studentId = $data['student_id'] ?? null;
        $type = $data['type'] ?? 'answer'; // 'question', 'answer', 'discussion'
        $points = (int)($data['points_awarded'] ?? 1);

        if (!$sessionId || !$studentId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng cung cấp session_id và student_id.'], 400);
        }

        try {
            $this->interactionRepo->addLog($sessionId, $studentId, $type, $points);
            
            // Tìm course_id của buổi học để đồng bộ lại CPI của sinh viên này
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT course_id FROM class_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $courseId = $stmt->fetchColumn();

            if ($courseId) {
                // Tính toán lại CPI
                $this->engagementRepo->recalculateScore($courseId, $studentId);
            }

            $this->jsonResponse(['status' => 'success', 'message' => 'Ghi nhận điểm phát biểu thành công!']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi ghi nhận tương tác: ' . $e->getMessage()], 500);
        }
    }
}
