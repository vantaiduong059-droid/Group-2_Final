<?php
// app/Controllers/Api/AlertApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Alert.php';
require_once '../app/Repositories/AlertRepository.php';

class AlertApiController extends Controller {
    private $alertRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $alertModel = new Alert();
        $this->alertRepo = new AlertRepository($alertModel);
    }

    public function index() {
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
            return;
        }
        $alerts = $this->alertRepo->getAllAlerts();
        $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
    }

    public function update($id) {
        try {
            $alert = $this->alertRepo->getById($id);
            if (!$alert) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Cảnh báo không tồn tại'], 404);
                return;
            }

            // Chỉ cho phép admin hoặc chính học sinh sở hữu cảnh báo cập nhật trạng thái đã đọc
            if ($_SESSION['user']['role'] !== 'admin' && $alert['user_id'] != $_SESSION['user']['id']) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
                return;
            }

            $this->alertRepo->markAsRead($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã đánh dấu là đã đọc']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật'], 500);
        }
    }
}
