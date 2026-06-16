<?php
// app/Controllers/Api/NotificationApiController.php
require_once '../core/Controller.php';
require_once '../config/database.php';

class NotificationApiController extends Controller {

    // Lấy danh sách thông báo và số lượng chưa đọc (hỗ trợ phân trang)
    public function index() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $userId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $sql = "
            SELECT id, title, message, link, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();

        // Đếm số thông báo chưa đọc
        $stmtUnread = $db->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmtUnread->execute([$userId]);
        $unreadCount = $stmtUnread->fetchColumn();

        // Đếm tổng số thông báo để phân trang
        $stmtTotal = $db->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = ?
        ");
        $stmtTotal->execute([$userId]);
        $totalCount = $stmtTotal->fetchColumn();

        $this->jsonResponse([
            'status' => 'success',
            'unread_count' => (int)$unreadCount,
            'total_count' => (int)$totalCount,
            'data' => $notifications
        ]);
    }

    // Đánh dấu tất cả thông báo là đã đọc
    public function markAsRead() {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $userId = $_SESSION['user']['id'];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.'
        ]);
    }
}
