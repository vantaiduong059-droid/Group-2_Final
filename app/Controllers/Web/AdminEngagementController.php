<?php
// app/Controllers/Web/AdminEngagementController.php
require_once '../core/Controller.php';
require_once '../app/Models/SystemConfig.php';
require_once '../app/Repositories/SystemConfigRepository.php';
require_once '../app/Models/Engagement.php';
require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class AdminEngagementController extends Controller {
    private $configRepo;
    private $engagementRepo;

    public function __construct() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            } else {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }
        $this->configRepo = new SystemConfigRepository(new SystemConfig());
        $this->engagementRepo = new EngagementRepository(new Engagement());
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
            || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    public function index() {
        $courseModel = new Course();
        $courseRepo = new CourseRepository($courseModel);
        $courses = $courseRepo->getAll();

        $this->view('admin/engagement', [
            'title' => 'Tổng hợp điểm tương tác CPI',
            'courses' => $courses
        ]);
    }

    public function getConfigs() {
        $configs = $this->configRepo->getAllConfigs();
        $this->jsonResponse(['status' => 'success', 'data' => $configs]);
    }

    public function saveConfigs() {
        $data = $this->getJsonInput();
        if (empty($data)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Dữ liệu trống.'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $this->configRepo->updateConfig($key, strval($value));
            }
            $db->commit();
            
            // Sau khi thay đổi quy tắc hệ thống mặc định, tự động tính toán lại CPI cho toàn trường
            $courseModel = new Course();
            $courseRepo = new CourseRepository($courseModel);
            $courses = $courseRepo->getAll();
            foreach ($courses as $c) {
                $this->engagementRepo->syncCourseEngagement($c['id']);
            }

            $this->jsonResponse(['status' => 'success', 'message' => 'Đã lưu cấu hình và đồng bộ điểm CPI thành công.']);
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi lưu cấu hình: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API dành riêng cho Admin - Lấy điểm CPI của sinh viên trong một lớp (không cần role teacher)
     */
    public function getCourseEngagement($courseId) {
        $scores = $this->engagementRepo->getScoresByCourse($courseId);
        $this->jsonResponse(['status' => 'success', 'data' => ['scores' => $scores]]);
    }

    /**
     * API dành riêng cho Admin - Lấy toàn bộ cảnh báo hệ thống
     */
    public function getAllAlerts() {
        require_once '../app/Models/Alert.php';
        require_once '../app/Repositories/AlertRepository.php';
        $alertRepo = new AlertRepository(new Alert());
        $alerts = $alertRepo->getAllAlerts();
        $this->jsonResponse(['status' => 'success', 'data' => $alerts]);
    }
}
