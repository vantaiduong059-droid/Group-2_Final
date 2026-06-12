<?php
// app/Controllers/Web/AdminController.php
require_once '../core/Controller.php';

class AdminController extends Controller {

    public function __construct() {
        // Kiểm tra đăng nhập và phân quyền
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function dashboard() {
        $db = Database::getInstance()->getConnection();
        
        // 1. Tổng số lớp học phần
        $stmt = $db->query("SELECT COUNT(*) FROM courses");
        $totalCourses = $stmt->fetchColumn();

        // 2. Tổng số sinh viên
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
        $totalStudents = $stmt->fetchColumn();

        // 3. Tổng số giảng viên
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
        $totalTeachers = $stmt->fetchColumn();

        // 4. Tỷ lệ chuyên cần chung
        $stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count FROM attendance_records");
        $attData = $stmt->fetch();
        $attendanceRate = ($attData['total'] > 0) ? round(($attData['present_count'] / $attData['total']) * 100, 1) : 0;

        // 5. Thống kê điểm danh (Tròn)
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM attendance_records GROUP BY status");
        $attStatsRaw = $stmt->fetchAll();
        $attStats = ['present' => 0, 'late' => 0, 'absent' => 0];
        foreach($attStatsRaw as $row) {
            if($row['status'] == 'present') $attStats['present'] = $row['count'];
            if($row['status'] == 'late') $attStats['late'] = $row['count'];
            if($row['status'] == 'absent') $attStats['absent'] = $row['count'];
        }

        // 6. Học sinh mới nhập học (Top 5)
        $stmt = $db->query("SELECT id, full_name, created_at FROM users WHERE role = 'student' ORDER BY id DESC LIMIT 5");
        $newStudents = $stmt->fetchAll();

        // 7. Sự kiện/Buổi học sắp tới (Top 3)
        $stmt = $db->query("
            SELECT s.session_date, s.start_time, c.name as course_name 
            FROM class_sessions s 
            JOIN courses c ON s.course_id = c.id 
            WHERE s.session_date >= CURDATE() 
            ORDER BY s.session_date ASC, s.start_time ASC 
            LIMIT 3
        ");
        $upcomingEvents = $stmt->fetchAll();

        // 8. Dữ liệu biểu đồ đường (6 tháng gần nhất)
        $stmt = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM users 
            WHERE role = 'student' 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $chartDataRaw = $stmt->fetchAll();
        $chartLabels = [];
        $chartValues = [];
        
        // Tạo chuỗi 6 tháng
        for ($i = 5; $i >= 0; $i--) {
            $monthStr = date('Y-m', strtotime("-$i months"));
            $chartLabels[] = 'Tháng ' . date('n', strtotime("-$i months"));
            
            // Tìm trong mảng dữ liệu có tháng này không
            $found = false;
            foreach($chartDataRaw as $row) {
                if($row['month'] === $monthStr) {
                    $chartValues[] = (int)$row['count'];
                    $found = true;
                    break;
                }
            }
            if(!$found) $chartValues[] = 0;
        }
        
        // Cần cộng dồn số sinh viên các tháng trước đó nếu muốn đường biểu đồ đi lên?
        // Nhưng đây là "Số sinh viên đăng ký", nên ta có thể để nguyên hoặc cộng dồn.
        // Giả sử tổng số sinh viên hiện tại là totalStudents, ta có thể giả lập số liệu lùi lại.
        // Nhưng để dễ nhất, cứ để chartValues là số lượng tăng thêm mỗi tháng, HOẶC làm số liệu cộng dồn.
        $cumulativeValues = [];
        $currentTotal = $totalStudents - array_sum($chartValues);
        foreach($chartValues as $val) {
            $currentTotal += $val;
            $cumulativeValues[] = $currentTotal;
        }

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'attendanceRate' => $attendanceRate,
            'attStats' => $attStats,
            'newStudents' => $newStudents,
            'upcomingEvents' => $upcomingEvents,
            'chartLabels' => json_encode($chartLabels),
            'chartValues' => json_encode($cumulativeValues)
        ]);
    }

    public function courses() {
        $this->view('admin/courses', ['title' => 'Quản lý Khóa học']);
    }

    public function students() {
        $this->view('admin/students', ['title' => 'Quản lý Sinh viên']);
    }

    public function teachers() {
        $this->view('admin/teachers', ['title' => 'Quản lý Giảng viên']);
    }

    public function sessions() {
        $this->view('admin/sessions', ['title' => 'Quản lý Buổi học']);
    }

    public function alerts() {
        $this->view('admin/alerts', ['title' => 'Hệ thống Cảnh báo']);
    }
}
