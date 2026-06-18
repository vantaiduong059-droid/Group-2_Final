<?php
// public/index.php

// Khởi tạo Session
session_start();

// Nạp các file core và config
require_once '../config/config.php';
require_once '../core/Router.php';
require_once '../core/Controller.php';
require_once '../core/Model.php';

// Nạp các Pattern Classes
// ... sẽ autoloader hoặc require sau

// Khởi tạo Router
$router = new Router();

// ==========================
// ĐỊNH NGHĨA CÁC ROUTES
// ==========================

// Web Routes
$router->get('/', 'HomeController@index');
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Admin Routes
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin/courses', 'AdminController@courses');
$router->get('/admin/students', 'AdminController@students');
$router->get('/admin/teachers', 'AdminController@teachers');
$router->get('/admin/sessions', 'AdminController@sessions');
$router->get('/admin/alerts', 'AdminController@alerts');

// Teacher Routes
$router->get('/teacher/dashboard', 'TeacherController@dashboard');

// Student Routes
$router->get('/student/dashboard', 'StudentController@dashboard');
$router->get('/student/schedule', 'StudentController@schedule');
$router->get('/student/my-courses', 'StudentController@myCourses');
$router->get('/student/profile', 'StudentController@profile');

// API Routes cho Fetch/AJAX (Trả về JSON)
$router->get('/api/courses', 'CourseApiController@index');
$router->post('/api/courses', 'CourseApiController@store');
$router->get('/api/courses/{id}', 'CourseApiController@show');
$router->put('/api/courses/{id}', 'CourseApiController@update');
$router->delete('/api/courses/{id}', 'CourseApiController@destroy');
$router->get('/api/courses/{id}/students', 'CourseApiController@getStudents');
$router->post('/api/courses/{id}/students', 'CourseApiController@addStudent');
$router->delete('/api/courses/{id}/students/{studentId}', 'CourseApiController@removeStudent');

// API Alerts
$router->get('/api/alerts', 'AlertApiController@index');
$router->put('/api/alerts/{id}', 'AlertApiController@update');

// API Teachers
$router->get('/api/teachers', 'TeacherApiController@index');
$router->post('/api/teachers', 'TeacherApiController@store');
$router->put('/api/teachers/{id}', 'TeacherApiController@update');
$router->delete('/api/teachers/{id}', 'TeacherApiController@destroy');

// API Students
$router->get('/api/students', 'StudentApiController@index');
$router->post('/api/students', 'StudentApiController@store');
$router->put('/api/students/{id}', 'StudentApiController@update');
$router->delete('/api/students/{id}', 'StudentApiController@destroy');

// API Sessions
$router->get('/api/sessions', 'SessionApiController@index');
$router->post('/api/sessions', 'SessionApiController@store');
$router->put('/api/sessions/{id}', 'SessionApiController@update');
$router->delete('/api/sessions/{id}', 'SessionApiController@destroy');

// API Điểm danh & Cấu hình điểm danh buổi học
$router->post('/api/attendance/submit', 'AttendanceApiController@submit');
$router->post('/api/sessions/{id}/start-attendance', 'AttendanceApiController@startAttendance');
$router->post('/api/sessions/{id}/stop-attendance', 'AttendanceApiController@stopAttendance');
$router->get('/api/sessions/{id}/attendance', 'AttendanceApiController@getAttendance');
$router->put('/api/sessions/{id}/attendance', 'AttendanceApiController@updateAttendance');

// API Quiz
$router->get('/api/quizzes', 'QuizApiController@index');
$router->post('/api/quizzes', 'QuizApiController@store');
$router->post('/api/quizzes/{id}/submit', 'QuizApiController@submit');
$router->get('/api/quizzes/{id}/submissions', 'QuizApiController@submissions');

// API Tương tác & CPI
$router->post('/api/interactions', 'InteractionApiController@store');
$router->post('/api/engagement/calculate', 'EngagementApiController@calculate');
$router->get('/api/student/dashboard-data', 'StudentApiController@dashboardData');
$router->post('/api/student/interaction', 'StudentApiController@studentInteraction');
$router->post('/api/student/change-password', 'StudentApiController@changePassword');

// API Thông báo
$router->get('/api/notifications', 'NotificationApiController@index');
$router->post('/api/notifications/mark-read', 'NotificationApiController@markAsRead');

// API Đơn xin phép vắng
$router->post('/api/leave-requests', 'LeaveRequestApiController@store');
$router->get('/api/leave-requests', 'LeaveRequestApiController@index');
$router->put('/api/leave-requests/{id}', 'LeaveRequestApiController@update');

// Lấy URI hiện tại và Method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Dispatch
$router->dispatch($requestUri, $requestMethod);
