<?php
// app/Controllers/Api/ProfileApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/User.php';
require_once '../app/Repositories/UserRepository.php';

class ProfileApiController extends Controller {
    private $userRepo;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
            exit;
        }
        $this->userRepo = new UserRepository(new User());
    }

    /**
     * GET /api/profile - Lấy thông tin cá nhân
     */
    public function show() {
        $id = $_SESSION['user']['id'];
        $profile = $this->userRepo->getProfile($id);
        if (!$profile) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy người dùng.'], 404);
        }
        $this->jsonResponse(['status' => 'success', 'data' => $profile]);
    }

    /**
     * POST /api/profile - Cập nhật thông tin (full_name, phone, avatar_url)
     */
    public function update() {
        $id = $_SESSION['user']['id'];
        $data = $this->getJsonInput();

        // Validate
        if ((isset($data['last_name']) && trim($data['last_name']) === '') || (isset($data['first_name']) && trim($data['first_name']) === '')) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Họ tên đệm và tên không được để trống.'], 400);
        }
        if (isset($data['last_name']) && !preg_match('/^[\p{L} ]+$/u', $data['last_name'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Họ và tên đệm chỉ được chứa chữ cái và khoảng trắng.'], 400);
        }
        if (isset($data['first_name']) && !preg_match('/^[\p{L} ]+$/u', $data['first_name'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Tên chỉ được chứa chữ cái và khoảng trắng.'], 400);
        }
        if (isset($data['phone']) && $data['phone'] !== '' && !preg_match('/^[0-9+\-\s]{9,15}$/', $data['phone'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Số điện thoại không hợp lệ.'], 400);
        }

        $updateData = [];
        if (isset($data['last_name']))  $updateData['last_name']  = trim($data['last_name']);
        if (isset($data['first_name'])) $updateData['first_name'] = trim($data['first_name']);
        if (isset($data['phone']))      $updateData['phone']      = trim($data['phone']);

        $this->userRepo->updateProfile($id, $updateData);

        // Cập nhật session name
        if (isset($updateData['last_name']) || isset($updateData['first_name'])) {
            $profile = $this->userRepo->getProfile($id);
            $_SESSION['user']['full_name'] = $profile['full_name'];
        }

        $profile = $this->userRepo->getProfile($id);
        $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật thông tin thành công.', 'data' => $profile]);
    }

    /**
     * POST /api/profile/password - Đổi mật khẩu
     */
    public function changePassword() {
        $id = $_SESSION['user']['id'];
        $data = $this->getJsonInput();

        $oldPw  = $data['old_password'] ?? '';
        $newPw  = $data['new_password'] ?? '';
        $confPw = $data['confirm_password'] ?? '';

        if (empty($oldPw) || empty($newPw)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ mật khẩu.'], 400);
        }
        if (strlen($newPw) < 8) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'], 400);
        }
        if ($newPw !== $confPw) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Xác nhận mật khẩu không khớp.'], 400);
        }

        $result = $this->userRepo->changePassword($id, $oldPw, $newPw);
        if ($result['ok']) {
            $this->jsonResponse(['status' => 'success', 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => $result['message']], 400);
        }
    }

    /**
     * POST /api/profile/avatar - Upload avatar
     */
    public function uploadAvatar() {
        $id = $_SESSION['user']['id'];

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng chọn file ảnh hợp lệ.'], 400);
        }

        $file     = $_FILES['avatar'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chỉ hỗ trợ định dạng JPG, PNG, GIF, WebP.'], 400);
        }
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Ảnh quá lớn, tối đa 2MB.'], 400);
        }

        $uploadDir = '../public/assets/images/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $id . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi lưu file ảnh.'], 500);
        }

        $avatarUrl = BASE_URL . '/assets/images/avatars/' . $filename;
        $this->userRepo->updateProfile($id, ['avatar_url' => $avatarUrl]);
        $_SESSION['user']['avatar_url'] = $avatarUrl;

        $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật ảnh đại diện thành công.', 'avatar_url' => $avatarUrl]);
    }
}
