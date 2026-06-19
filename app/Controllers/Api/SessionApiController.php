<?php
// app/Controllers/Api/SessionApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/ClassSession.php';
require_once '../app/Repositories/SessionRepository.php';

class SessionApiController extends Controller {
    private $sessionRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'teacher'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $sessionModel = new ClassSession();
        $this->sessionRepo = new SessionRepository($sessionModel);
    }

    public function index() {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $courseId = $_GET['course_id'] ?? null;
        
        if ($start && $end) {
            $sessions = $this->sessionRepo->getSessionsByDateRange($start, $end, $courseId);
        } else {
            $sessions = $this->sessionRepo->getAllSessions();
        }
        $this->jsonResponse(['status' => 'success', 'data' => $sessions]);
    }

    private function checkSessionConflict($date, $start, $end, $room, $excludeId = null) {
        return ScheduleHelper::checkSessionConflict($date, $start, $end, $room, $excludeId);
    }

    public function checkConflict() {
        $date = $_GET['date'] ?? null;
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $room = $_GET['room'] ?? null;
        $excludeId = $_GET['exclude_id'] ?? null;
        
        if (empty($date) || empty($start) || empty($end) || empty($room)) {
            $this->jsonResponse(['status' => 'success', 'conflict' => false]);
            return;
        }
        
        if (strlen($start) === 5) $start .= ':00';
        if (strlen($end) === 5) $end .= ':00';
        
        $conflict = $this->checkSessionConflict($date, $start, $end, $room, $excludeId);
        if ($conflict) {
            $timeRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            $dateStr = date('d/m/Y', strtotime($conflict['session_date']));
            $this->jsonResponse([
                'status' => 'conflict',
                'message' => "Phòng {$conflict['room']} đã có buổi học [{$conflict['course_code']} - {$conflict['course_name']}] vào {$timeRange} ngày {$dateStr}, vui lòng chọn phòng hoặc giờ khác"
            ]);
        } else {
            $this->jsonResponse(['status' => 'success', 'conflict' => false]);
        }
    }

    public function store() {
        $data = $this->getJsonInput();
        
        if (empty($data['course_id']) || empty($data['session_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }

        // Chống trùng
        $conflict = $this->checkSessionConflict($data['session_date'], $data['start_time'], $data['end_time'], $data['room'] ?? '');
        if ($conflict) {
            $timeRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            $dateStr = date('d/m/Y', strtotime($conflict['session_date']));
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Phòng {$conflict['room']} đã có buổi học [{$conflict['course_code']} - {$conflict['course_name']}] vào {$timeRange} ngày {$dateStr}, vui lòng chọn phòng hoặc giờ khác"
            ], 400);
            return;
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

        // Chống trùng
        $conflict = $this->checkSessionConflict($data['session_date'], $data['start_time'], $data['end_time'], $data['room'] ?? '', $id);
        if ($conflict) {
            $timeRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            $dateStr = date('d/m/Y', strtotime($conflict['session_date']));
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Phòng {$conflict['room']} đã có buổi học [{$conflict['course_code']} - {$conflict['course_name']}] vào {$timeRange} ngày {$dateStr}, vui lòng chọn phòng hoặc giờ khác"
            ], 400);
            return;
        }

        try {
            $this->sessionRepo->updateSession($id, $data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật buổi học.'], 500);
        }
    }

    public function show($id) {
        $session = $this->sessionRepo->getSessionDetails($id);
        if ($session) {
            $this->jsonResponse(['status' => 'success', 'data' => $session]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy buổi học.'], 404);
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
