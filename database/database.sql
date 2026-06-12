-- Tạo CSDL
CREATE DATABASE IF NOT EXISTS attendance_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_system;

-- 1. Bảng users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Bảng courses (Khóa học/Học phần)
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `class_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `credits` INT DEFAULT 3,
  `periods` INT DEFAULT 45,
  `description` TEXT,
  `teacher_id` INT,
  `rule_present_points` INT DEFAULT 2,
  `rule_late_points` INT DEFAULT 1,
  `rule_absent_points` INT DEFAULT 0,
  `rule_interaction_points` INT DEFAULT 1,
  `rule_attendance_weight` INT DEFAULT 50,
  `rule_quiz_weight` INT DEFAULT 50,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Bảng phụ: course_students (N-N giữa courses và users có role student)
CREATE TABLE `course_students` (
  `course_id` INT,
  `student_id` INT,
  PRIMARY KEY (`course_id`, `student_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 3. Bảng class_sessions (Buổi học)
CREATE TABLE `class_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('scheduled', 'active', 'completed') DEFAULT 'scheduled',
  `attendance_code` VARCHAR(10) DEFAULT NULL,
  `attendance_expires_at` DATETIME DEFAULT NULL,
  `qr_token` VARCHAR(255) DEFAULT NULL,
  `room` VARCHAR(100) DEFAULT 'Phòng học 102, số 1 Phan Tây Nhạc',
  `period` VARCHAR(50) DEFAULT '1 - 3',
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
);

-- 4. Bảng attendance_methods (Phương thức điểm danh)
CREATE TABLE `attendance_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE, -- 'QR', 'Code', 'Manual'
  `description` VARCHAR(255)
);

-- 5. Bảng attendance_records (Bản ghi điểm danh)
CREATE TABLE `attendance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `method_id` INT NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `class_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`method_id`) REFERENCES `attendance_methods`(`id`),
  UNIQUE KEY `unique_attendance` (`session_id`, `student_id`)
);

-- 6. Bảng quiz_sessions (Phiên Quiz trong buổi học)
CREATE TABLE `quiz_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `total_marks` DECIMAL(5,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `class_sessions`(`id`) ON DELETE CASCADE
);

-- 7. Bảng quiz_submissions (Bài làm Quiz)
CREATE TABLE `quiz_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `score` DECIMAL(5,2) DEFAULT 0,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`quiz_id`) REFERENCES `quiz_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_submission` (`quiz_id`, `student_id`)
);

-- 8. Bảng interaction_logs (Log tương tác: Hỏi/Đáp/Phát biểu)
CREATE TABLE `interaction_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `type` ENUM('question', 'answer', 'discussion') NOT NULL,
  `points_awarded` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `class_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 9. Bảng engagement_scores (Tổng điểm chuyên cần & tương tác theo khóa học)
CREATE TABLE `engagement_scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `attendance_points` INT DEFAULT 0,
  `interaction_points` INT DEFAULT 0,
  `total_score` INT DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_engagement` (`course_id`, `student_id`)
);

-- 10. Bảng alerts (Cảnh báo Engine)
CREATE TABLE `alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL, -- Sinh viên bị cảnh báo hoặc Giáo viên nhận cảnh báo
  `course_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
);

-- Dữ liệu Mẫu (Dummy Data) được sinh tự động

