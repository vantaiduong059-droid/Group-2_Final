<?php
// Script fix dữ liệu: chạy qua browser tại http://localhost/Group-2_Final_Student/database/fix_testdata.php
header('Content-Type: text/html; charset=UTF-8');

require_once '../config/database.php';
require_once '../config/config.php';

$db = Database::getInstance()->getConnection();
$db->exec("SET NAMES utf8mb4");

$results = [];

try {
    // ===================================================
    // FIX 1: Xóa alert cũ bị lỗi encoding, thêm lại đúng
    // ===================================================
    $db->exec("DELETE FROM alerts WHERE user_id IN (242, 234)");
    $results[] = "✅ Đã xóa alert cũ bị lỗi encoding";

    $stmt = $db->prepare("INSERT INTO alerts (user_id, course_id, message, is_read) VALUES (?, ?, ?, ?)");
    
    $stmt->execute([242, 27, 'Cảnh báo: Bạn đã vắng 4 buổi trong môn Cơ sở dữ liệu phân tán — vượt ngưỡng cho phép (3 buổi). Hãy liên hệ giảng viên ngay.', 0]);
    $results[] = "✅ Đã thêm alert vắng nhiều (student)";
    
    $stmt->execute([242, 27, 'Cảnh báo: Điểm CPI của bạn trong môn Cơ sở dữ liệu phân tán hiện dưới ngưỡng tối thiểu (50/100). Hãy tham gia tích cực hơn.', 0]);
    $results[] = "✅ Đã thêm alert CPI thấp (student)";
    
    // Alert cho giảng viên teacher3 (id=234, giảng viên course 27)
    $stmt->execute([234, 27, 'Sinh viên Nguyễn Hải Anh (student1) đã vắng 4 buổi trong môn của bạn. Cần liên hệ hỗ trợ sinh viên.', 0]);
    $results[] = "✅ Đã thêm alert cho giảng viên (teacher3)";

    // ===================================================
    // FIX 2: Xóa quiz data cũ, thêm lại đúng
    // ===================================================
    $db->exec("DELETE FROM quiz_submissions WHERE student_id = 242");
    $results[] = "✅ Đã xóa quiz_submissions cũ";

    $db->exec("DELETE FROM quiz_sessions WHERE id IN (501, 502)");
    $results[] = "✅ Đã xóa quiz_sessions cũ";

    // Thêm quiz_sessions đúng (session 129 và 130 thuộc course 25 - AI)
    $stmtQ = $db->prepare("INSERT INTO quiz_sessions (id, session_id, title, start_time, end_time, total_marks) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=title");
    $stmtQ->execute([501, 129, 'Quiz 1 - Nhập môn AI & Machine Learning', '2026-06-17 09:30:00', '2026-06-17 09:45:00', 10]);
    $results[] = "✅ Đã thêm quiz_session 501 (Quiz 1 - AI)";
    
    $stmtQ->execute([502, 130, 'Quiz 2 - Thuật toán tìm kiếm & Tối ưu', '2026-06-24 09:30:00', '2026-06-24 09:45:00', 10]);
    $results[] = "✅ Đã thêm quiz_session 502 (Quiz 2 - Tìm kiếm)";

    // Thêm quiz_submissions
    $stmtQS = $db->prepare("INSERT INTO quiz_submissions (quiz_id, student_id, score) VALUES (?, ?, ?)");
    $stmtQS->execute([501, 242, 8.5]);
    $results[] = "✅ Đã thêm quiz_submission: Quiz 1 - điểm 8.5/10";
    
    $stmtQS->execute([502, 242, 4.0]);
    $results[] = "✅ Đã thêm quiz_submission: Quiz 2 - điểm 4.0/10";

    // ===================================================
    // FIX 3: Sửa leave_requests nếu bị lỗi cột 'eason'
    // ===================================================
    // Kiểm tra cột thực tế của bảng
    $cols = $db->query("SHOW COLUMNS FROM leave_requests")->fetchAll(PDO::FETCH_COLUMN);
    $results[] = "ℹ️ Các cột leave_requests: " . implode(', ', $cols);

    // Xóa leave_requests cũ và thêm lại
    $db->exec("DELETE FROM leave_requests WHERE student_id = 242");
    $results[] = "✅ Đã xóa leave_requests cũ";

    $hasReason = in_array('reason', $cols);
    $hasEason = in_array('eason', $cols);
    $col = $hasReason ? 'reason' : ($hasEason ? 'eason' : null);
    
    if ($col) {
        $stmtLR = $db->prepare("INSERT INTO leave_requests (student_id, session_id, `$col`, status) VALUES (?, ?, ?, ?)");
        $stmtLR->execute([242, 133, 'Tôi bị ốm và có giấy xác nhận của bệnh viện. Kính mong thầy/cô xem xét cho phép vắng có phép.', 'approved']);
        $results[] = "✅ Đã thêm leave_request: approved (buổi 133)";
        
        $stmtLR->execute([242, 135, 'Gia đình có việc đột xuất, tôi xin phép nghỉ buổi học ngày 29/07.', 'pending']);
        $results[] = "✅ Đã thêm leave_request: pending (buổi 135)";
        
        $stmtLR->execute([242, 130, 'Tôi muốn nghỉ để đi chơi với bạn bè.', 'rejected']);
        $results[] = "✅ Đã thêm leave_request: rejected (buổi 130)";
    } else {
        $results[] = "⚠️ Không tìm thấy cột reason/eason trong leave_requests!";
    }

    // ===================================================
    // KIỂM TRA KẾT QUẢ
    // ===================================================
    $checkAlerts = $db->query("SELECT COUNT(*) FROM alerts WHERE user_id = 242")->fetchColumn();
    $checkQuiz = $db->query("SELECT COUNT(*) FROM quiz_submissions WHERE student_id = 242")->fetchColumn();
    $checkLeave = $db->query("SELECT COUNT(*) FROM leave_requests WHERE student_id = 242")->fetchColumn();
    
    $results[] = "";
    $results[] = "📊 KẾT QUẢ CUỐI:";
    $results[] = "  - Alerts (student): $checkAlerts bản ghi";
    $results[] = "  - Quiz submissions: $checkQuiz bản ghi";
    $results[] = "  - Leave requests: $checkLeave bản ghi";

} catch (Exception $e) {
    $results[] = "❌ LỖI: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Fix Test Data</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        .ok { color: #6a9955; }
        .err { color: #f44747; }
        .info { color: #9cdcfe; }
        .done { background: #264f78; padding: 10px; border-radius: 6px; margin-top: 20px; }
        a { color: #4ec9b0; }
    </style>
</head>
<body>
<h1>🔧 Fix Test Data — Student Portal</h1>
<?php foreach ($results as $r): ?>
    <div class="<?= str_starts_with($r, '✅') ? 'ok' : (str_starts_with($r, '❌') ? 'err' : (str_starts_with($r, 'ℹ️') || str_starts_with($r, '📊') ? 'info' : '')) ?>">
        <?= htmlspecialchars($r) ?>
    </div>
<?php endforeach; ?>
<div class="done">
    ✅ Hoàn tất! <a href="/Group-2_Final_Student/public/login">→ Quay lại đăng nhập để kiểm tra</a>
</div>
</body>
</html>
