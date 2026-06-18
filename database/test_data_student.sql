-- ============================================================
-- FILE: test_data_student.sql
-- Mục đích: Tạo dữ liệu test đầy đủ cho Student Portal
-- Tài khoản test: student1 / 123456 (user_id = 242)
-- Chạy: mysql -u root attendance_system < test_data_student.sql
-- ============================================================

USE attendance_system;

-- ============================================================
-- BƯỚC 1: Tạo bảng leave_requests (nếu chưa có)
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `class_sessions`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- BƯỚC 2: Đặt một số buổi học đã qua thành 'completed'
-- (để có thể ghi attendance_records cho các buổi đã qua)
-- Course 25 (AI): sessions 129-135 (17/06 → 29/07)
-- Course 27 (CSDL): sessions 143-149 (19/06 → 31/07)
-- Course 30 (Web): sessions 164-170 (16/06 → 28/07)
-- ============================================================

-- Đánh dấu 5 buổi đầu của course 25 là completed (đã qua)
UPDATE `class_sessions` SET status = 'completed' WHERE id IN (129, 130, 131, 132, 133);

-- Đánh dấu 4 buổi đầu của course 27 là completed
UPDATE `class_sessions` SET status = 'completed' WHERE id IN (143, 144, 145, 146);

-- Đánh dấu 6 buổi đầu của course 30 là completed
UPDATE `class_sessions` SET status = 'completed' WHERE id IN (164, 165, 166, 167, 168, 169);

-- Tạo 1 buổi học ĐANG DIỄN RA (active) cho course 25 (buổi 134)
-- Sinh viên 242 CHƯA điểm danh → sẽ thấy hộp nhập code
UPDATE `class_sessions` 
SET status = 'active',
    attendance_code = '123456',
    attendance_expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR)
WHERE id = 134;

-- ============================================================
-- BƯỚC 3: Tạo attendance_records
-- ============================================================

-- === Course 25 (INS3066 - AI cơ bản) ===
-- Tình huống: Học tốt — 3 có mặt, 1 muộn, 1 có phép
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (129, 242, 1, 'present') ON DUPLICATE KEY UPDATE status='present';  -- Buổi 1: QR, có mặt
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (130, 242, 2, 'present') ON DUPLICATE KEY UPDATE status='present';  -- Buổi 2: Code, có mặt
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (131, 242, 1, 'present') ON DUPLICATE KEY UPDATE status='present';  -- Buổi 3: QR, có mặt
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (132, 242, 3, 'late')    ON DUPLICATE KEY UPDATE status='late';      -- Buổi 4: Manual, muộn
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (133, 242, 3, 'excused') ON DUPLICATE KEY UPDATE status='excused';   -- Buổi 5: Manual, có phép

-- === Course 27 (INS3068 - CSDL phân tán) ===
-- Tình huống: Vắng nhiều → kích hoạt Alert
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (143, 242, 3, 'absent') ON DUPLICATE KEY UPDATE status='absent';  -- Buổi 1: Vắng
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (144, 242, 3, 'absent') ON DUPLICATE KEY UPDATE status='absent';  -- Buổi 2: Vắng
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (145, 242, 3, 'absent') ON DUPLICATE KEY UPDATE status='absent';  -- Buổi 3: Vắng
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (146, 242, 3, 'absent') ON DUPLICATE KEY UPDATE status='absent';  -- Buổi 4: Vắng (quá ngưỡng 3!)

-- === Course 30 (INS3072 - Web nâng cao) ===
-- Tình huống: Đi muộn nhiều — 2 có mặt, 3 muộn, 1 vắng
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (164, 242, 2, 'present') ON DUPLICATE KEY UPDATE status='present';  -- Buổi 1: Code, có mặt
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (165, 242, 1, 'present') ON DUPLICATE KEY UPDATE status='present';  -- Buổi 2: QR, có mặt
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (166, 242, 3, 'late')    ON DUPLICATE KEY UPDATE status='late';      -- Buổi 3: Muộn
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (167, 242, 3, 'late')    ON DUPLICATE KEY UPDATE status='late';      -- Buổi 4: Muộn
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (168, 242, 3, 'late')    ON DUPLICATE KEY UPDATE status='late';      -- Buổi 5: Muộn
INSERT INTO `attendance_records` (session_id, student_id, method_id, status) VALUES (169, 242, 3, 'absent')  ON DUPLICATE KEY UPDATE status='absent';    -- Buổi 6: Vắng

-- ============================================================
-- BƯỚC 4: Tạo interaction_logs (tương tác)
-- ============================================================

-- Course 25: Tương tác tốt (hỏi, trả lời, thảo luận)
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (129, 242, 'question',   1);
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (129, 242, 'answer',     2);
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (130, 242, 'discussion', 1);
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (131, 242, 'question',   1);
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (132, 242, 'answer',     2);

-- Course 30: Tương tác ít
INSERT INTO `interaction_logs` (session_id, student_id, type, points_awarded) VALUES (164, 242, 'question', 1);

-- ============================================================
-- BƯỚC 5: Tạo quiz_sessions và quiz_submissions
-- ============================================================

