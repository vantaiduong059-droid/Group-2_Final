<?php
// config/config.php

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

define('BASE_URL', 'http://localhost/ins3064/final_project/public');

// Tắt hiển thị lỗi ra trình duyệt (phù hợp môi trường production/demo)
// Để debug, đổi lại thành: ini_set('display_errors', 1); error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
