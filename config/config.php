<?php
// config/config.php

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

define('BASE_URL', 'http://localhost/ins3064/final_project/public');

// Bật thông báo lỗi trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