-- 1. Bảng users
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (1, 'admin', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'System Admin', 'admin@example.com', 'admin') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (232, 'teacher1', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'PGS.TS. Nguyễn Văn An', 'nva@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (233, 'teacher2', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'PGS.TS. Trần Văn Cơ', 'tvc@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (234, 'teacher3', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'ThS. Phạm Thị Bình', 'ptb@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (235, 'teacher4', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'TS. Lê Hoàng Nam', 'lhn@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (236, 'teacher5', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'ThS. Nguyễn Thị Mai', 'ntm@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (237, 'teacher6', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'TS. Vũ Minh Trí', 'vmt@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (238, 'teacher7', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'ThS. Đặng Hồng Liên', 'dhl@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (239, 'teacher8', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'TS. Bùi Quốc Anh', 'bqa@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (240, 'teacher9', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'ThS. Đỗ Hoàng Yến', 'dhy@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (241, 'teacher10', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'TS. Ngô Gia Bảo', 'ngb@example.com', 'teacher') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (242, 'student1', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Hải Anh', 'student1@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (243, 'student2', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Thị Bình', 'student2@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (244, 'student3', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Văn Nam', 'student3@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (245, 'student4', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Đức Yến', 'student4@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (246, 'student5', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Thành Bình', 'student5@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (247, 'student6', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Thành Vy', 'student6@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (248, 'student7', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Thành Lâm', 'student7@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (249, 'student8', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Hải Hùng', 'student8@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (250, 'student9', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Đức Lâm', 'student9@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (251, 'student10', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Minh Anh', 'student10@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (252, 'student11', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Văn Vy', 'student11@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (253, 'student12', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Hoàng Đức Lâm', 'student12@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (254, 'student13', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Văn Vy', 'student13@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (255, 'student14', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Hải Cường', 'student14@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (256, 'student15', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Gia Huy', 'student15@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (257, 'student16', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Thành Vy', 'student16@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (258, 'student17', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Hải Lâm', 'student17@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (259, 'student18', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Văn Nam', 'student18@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (260, 'student19', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Thành Trang', 'student19@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (261, 'student20', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Minh Hải', 'student20@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (262, 'student21', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Đức Yến', 'student21@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (263, 'student22', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Quốc Anh', 'student22@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (264, 'student23', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Minh Dung', 'student23@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (265, 'student24', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hoàng Sơn', 'student24@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (266, 'student25', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Thành Sơn', 'student25@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (267, 'student26', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Văn Cường', 'student26@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (268, 'student27', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Văn Yến', 'student27@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (269, 'student28', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Thị Cường', 'student28@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (270, 'student29', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Hồng Vy', 'student29@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (271, 'student30', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Hải Hải', 'student30@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (272, 'student31', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Hải Huy', 'student31@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (273, 'student32', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Hoàng Thị Bình', 'student32@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (274, 'student33', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Hồng Huy', 'student33@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (275, 'student34', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Đức Nam', 'student34@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (276, 'student35', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hải Yến', 'student35@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (277, 'student36', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Minh Dung', 'student36@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (278, 'student37', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Hoàng Sơn', 'student37@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (279, 'student38', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Minh Lâm', 'student38@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (280, 'student39', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Văn Hùng', 'student39@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (281, 'student40', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Minh Yến', 'student40@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (282, 'student41', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Thành Lâm', 'student41@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (283, 'student42', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Hải Giang', 'student42@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (284, 'student43', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Đức Lâm', 'student43@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (285, 'student44', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Đức Bảo', 'student44@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (286, 'student45', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Quốc Nam', 'student45@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (287, 'student46', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Đức Bảo', 'student46@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (288, 'student47', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Hoàng Lâm', 'student47@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (289, 'student48', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Quốc Vy', 'student48@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (290, 'student49', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Hải Huy', 'student49@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (291, 'student50', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Minh Hùng', 'student50@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (292, 'student51', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Thị Cường', 'student51@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (293, 'student52', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hải Trang', 'student52@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (294, 'student53', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Hoàng Hải Lâm', 'student53@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (295, 'student54', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Hải Trang', 'student54@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (296, 'student55', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Thành Bảo', 'student55@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (297, 'student56', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Hoàng Vy', 'student56@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (298, 'student57', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Đức Yến', 'student57@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (299, 'student58', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Gia Sơn', 'student58@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (300, 'student59', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Gia Giang', 'student59@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (301, 'student60', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Hải Giang', 'student60@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (302, 'student61', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Gia Hùng', 'student61@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (303, 'student62', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Quốc Dung', 'student62@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (304, 'student63', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Hồng Nam', 'student63@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (305, 'student64', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hồng Lâm', 'student64@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (306, 'student65', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Hoàng Anh', 'student65@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (307, 'student66', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Hồng Huy', 'student66@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (308, 'student67', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Thị Huy', 'student67@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (309, 'student68', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Gia Nam', 'student68@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (310, 'student69', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Thành Anh', 'student69@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (311, 'student70', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Văn Vy', 'student70@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (312, 'student71', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Hoàng Minh Hải', 'student71@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (313, 'student72', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Gia Bình', 'student72@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (314, 'student73', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Văn Huy', 'student73@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (315, 'student74', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Thị Nam', 'student74@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (316, 'student75', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Thành Trang', 'student75@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (317, 'student76', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Hải Yến', 'student76@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (318, 'student77', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Đức Lâm', 'student77@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (319, 'student78', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hoàng Vy', 'student78@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (320, 'student79', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Minh Bình', 'student79@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (321, 'student80', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Văn Dung', 'student80@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (322, 'student81', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Thị Giang', 'student81@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (323, 'student82', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Hồng Bảo', 'student82@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (324, 'student83', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Thị Nam', 'student83@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (325, 'student84', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Lê Hoàng Nam', 'student84@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (326, 'student85', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phan Gia Sơn', 'student85@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (327, 'student86', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Phạm Minh Lâm', 'student86@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (328, 'student87', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Thị Dung', 'student87@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (329, 'student88', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Hồng Sơn', 'student88@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (330, 'student89', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Minh Hải', 'student89@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (331, 'student90', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Đức Vy', 'student90@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (332, 'student91', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Trần Đức Hải', 'student91@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (333, 'student92', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Quốc Huy', 'student92@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (334, 'student93', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Hoàng Hồng Sơn', 'student93@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (335, 'student94', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Thành Bảo', 'student94@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (336, 'student95', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Bùi Gia Vy', 'student95@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (337, 'student96', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Nguyễn Hoàng Dung', 'student96@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (338, 'student97', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Vũ Quốc Dung', 'student97@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (339, 'student98', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Hoàng Yến', 'student98@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (340, 'student99', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đỗ Văn Anh', 'student99@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES (341, 'student100', '$2y$10$ZhgfamOnzCIf/dLEHyOrzOQ7QpSM/vosBqfIPMsX7ANmbvEppc8nW', 'Đặng Hải Cường', 'student100@example.com', 'student') ON DUPLICATE KEY UPDATE id=id;

-- 2. Bảng attendance_methods
INSERT INTO `attendance_methods` (`id`, `name`, `description`) VALUES (1, 'QR', 'Quét mã QR') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `attendance_methods` (`id`, `name`, `description`) VALUES (2, 'Code', 'Nhập mã Code 6 chữ số') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `attendance_methods` (`id`, `name`, `description`) VALUES (3, 'Manual', 'Giảng viên điểm danh tay') ON DUPLICATE KEY UPDATE id=id;

-- 3. Bảng courses
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (23, 'INS3060', 'INS3060.H01', 'Lập trình ứng dụng Di động', 3, 45, 'Học phần phát triển ứng dụng di động đa nền tảng Flutter kỳ hè.', 234, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (24, 'INS3065', 'INS3065.H01', 'Kiểm thử phần mềm nâng cao', 3, 45, 'Môn học kiểm thử hộp đen, hộp trắng và tự động hóa kiểm thử.', 233, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (25, 'INS3066', 'INS3066.H01', 'Trí tuệ nhân tạo cơ bản', 3, 45, 'Nhập môn máy học, thị giác máy tính và NLP cơ bản.', 232, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (26, 'INS3064', 'INS3064.H01', 'Phát triển ứng dụng Web', 3, 45, 'Học phát triển web fullstack PHP MVC nâng cao kỳ hè.', 235, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (27, 'INS3068', 'INS3068.H01', 'Cơ sở dữ liệu phân tán', 3, 45, 'Thiết kế, tối ưu hóa và phân tán CSDL quy mô lớn.', 237, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (28, 'INS3070', 'INS3070.H01', 'An toàn thông tin mạng', 3, 45, 'Các kỹ thuật bảo mật mạng, mật mã học và ứng dụng phòng thủ.', 239, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (29, 'INS3071', 'INS3071.H01', 'Điện toán đám mây', 3, 45, 'Tìm hiểu kiến trúc Cloud (AWS, Azure) và phát triển dịch vụ không máy chủ.', 236, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (30, 'INS3072', 'INS3072.H01', 'Lập trình Web nâng cao', 3, 45, 'Phát triển ứng dụng Web thời gian thực với WebSockets và React.', 238, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (31, 'INS3073', 'INS3073.H01', 'Phát triển hệ thống IoT', 3, 45, 'Lập trình vi điều khiển, kết nối mạng cảm biến và giao thức MQTT.', 240, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `courses` (`id`, `code`, `class_code`, `name`, `credits`, `periods`, `description`, `teacher_id`, `rule_present_points`, `rule_late_points`, `rule_absent_points`, `rule_interaction_points`, `rule_attendance_weight`, `rule_quiz_weight`) VALUES (32, 'INS3074', 'INS3074.H01', 'An ninh mạng ứng dụng', 3, 45, 'Các kỹ thuật kiểm thử xâm nhập (Penetration Testing) và phòng thủ hệ thống.', 241, 2, 1, 0, 1, 50, 50) ON DUPLICATE KEY UPDATE id=id;

-- 4. Bảng course_students
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 253) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 255) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 259) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 263) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 265) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 269) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 273) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 281) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 290) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 292) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 295) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 299) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 303) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 306) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 308) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 309) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 313) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 318) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 321) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 327) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 331) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 332) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 336) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 340) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (23, 341) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 244) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 247) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 250) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 251) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 255) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 256) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 260) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 263) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 274) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 278) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 279) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 281) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 283) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 287) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 288) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 289) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 295) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 298) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 301) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 310) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 314) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 325) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 332) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (24, 340) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 242) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 246) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 248) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 251) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 259) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 267) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 269) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 271) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 275) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 276) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 278) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 297) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 300) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 302) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 307) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 312) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 315) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 320) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 326) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 335) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 336) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 337) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 338) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 339) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 340) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (25, 341) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 245) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 246) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 252) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 256) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 259) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 267) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 279) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 284) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 285) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 287) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 304) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 306) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 307) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 308) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 309) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 311) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 313) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 317) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 320) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 325) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 326) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 330) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 337) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (26, 341) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 242) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 245) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 250) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 268) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 269) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 270) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 274) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 276) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 277) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 279) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 282) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 284) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 285) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 300) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 307) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 308) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 309) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 312) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 316) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 318) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 331) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 332) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 335) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (27, 340) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 248) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 254) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 255) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 257) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 260) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 264) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 266) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 271) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 276) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 284) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 288) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 289) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 290) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 297) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 298) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 300) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 309) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 311) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 320) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 328) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 329) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 332) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 333) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 334) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (28, 339) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 245) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 247) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 248) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 250) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 251) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 253) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 260) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 263) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 267) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 268) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 271) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 272) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 276) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 281) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 292) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 295) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 296) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 298) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 299) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 308) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 319) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 324) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 333) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 334) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 337) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 338) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (29, 339) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 242) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 243) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 251) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 254) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 255) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 264) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 266) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 267) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 268) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 276) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 280) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 281) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 282) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 283) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 284) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 286) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 288) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 290) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 293) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 296) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 297) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 308) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 316) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 317) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 318) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 325) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (30, 336) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 242) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 244) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 268) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 273) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 277) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 279) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 282) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 284) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 288) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 291) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 293) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 297) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 299) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 304) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 310) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 315) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 322) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 326) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 329) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 330) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 332) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 335) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (31, 340) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 246) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 250) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 253) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 258) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 260) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 265) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 269) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 270) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 275) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 277) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 278) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 280) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 281) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 283) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 290) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 299) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 300) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 303) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 305) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 316) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 321) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 322) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 323) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 328) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 335) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `course_students` (`course_id`, `student_id`) VALUES (32, 339) ON DUPLICATE KEY UPDATE course_id=course_id;