-- Quiz cho course 25
INSERT INTO `quiz_sessions` (id, session_id, title, start_time, end_time, total_marks)
VALUES (501, 129, 'Quiz 1 - Nhập môn AI & Machine Learning', '2026-06-17 09:30:00', '2026-06-17 09:45:00', 10)
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO `quiz_sessions` (id, session_id, title, start_time, end_time, total_marks)
VALUES (502, 130, 'Quiz 2 - Thuật toán tìm kiếm & Tối ưu', '2026-06-24 09:30:00', '2026-06-24 09:45:00', 10)
ON DUPLICATE KEY UPDATE title = title;

-- Bài nộp của student1 (id=242)
INSERT INTO `quiz_submissions` (quiz_id, student_id, score)
VALUES (501, 242, 8.5)
ON DUPLICATE KEY UPDATE score = 8.5;

INSERT INTO `quiz_submissions` (quiz_id, student_id, score)
VALUES (502, 242, 4.0)
ON DUPLICATE KEY UPDATE score = 4.0;

-- ============================================================
-- BƯỚC 6: Tạo alerts (cảnh báo)
-- ============================================================

-- Cảnh báo cho student1: Vắng quá nhiều ở course 27
INSERT INTO `alerts` (user_id, course_id, message, is_read)
VALUES (242, 27, 'Cảnh báo: Bạn đã vắng 4 buổi trong môn Cơ sở dữ liệu phân tán — vượt ngưỡng cho phép (3 buổi). Hãy liên hệ giảng viên ngay.', FALSE);

-- Cảnh báo cho student1: CPI thấp ở course 27
INSERT INTO `alerts` (user_id, course_id, message, is_read)
VALUES (242, 27, 'Cảnh báo: Điểm CPI của bạn trong môn Cơ sở dữ liệu phân tán hiện dưới ngưỡng tối thiểu (50/100). Hãy tham gia tích cực hơn.', FALSE);

-- Cảnh báo gửi tới Giảng viên (teacher3, id=234) về student1
-- teacher3 = TS. Lê Hoàng Nam — giảng viên course 27
INSERT INTO `alerts` (user_id, course_id, message, is_read)
VALUES (234, 27, 'Sinh viên Nguyễn Hải Anh (student1) đã vắng 4 buổi trong môn Cơ sở dữ liệu phân tán của bạn. Cần liên hệ hỗ trợ sinh viên.', FALSE);

-- ============================================================
-- BƯỚC 7: Tạo leave_requests (đơn xin phép)
-- ============================================================

-- Đơn đã được APPROVED (buổi 133 đã excused)
INSERT INTO `leave_requests` (student_id, session_id, reason, status)
VALUES (242, 133, 'Tôi bị ốm và có giấy xác nhận của bệnh viện ngày 15/07/2026. Kính mong thầy/cô xem xét cho phép vắng có phép.', 'approved');

-- Đơn đang PENDING (buổi 135 - buổi tương lai)
INSERT INTO `leave_requests` (student_id, session_id, reason, status)
VALUES (242, 135, 'Gia đình có việc đột xuất, tôi xin phép nghỉ buổi học ngày 29/07. Tôi sẽ tự học bù theo tài liệu của thầy/cô.', 'pending');

-- Đơn bị REJECTED
INSERT INTO `leave_requests` (student_id, session_id, reason, status)
VALUES (242, 130, 'Tôi muốn nghỉ để đi chơi với bạn bè.', 'rejected');

-- ============================================================
-- BƯỚC 8: Cập nhật engagement_scores thực tế
-- ============================================================

-- Course 25: CPI tốt
-- Điểm chuyên cần: present×3=6đ, late×1=1đ, excused=0đ → 7đ
-- Điểm tương tác: 1+2+1+1+2 = 7đ
-- total_score = (7/10 * 50%) + (7/10 * 50%) * 100 = 70
UPDATE `engagement_scores`
SET attendance_points = 7, interaction_points = 7, total_score = 70
WHERE course_id = 25 AND student_id = 242;

-- Course 27: CPI kém (vắng 4 buổi, không tương tác)
-- Điểm chuyên cần: absent×4=0đ, Tương tác: 0đ → total ~5
UPDATE `engagement_scores`
SET attendance_points = 0, interaction_points = 0, total_score = 5
WHERE course_id = 27 AND student_id = 242;

-- Course 30: CPI trung bình
-- Điểm chuyên cần: present×2=4đ, late×3=3đ, absent×1=0đ → 7đ
-- Tương tác: 1đ → total ~45
UPDATE `engagement_scores`
SET attendance_points = 7, interaction_points = 1, total_score = 45
WHERE course_id = 30 AND student_id = 242;

-- ============================================================
-- Xác nhận kết quả
-- ============================================================
SELECT 'attendance_records' AS table_name, COUNT(*) AS rows_inserted FROM attendance_records WHERE student_id = 242
UNION ALL
SELECT 'interaction_logs', COUNT(*) FROM interaction_logs WHERE student_id = 242
UNION ALL
SELECT 'quiz_submissions', COUNT(*) FROM quiz_submissions WHERE student_id = 242
UNION ALL
SELECT 'alerts (student)', COUNT(*) FROM alerts WHERE user_id = 242
UNION ALL
SELECT 'leave_requests', COUNT(*) FROM leave_requests WHERE student_id = 242
UNION ALL
SELECT 'active_sessions', COUNT(*) FROM class_sessions WHERE status = 'active';
