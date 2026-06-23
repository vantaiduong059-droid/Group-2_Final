<?php
// app/Controllers/Api/AlertApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Alert.php';
require_once '../app/Repositories/AlertRepository.php';

class AlertApiController extends Controller {
    private $alertRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'student'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $alertModel = new Alert();
        $this->alertRepo = new AlertRepository($alertModel);
    }

    public function index() {
        if ($_SESSION['user']['role'] === 'student') {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT a.*, u.full_name as user_name, u.role, c.name as course_name 
                FROM alerts a
                JOIN users u ON a.user_id = u.id
                JOIN courses c ON a.course_id = c.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$_SESSION['user']['id']]);
            $alerts = $stmt->fetchAll();
            $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
            return;
        }
        $alerts = $this->alertRepo->getAllAlerts();
        $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
    }

    public function update($id) {
        $db = Database::getInstance()->getConnection();
        if ($_SESSION['user']['role'] === 'student') {
            $stmtCheck = $db->prepare("SELECT user_id FROM alerts WHERE id = ?");
            $stmtCheck->execute([$id]);
            $alertUserId = $stmtCheck->fetchColumn();
            if ($alertUserId != $_SESSION['user']['id']) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
                return;
            }
        }
        try {
            $this->alertRepo->markAsRead($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã đánh dấu là đã đọc']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật'], 500);
        }
    }

    public function assignAdvisor($id) {
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
            return;
        }
        $data = $this->getJsonInput();
        $advisorId = isset($data['advisor_id']) && $data['advisor_id'] !== '' ? (int)$data['advisor_id'] : null;

        try {
            $this->alertRepo->assignAdvisor($id, $advisorId);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã gán cố vấn học tập thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi gán cố vấn: ' . $e->getMessage()], 500);
        }
    }
}
