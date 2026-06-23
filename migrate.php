<?php
// Tạm thời chạy file này để update CSDL
chdir(__DIR__ . '/public');
require_once 'core/Model.php';

class Migrator extends Model {
    private function addColumn($table, $column, $definition) {
        try {
            $this->db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Thêm cột `$column` vào bảng `$table` thành công.<br>";
        } catch (Exception $e) {
            echo "Cột `$column` đã tồn tại hoặc lỗi: " . $e->getMessage() . "<br>";
        }
    }

    public function run() {
        try {
            // Cập nhật bảng users để tách last_name và first_name
            $stmtCheck = $this->db->query("SHOW COLUMNS FROM `users` LIKE 'first_name'");
            if (!$stmtCheck->fetch()) {
                // Thêm last_name và first_name
                $this->db->exec("ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT ''");
                $this->db->exec("ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT ''");
                echo "Thêm cột `last_name` và `first_name` vào bảng `users` thành công.<br>";
                
                // Tách tên cũ
                $users = $this->db->query("SELECT id, full_name FROM `users`")->fetchAll();
                $stmtUpdate = $this->db->prepare("UPDATE `users` SET `last_name` = ?, `first_name` = ? WHERE `id` = ?");
                foreach ($users as $user) {
                    $fullName = trim($user['full_name']);
                    $parts = explode(' ', $fullName);
                    if (count($parts) > 1) {
                        $firstName = array_pop($parts);
                        $lastName = implode(' ', $parts);
                    } else {
                        $firstName = $fullName;
                        $lastName = '';
                    }
                    $stmtUpdate->execute([$lastName, $firstName, $user['id']]);
                }
                echo "Đã tách và di chuyển họ tên cho " . count($users) . " người dùng.<br>";
                
                // Chuyển full_name thành Generated Column
                $this->db->exec("ALTER TABLE `users` DROP COLUMN `full_name`");
                $this->db->exec("ALTER TABLE `users` ADD COLUMN `full_name` VARCHAR(151) GENERATED ALWAYS AS (CONCAT(last_name, ' ', first_name)) STORED");
                echo "Đã chuyển đổi cột `full_name` thành GENERATED COLUMN.<br>";
            } else {
                echo "Cột `first_name` đã tồn tại trong bảng `users`. Bỏ qua di chuyển họ tên.<br>";
            }

            // Cập nhật bảng courses
            $this->addColumn('courses', 'rule_present_points', "INT DEFAULT 2");
            $this->addColumn('courses', 'rule_late_points', "INT DEFAULT 1");
            $this->addColumn('courses', 'rule_absent_points', "INT DEFAULT 0");
            $this->addColumn('courses', 'rule_interaction_points', "INT DEFAULT 1");
            $this->addColumn('courses', 'rule_attendance_weight', "INT DEFAULT 50");
            $this->addColumn('courses', 'rule_quiz_weight', "INT DEFAULT 50");

            // Cập nhật bảng class_sessions
            $this->addColumn('class_sessions', 'attendance_code', "VARCHAR(10) DEFAULT NULL");
            $this->addColumn('class_sessions', 'attendance_expires_at', "DATETIME DEFAULT NULL");
            $this->addColumn('class_sessions', 'qr_token', "VARCHAR(255) DEFAULT NULL");
            $this->addColumn('class_sessions', 'room', "VARCHAR(100) DEFAULT 'Phòng học 102, số 1 Phan Tây Nhạc'");
            $this->addColumn('class_sessions', 'period', "VARCHAR(50) DEFAULT '1 - 3'");
            $this->addColumn('class_sessions', 'note', "TEXT DEFAULT NULL");

            // Thêm bảng majors
            $this->db->exec("CREATE TABLE IF NOT EXISTS `majors` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Đã tạo bảng `majors` thành công.<br>";

            // Thêm ngành mẫu nếu bảng rỗng
            $stmtMajorsCount = $this->db->query("SELECT COUNT(*) FROM `majors`");
            if ($stmtMajorsCount->fetchColumn() == 0) {
                $majorsData = [
                    ['CN1', 'Tin học và Kỹ thuật máy tính'],
                    ['CN2', 'Công nghệ thông tin'],
                    ['CN3', 'Khoa học máy tính'],
                    ['CN4', 'Hệ thống thông tin'],
                    ['CN5', 'Kỹ thuật phần mềm'],
                    ['CN6', 'An toàn thông tin']
                ];
                $stmtInsertMajor = $this->db->prepare("INSERT INTO `majors` (code, name) VALUES (?, ?)");
                foreach ($majorsData as $major) {
                    $stmtInsertMajor->execute($major);
                }
                echo "Đã sinh dữ liệu ngành mẫu.<br>";
            }

            // Cập nhật bảng users
            $this->addColumn('users', 'cohort', "VARCHAR(20) DEFAULT NULL");
            $this->addColumn('users', 'major_id', "INT DEFAULT NULL");
            try {
                $this->db->exec("ALTER TABLE `users` ADD CONSTRAINT `fk_users_major` FOREIGN KEY (`major_id`) REFERENCES `majors`(`id`) ON DELETE SET NULL");
                echo "Đã thêm khóa ngoại major_id vào bảng `users`.<br>";
            } catch (Exception $e) {
                echo "Khóa ngoại major_id đã tồn tại hoặc lỗi: " . $e->getMessage() . "<br>";
            }

            // Tự sinh khóa và ngành cho sinh viên hiện tại chưa có
            $studentsWithoutInfo = $this->db->query("SELECT id FROM `users` WHERE role = 'student' AND (cohort IS NULL OR major_id IS NULL)")->fetchAll();
            if (count($studentsWithoutInfo) > 0) {
                $cohorts = ['K65', 'K66', 'K67', 'K68'];
                $majorIds = $this->db->query("SELECT id FROM `majors`")->fetchAll(PDO::FETCH_COLUMN);
                
                $stmtUpdateStudentInfo = $this->db->prepare("UPDATE `users` SET cohort = ?, major_id = ? WHERE id = ?");
                foreach ($studentsWithoutInfo as $std) {
                    $randomCohort = $cohorts[array_rand($cohorts)];
                    $randomMajorId = $majorIds[array_rand($majorIds)];
                    $stmtUpdateStudentInfo->execute([$randomCohort, $randomMajorId, $std['id']]);
                }
                echo "Đã tự động gán Khóa và Ngành mẫu cho " . count($studentsWithoutInfo) . " sinh viên.<br>";
            }

            // Thêm bảng course_schedules
            $this->db->exec("CREATE TABLE IF NOT EXISTS `course_schedules` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `course_id` INT NOT NULL,
                `day_of_week` INT NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `room` VARCHAR(100) NOT NULL,
                FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Đã tạo bảng `course_schedules` thành công.<br>";

            // Cập nhật bảng courses
            $this->addColumn('courses', 'total_sessions', "INT DEFAULT 15");

            // Cập nhật thảo luận & chấm điểm
            $this->addColumn('class_discussions', 'session_id', "INT DEFAULT NULL");
            try {
                $this->db->exec("ALTER TABLE `class_discussions` ADD CONSTRAINT `fk_discussions_session` FOREIGN KEY (`session_id`) REFERENCES `class_sessions`(`id`) ON DELETE CASCADE");
                echo "Đã thêm khóa ngoại `session_id` cho bảng `class_discussions`.<br>";
            } catch (Exception $e) {
                echo "Khóa ngoại `session_id` đã tồn tại hoặc lỗi: " . $e->getMessage() . "<br>";
            }
            $this->addColumn('discussion_replies', 'score', "DECIMAL(5,2) DEFAULT NULL");

            echo "Hoàn thành cập nhật CSDL!";
        } catch (Exception $e) {
            echo "Lỗi: " . $e->getMessage();
        }
    }
}

$m = new Migrator();
$m->run();
