<?php
// app/Controllers/Api/TeacherApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/User.php';
require_once '../app/Repositories/UserRepository.php';

class TeacherApiController extends Controller {
    private $userRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            exit;
        }
        $userModel = new User();
        $this->userRepo = new UserRepository($userModel);
    }

    public function index() {
        $teachers = $this->userRepo->getTeachers();
        $this->jsonResponse(['status' => 'success', 'data' => $teachers]);
    }

    public function store() {
        $data = $this->getJsonInput();
        
        if (empty($data['username']) || empty($data['last_name']) || empty($data['first_name']) || empty($data['email'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'], 400);
        }

        if (!preg_match('/^[\p{L} ]+$/u', $data['last_name']) || !preg_match('/^[\p{L} ]+$/u', $data['first_name'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Họ tên chỉ được chứa chữ cái và khoảng trắng'], 400);
        }

        try {
            $this->userRepo->createTeacher($data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Thêm giảng viên thành công. Mật khẩu mặc định là 123456.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi: Username hoặc Email có thể đã tồn tại.'], 500);
        }
    }

    public function update($id) {
        $data = $this->getJsonInput();
        
        if (empty($data['username']) || empty($data['last_name']) || empty($data['first_name']) || empty($data['email'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'], 400);
        }

        if (!preg_match('/^[\p{L} ]+$/u', $data['last_name']) || !preg_match('/^[\p{L} ]+$/u', $data['first_name'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Họ tên chỉ được chứa chữ cái và khoảng trắng'], 400);
        }

        try {
            $this->userRepo->updateTeacher($id, $data);
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật giảng viên thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi cập nhật. Username/Email có thể bị trùng.'], 500);
        }
    }

    public function destroy($id) {
        try {
            $this->userRepo->delete($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa giảng viên thành công.']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa (giảng viên này có thể đang được phân công dạy khóa học).'], 500);
        }
    }
}