-- 5. Bảng class_sessions
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (115, 23, '2026-06-15', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (116, 23, '2026-06-22', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (117, 23, '2026-06-29', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (118, 23, '2026-07-06', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (119, 23, '2026-07-13', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (120, 23, '2026-07-20', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (121, 23, '2026-07-27', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng thực hành 202', '1 - 3', 'Buổi học Di động hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (122, 24, '2026-06-16', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (123, 24, '2026-06-23', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (124, 24, '2026-06-30', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (125, 24, '2026-07-07', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (126, 24, '2026-07-14', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (127, 24, '2026-07-21', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (128, 24, '2026-07-28', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 305', '7 - 9', 'Buổi học Kiểm thử hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (129, 25, '2026-06-17', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (130, 25, '2026-06-24', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (131, 25, '2026-07-01', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (132, 25, '2026-07-08', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (133, 25, '2026-07-15', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (134, 25, '2026-07-22', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (135, 25, '2026-07-29', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng chuyên đề 401', '1 - 3', 'Buổi học AI hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (136, 26, '2026-06-18', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (137, 26, '2026-06-25', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (138, 26, '2026-07-02', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (139, 26, '2026-07-09', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (140, 26, '2026-07-16', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (141, 26, '2026-07-23', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (142, 26, '2026-07-30', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 501', '7 - 9', 'Buổi học Web hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (143, 27, '2026-06-19', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (144, 27, '2026-06-26', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (145, 27, '2026-07-03', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (146, 27, '2026-07-10', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (147, 27, '2026-07-17', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (148, 27, '2026-07-24', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (149, 27, '2026-07-31', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng học lý thuyết 204', '1 - 3', 'Buổi học CSDL phân tán hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (150, 28, '2026-06-20', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (151, 28, '2026-06-27', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (152, 28, '2026-07-04', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (153, 28, '2026-07-11', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (154, 28, '2026-07-18', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (155, 28, '2026-07-25', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (156, 28, '2026-08-01', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Lab 302', '7 - 9', 'Buổi học An toàn thông tin hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (157, 29, '2026-06-15', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (158, 29, '2026-06-22', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (159, 29, '2026-06-29', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (160, 29, '2026-07-06', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (161, 29, '2026-07-13', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (162, 29, '2026-07-20', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (163, 29, '2026-07-27', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng Cloud Lab 1', '7 - 9', 'Buổi học Cloud hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (164, 30, '2026-06-16', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (165, 30, '2026-06-23', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (166, 30, '2026-06-30', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (167, 30, '2026-07-07', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (168, 30, '2026-07-14', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (169, 30, '2026-07-21', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (170, 30, '2026-07-28', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Web Lab 2', '1 - 3', 'Buổi học Web nâng cao hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (171, 31, '2026-06-17', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (172, 31, '2026-06-24', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (173, 31, '2026-07-01', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (174, 31, '2026-07-08', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (175, 31, '2026-07-15', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (176, 31, '2026-07-22', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (177, 31, '2026-07-29', '13:30:00', '16:30:00', 'scheduled', NULL, NULL, NULL, 'Phòng IoT Lab 3', '7 - 9', 'Buổi học IoT hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (178, 32, '2026-06-18', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (179, 32, '2026-06-25', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (180, 32, '2026-07-02', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (181, 32, '2026-07-09', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (182, 32, '2026-07-16', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (183, 32, '2026-07-23', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `class_sessions` (`id`, `course_id`, `session_date`, `start_time`, `end_time`, `status`, `attendance_code`, `attendance_expires_at`, `qr_token`, `room`, `period`, `note`) VALUES (184, 32, '2026-07-30', '08:00:00', '11:00:00', 'scheduled', NULL, NULL, NULL, 'Phòng Security Lab 4', '1 - 3', 'Buổi học An ninh mạng hàng tuần') ON DUPLICATE KEY UPDATE id=id;

-- 6. Bảng engagement_scores
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 253, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 255, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 259, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 263, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 265, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 269, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 273, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 281, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 290, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 292, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 295, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 299, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 303, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 306, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 308, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 309, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 313, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 318, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 321, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 327, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 331, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 332, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 336, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 340, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (23, 341, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 244, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 247, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 250, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 251, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 255, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 256, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 260, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 263, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 274, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 278, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 279, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 281, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 283, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 287, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 288, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 289, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 295, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 298, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 301, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 310, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 314, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 325, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 332, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (24, 340, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 242, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 246, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 248, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 251, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 259, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 267, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 269, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 271, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 275, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 276, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 278, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 297, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 300, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 302, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 307, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 312, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 315, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 320, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 326, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 335, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 336, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 337, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 338, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 339, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 340, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (25, 341, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 245, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 246, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 252, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 256, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 259, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 267, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 279, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 284, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 285, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 287, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 304, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 306, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 307, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 308, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 309, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 311, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 313, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 317, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 320, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 325, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 326, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 330, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 337, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (26, 341, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 242, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 245, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 250, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 268, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 269, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 270, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 274, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 276, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 277, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 279, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 282, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 284, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 285, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 300, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 307, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 308, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 309, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 312, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 316, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 318, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 331, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 332, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 335, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (27, 340, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 248, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 254, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 255, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 257, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 260, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 264, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 266, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 271, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 276, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 284, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 288, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 289, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 290, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 297, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 298, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 300, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 309, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 311, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 320, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 328, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 329, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 332, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 333, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 334, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (28, 339, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 245, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 247, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 248, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 250, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 251, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 253, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 260, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 263, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 267, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 268, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 271, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 272, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 276, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 281, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 292, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 295, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 296, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 298, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 299, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 308, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 319, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 324, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 333, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 334, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 337, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 338, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (29, 339, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 242, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 243, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 251, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 254, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 255, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 264, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 266, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 267, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 268, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 276, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 280, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 281, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 282, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 283, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 284, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 286, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 288, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 290, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 293, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 296, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 297, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 308, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 316, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 317, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 318, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 325, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (30, 336, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 242, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 244, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 268, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 273, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 277, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 279, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 282, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 284, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 288, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 291, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 293, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 297, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 299, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 304, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 310, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 315, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 322, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 326, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 329, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 330, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 332, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 335, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (31, 340, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 246, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 250, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 253, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 258, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 260, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 265, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 269, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 270, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 275, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 277, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 278, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 280, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 281, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 283, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 290, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 299, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 300, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 303, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 305, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 316, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 321, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 322, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 323, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 328, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 335, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
INSERT INTO `engagement_scores` (`course_id`, `student_id`, `attendance_points`, `interaction_points`, `total_score`) VALUES (32, 339, 0, 0, 100) ON DUPLICATE KEY UPDATE course_id=course_id;
