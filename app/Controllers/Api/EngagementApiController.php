<?php
// app/Controllers/Api/EngagementApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Engagement.php';
require_once '../app/Repositories/EngagementRepository.php';

class EngagementApiController extends Controller {
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    /**
     * Đồng bộ tính toán lại CPI cho khóa học
     */
    public function calculate() {
        if ($_SESSION['user']['role'] === 'student') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $this->getJsonInput();
        $courseId = $data['course_id'] ?? null;

        if (!$courseId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu ID khóa học.'], 400);
        }

        try {
            $success = $this->engagementRepo->syncCourseEngagement($courseId);
            if ($success) {
                // Lấy bảng điểm mới sau khi đồng bộ
                $scores = $this->engagementRepo->getScoresByCourse($courseId);
                $this->jsonResponse([
                    'status' => 'success', 
                    'message' => 'Đã tính toán và đồng bộ lại bảng điểm chuyên cần của lớp.',
                    'data' => $scores
                ]);
            } else {
                $this->jsonResponse(['status' => 'error', 'message' => 'Đã xảy ra lỗi khi tính toán.'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}
