<?php
// app/Controllers/Api/SessionApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/ClassSession.php';
require_once '../app/Repositories/SessionRepository.php';

class SessionApiController extends Controller {
    private $sessionRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $sessionModel = new ClassSession();
        $this->sessionRepo = new SessionRepository($sessionModel);
    }

    public function index() {
        $sessions = $this->sessionRepo->getAllSessions();
        $this->jsonResponse(['status' => 'success', 'data' => $sessions]);
    }

    public function store() {
        $data = $this->getJsonInput();
        
        if (empty($data['course_id']) || empty($data['session_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }

        try {
            $this->sessionRepo->createSession($data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo buổi học thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi tạo buổi học.'], 500);
        }
    }

    public function update($id) {
        $data = $this->getJsonInput();
        
        if (empty($data['course_id']) || empty($data['session_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }

        try {
            $this->sessionRepo->updateSession($id, $data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật buổi học.'], 500);
        }
    }

    public function destroy($id) {
        try {
            $this->sessionRepo->delete($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa buổi học thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa (có thể do ràng buộc dữ liệu).'], 500);
        }
    }
}
