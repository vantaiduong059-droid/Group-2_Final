<?php
// app/Controllers/Api/CourseApiController.php
require_once '../core/Controller.php';
require_once '../app/Models/Course.php';
require_once '../app/Repositories/CourseRepository.php';

class CourseApiController extends Controller {
    private $courseRepo;

    public function __construct() {
        $courseModel = new Course();
        $this->courseRepo = new CourseRepository($courseModel);
    }

    // Lấy danh sách khóa học (Read)
    public function index() {
        $courses = $this->courseRepo->getAll();
        $this->jsonResponse(['status' => 'success', 'data' => $courses]);
    }

    // Lấy chi tiết 1 khóa học kèm lịch học cố định
    public function show($id) {
        $course = $this->courseRepo->getCourseDetails($id);
        if ($course) {
            $db = Database::getInstance()->getConnection();
            $stmtSched = $db->prepare("SELECT * FROM course_schedules WHERE course_id = ? ORDER BY day_of_week ASC, start_time ASC");
            $stmtSched->execute([$id]);
            $course['schedules'] = $stmtSched->fetchAll();
            
            $this->jsonResponse(['status' => 'success', 'data' => $course]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Không tìm thấy khóa học'], 404);
        }
    }

    // Kiểm tra trùng lịch học cố định (phòng + thứ + giờ giao nhau)
    private function checkScheduleConflict($schedules, $excludeCourseId = null) {
        return ScheduleHelper::checkScheduleConflict($schedules, $excludeCourseId);
    }

    // Sinh tự động các buổi học trong class_sessions
    private function generateClassSessions($courseId, $schedules, $totalSessions) {
        if (empty($schedules) || $totalSessions <= 0) return;
        
        $db = Database::getInstance()->getConnection();
        
        // Sắp xếp schedules theo thứ tự thời gian để sinh buổi học hợp lý
        usort($schedules, function($a, $b) {
            if ($a['day_of_week'] == $b['day_of_week']) {
                return strcmp($a['start_time'], $b['start_time']);
            }
            return $a['day_of_week'] - $b['day_of_week'];
        });

        // Gom nhóm theo thứ để kiểm tra cho nhanh
        $schedByDay = [];
        foreach ($schedules as $sc) {
            $schedByDay[(int)$sc['day_of_week']][] = $sc;
        }

        $createdCount = 0;
        $currentDate = date('Y-m-d');
        $loopCount = 0;

        while ($createdCount < $totalSessions && $loopCount < 365) {
            $n = (int)date('N', strtotime($currentDate)); // 1 (Thứ 2) -> 7 (Chủ nhật)
            $dbDay = ($n === 7) ? 8 : ($n + 1); // 2 -> 8

            if (isset($schedByDay[$dbDay])) {
                foreach ($schedByDay[$dbDay] as $sc) {
                    if ($createdCount >= $totalSessions) break;

                    // Lấy ca học hoặc tính toán period
                    $periodStr = $sc['period'] ?? '1 - 3';

                    $stmt = $db->prepare("
                        INSERT INTO class_sessions (course_id, session_date, start_time, end_time, status, room, period)
                        VALUES (?, ?, ?, ?, 'scheduled', ?, ?)
                    ");
                    $stmt->execute([
                        $courseId,
                        $currentDate,
                        $sc['start_time'],
                        $sc['end_time'],
                        $sc['room'],
                        $periodStr
                    ]);
                    $createdCount++;
                }
            }
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            $loopCount++;
        }
    }

    // Thêm mới khóa học (Create)
    public function store() {
        $data = $this->getJsonInput();
        
        // Basic Validation
        if (empty($data['code']) || empty($data['class_code']) || empty($data['name']) || empty($data['credits'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc'], 400);
        }

        // Tính số buổi học mặc định nếu chưa nhập (credits * 5)
        if (empty($data['total_sessions'])) {
            $data['total_sessions'] = (int)$data['credits'] * 5;
        }

        // Tính số tiết tự động nếu chưa có
        if (empty($data['periods'])) {
            $data['periods'] = (int)$data['credits'] * 15;
        }

        // Kiểm tra trùng lịch học
        $conflict = $this->checkScheduleConflict($data['schedules'] ?? []);
        if ($conflict) {
            $dayNames = [
                2 => 'Thứ hai', 3 => 'Thứ ba', 4 => 'Thứ tư', 
                5 => 'Thứ năm', 6 => 'Thứ sáu', 7 => 'Thứ bảy', 8 => 'Chủ nhật'
            ];
            $dayLabel = $dayNames[$conflict['day_of_week']] ?? 'N/A';
            $timeRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Lịch học bị trùng: Phòng {$conflict['room']} vào {$dayLabel} ({$timeRange}) đã được sử dụng bởi lớp \"{$conflict['course_name']}\"."
            ], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            
            // 1. Tạo khóa học
            $courseId = $this->courseRepo->createCourse($data);
            
            // 2. Lưu lịch học cố định
            $schedules = $data['schedules'] ?? [];
            if (!empty($schedules)) {
                $stmtSched = $db->prepare("
                    INSERT INTO course_schedules (course_id, day_of_week, start_time, end_time, room)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($schedules as $sc) {
                    $stmtSched->execute([
                        $courseId,
                        $sc['day_of_week'],
                        $sc['start_time'],
                        $sc['end_time'],
                        $sc['room']
                    ]);
                }
            }

            // 3. Tự động sinh danh sách buổi học cụ thể
            $this->generateClassSessions($courseId, $schedules, $data['total_sessions']);

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Tạo lớp học phần và lịch học thành công']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // Sửa khóa học (Update)
    public function update($id) {
        $data = $this->getJsonInput();
        
        if (empty($data['code']) || empty($data['class_code']) || empty($data['name']) || empty($data['credits'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc'], 400);
        }

        // Tính số buổi học mặc định nếu chưa nhập (credits * 5)
        if (empty($data['total_sessions'])) {
            $data['total_sessions'] = (int)$data['credits'] * 5;
        }

        // Tính số tiết tự động nếu chưa có
        if (empty($data['periods'])) {
            $data['periods'] = (int)$data['credits'] * 15;
        }

        // Kiểm tra trùng lịch học
        $conflict = $this->checkScheduleConflict($data['schedules'] ?? [], $id);
        if ($conflict) {
            $dayNames = [
                2 => 'Thứ hai', 3 => 'Thứ ba', 4 => 'Thứ tư', 
                5 => 'Thứ năm', 6 => 'Thứ sáu', 7 => 'Thứ bảy', 8 => 'Chủ nhật'
            ];
            $dayLabel = $dayNames[$conflict['day_of_week']] ?? 'N/A';
            $timeRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Lịch học bị trùng: Phòng {$conflict['room']} vào {$dayLabel} ({$timeRange}) đã được sử dụng bởi lớp \"{$conflict['course_name']}\"."
            ], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            // 1. Cập nhật thông tin lớp học phần
            $this->courseRepo->updateCourse($id, $data);

            // Kiểm tra xem lớp học phần đã phát sinh điểm danh chưa
            $stmtCheckAtt = $db->prepare("
                SELECT COUNT(*) FROM attendance_records ar
                JOIN class_sessions cs ON ar.session_id = cs.id
                WHERE cs.course_id = ?
            ");
            $stmtCheckAtt->execute([$id]);
            $hasAttendance = $stmtCheckAtt->fetchColumn() > 0;

            // 2. Cập nhật lịch học cố định
            $db->prepare("DELETE FROM course_schedules WHERE course_id = ?")->execute([$id]);
            $schedules = $data['schedules'] ?? [];
            if (!empty($schedules)) {
                $stmtSched = $db->prepare("
                    INSERT INTO course_schedules (course_id, day_of_week, start_time, end_time, room)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($schedules as $sc) {
                    $stmtSched->execute([
                        $id,
                        $sc['day_of_week'],
                        $sc['start_time'],
                        $sc['end_time'],
                        $sc['room']
                    ]);
                }
            }

            // 3. Xử lý danh sách các buổi học cụ thể
            if ($hasAttendance) {
                // Đã có điểm danh: Không được xóa và sinh lại, bỏ qua việc tự sinh lại class_sessions
            } else {
                // Chưa có điểm danh: Xóa class_sessions cũ và sinh lại từ đầu
                $db->prepare("DELETE FROM class_sessions WHERE course_id = ?")->execute([$id]);
                $this->generateClassSessions($id, $schedules, $data['total_sessions']);
            }

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Cập nhật lớp học phần và lịch học thành công']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // Xóa khóa học (Delete)
    public function destroy($id) {
        try {
            $this->courseRepo->delete($id);
            $this->jsonResponse(['status' => 'success', 'message' => 'Xóa khóa học thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa (ràng buộc dữ liệu)'], 500);
        }
    }

    /**
     * Lấy danh sách sinh viên của lớp học phần
     */
    public function getStudents($courseId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.email 
            FROM users u
            JOIN course_students cs ON u.id = cs.student_id
            WHERE cs.course_id = ?
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll();
        $this->jsonResponse(['status' => 'success', 'data' => $students]);
    }

    /**
     * Thêm sinh viên vào lớp học phần
     */
    public function addStudent($courseId) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        
        $data = $this->getJsonInput();
        $studentId = $data['student_id'] ?? null;
        if (!$studentId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Thiếu ID sinh viên'], 400);
        }

        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO course_students (course_id, student_id) VALUES (?, ?)");
            $stmt->execute([$courseId, $studentId]);
            
            // Khởi tạo điểm chuyên cần tích lũy mặc định
            $stmtScore = $db->prepare("
                INSERT INTO engagement_scores (course_id, student_id, attendance_points, interaction_points, total_score)
                VALUES (?, ?, 0, 0, 100)
                ON DUPLICATE KEY UPDATE total_score = 100
            ");
            $stmtScore->execute([$courseId, $studentId]);

            $this->jsonResponse(['status' => 'success', 'message' => 'Thêm sinh viên vào lớp thành công']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Sinh viên này đã tham gia lớp học rồi.'], 500);
        }
    }

    /**
     * Xóa sinh viên khỏi lớp học phần
     */
    public function removeStudent($courseId, $studentId) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM course_students WHERE course_id = ? AND student_id = ?");
            $stmt->execute([$courseId, $studentId]);
            
            $stmtScore = $db->prepare("DELETE FROM engagement_scores WHERE course_id = ? AND student_id = ?");
            $stmtScore->execute([$courseId, $studentId]);

            $this->jsonResponse(['status' => 'success', 'message' => 'Đã xóa sinh viên khỏi lớp học phần']);
        } catch (Exception $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi xóa sinh viên: ' . $e->getMessage()], 500);
        }
    }
}

