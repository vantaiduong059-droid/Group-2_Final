<?php

require_once '../core/Controller.php';
require_once '../app/Models/Alert.php';
require_once '../app/Repositories/AlertRepository.php';

class AlertApiController extends Controller {
    private $alertRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $alertModel = new Alert();
        $this->alertRepo = new AlertRepository($alertModel);
    }

    public function index() {
        $alerts = $this->alertRepo->getAllAlerts();
        $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
    }

    public function update($id) {
        try {
            $this->alertRepo->markAsRead($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Đã đánh dấu là đã đọc']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật'], 500);
        }
    }
}
