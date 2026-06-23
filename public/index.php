<?php
// public/index.php

// Khởi tạo Session
session_start();

// Nạp các file core và config
require_once '../config/config.php';
require_once '../core/Router.php';
require_once '../core/Controller.php';
require_once '../core/Model.php';
require_once '../app/Helpers/AttendanceSessionHelper.php';
require_once '../app/Helpers/AttendanceStatsHelper.php';
require_once '../app/Helpers/AlertEngine.php';
require_once '../app/Helpers/ScheduleHelper.php';

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
$router->get('/admin/notifications', 'AdminController@notifications');

// Teacher Routes
$router->get('/teacher/dashboard', 'TeacherController@dashboard');
$router->get('/teacher/sessions', 'TeacherController@sessions');
$router->get('/teacher/notifications', 'TeacherController@notifications');

// Student Routes
$router->get('/student/dashboard', 'StudentController@dashboard');
$router->get('/student/notifications', 'StudentController@notifications');

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
$router->get('/api/majors', 'StudentApiController@getMajors');

// API Sessions
$router->get('/api/sessions', 'SessionApiController@index');
$router->get('/api/sessions/check-conflict', 'SessionApiController@checkConflict');
$router->get('/api/sessions/{id}', 'SessionApiController@show');
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

// API search & notifications
$router->get('/api/search', 'SearchApiController@search');
$router->get('/api/notifications', 'NotificationApiController@index');
$router->post('/api/notifications/read', 'NotificationApiController@markAsRead');

// API Tương tác & CPI
$router->post('/api/interactions', 'InteractionApiController@store');
$router->post('/api/engagement/calculate', 'EngagementApiController@calculate');
$router->get('/api/student/dashboard-data', 'StudentApiController@dashboardData');

// Web Route trang con điểm danh của Admin
$router->get('/admin/sessions/{id}/attendance', 'AdminController@sessionAttendance');

// ==========================================================
// ROUTES MỚI CHO PHASE 3
// ==========================================================

// Web Routes cho Admin (Phase 3)
$router->get('/admin/interactions', 'AdminInteractionController@index');
$router->get('/admin/engagement', 'AdminEngagementController@index');

// Web Routes cho Giảng viên (Phase 3)
$router->get('/teacher/quizzes', 'TeacherQuizzesController@index');
$router->get('/teacher/engagement', 'TeacherEngagementController@index');
$router->get('/teacher/alerts', 'TeacherAlertsController@index');

// API Routes cho Admin (Phase 3)
$router->get('/api/admin/configs', 'AdminEngagementController@getConfigs');
$router->post('/api/admin/configs', 'AdminEngagementController@saveConfigs');
$router->get('/api/admin/interactions/summary', 'AdminInteractionController@getSummary');
$router->put('/api/alerts/{id}/advisor', 'AlertApiController@assignAdvisor');
$router->get('/api/admin/courses/{id}/engagement', 'AdminEngagementController@getCourseEngagement');
$router->get('/api/admin/alerts', 'AdminEngagementController@getAllAlerts');

// API Routes cho Giảng viên (Phase 3)
$router->get('/api/teacher/courses', 'TeacherController@getMyCourses');
$router->get('/api/teacher/courses/{id}/quizzes', 'TeacherQuizzesController@getQuizzes');
$router->get('/api/teacher/quizzes/{id}/questions', 'TeacherQuizzesController@getQuestions');
$router->post('/api/teacher/courses/{id}/quizzes', 'TeacherQuizzesController@createQuiz');
$router->put('/api/teacher/quizzes/{id}', 'TeacherQuizzesController@updateQuiz');
$router->delete('/api/teacher/quizzes/{id}', 'TeacherQuizzesController@deleteQuiz');

$router->post('/api/teacher/quizzes/{id}/questions', 'TeacherQuizzesController@addQuestion');
$router->put('/api/teacher/questions/{id}', 'TeacherQuizzesController@updateQuestion');
$router->delete('/api/teacher/questions/{id}', 'TeacherQuizzesController@deleteQuestion');

$router->get('/api/teacher/courses/{id}/discussions', 'TeacherQuizzesController@getDiscussions');
$router->post('/api/teacher/courses/{id}/discussions', 'TeacherQuizzesController@createDiscussion');
$router->delete('/api/teacher/discussions/{id}', 'TeacherQuizzesController@deleteDiscussion');
$router->get('/api/teacher/discussions/{id}/replies', 'TeacherQuizzesController@getReplies');
$router->post('/api/teacher/discussions/{id}/replies', 'TeacherQuizzesController@createReply');

$router->get('/api/teacher/courses/{id}/engagement', 'TeacherEngagementController@getEngagement');
$router->put('/api/teacher/courses/{id}/engagement-rules', 'TeacherEngagementController@updateRules');

$router->get('/api/teacher/courses/{id}/alerts', 'TeacherAlertsController@getAlerts');
$router->post('/api/teacher/alerts/{id}/resolve', 'TeacherAlertsController@resolveAlert');
$router->get('/api/teacher/dashboard', 'TeacherDashboardApiController@summary');

// ==========================================================
// ROUTES MỚI CHO PHASE 4 – TEACHER & STUDENT FULL
// ==========================================================

// Web Routes cho Teacher (Phase 4)
$router->get('/teacher/my-courses', 'TeacherController@myCourses');
$router->get('/teacher/course-students/{id}', 'TeacherController@courseStudents');
$router->get('/teacher/session-detail/{id}', 'TeacherController@sessionDetail');
$router->get('/teacher/course/{id}/current-session', 'TeacherController@currentSession');
$router->get('/teacher/profile', 'TeacherController@profile');

// Web Routes cho Student (Phase 4)
$router->get('/student/schedule', 'StudentController@schedule');
$router->get('/student/attendance', 'StudentController@attendance');
$router->get('/student/quiz', 'StudentController@quiz');
$router->get('/student/history', 'StudentController@history');
$router->get('/student/profile', 'StudentController@profile');

// API – Profile (dùng chung cả Teacher và Student)
$router->get('/api/profile', 'ProfileApiController@show');
$router->post('/api/profile', 'ProfileApiController@update');
$router->post('/api/profile/password', 'ProfileApiController@changePassword');
$router->post('/api/profile/avatar', 'ProfileApiController@uploadAvatar');

// API – Student Dashboard
$router->get('/api/student/dashboard', 'StudentDashboardApiController@summary');
$router->get('/api/student/sessions', 'StudentDashboardApiController@sessions');
$router->get('/api/student/subjects', 'StudentDashboardApiController@subjects');
$router->get('/api/student/history', 'StudentDashboardApiController@history');
$router->get('/api/student/active-session', 'StudentDashboardApiController@activeSession');
$router->get('/api/student/quizzes', 'StudentDashboardApiController@quizzesBySession');
$router->get('/api/student/dashboard/course-charts', 'StudentDashboardApiController@courseCharts');

// API – Điểm danh chi tiết (Phase 4 extensions)
$router->get('/api/attendance/{id}', 'AttendanceApiController@getAttendance');
$router->post('/api/attendance/{id}/start', 'AttendanceApiController@startAttendance');
$router->post('/api/attendance/{id}/stop', 'AttendanceApiController@stopAttendance');
$router->post('/api/attendance/{id}/update', 'AttendanceApiController@updateAttendance');
$router->post('/api/attendance/{id}/edit', 'AttendanceApiController@editWithLog');
$router->get('/api/attendance/{id}/logs', 'AttendanceApiController@getChangeLogs');
$router->get('/api/attendance/{id}/complaints', 'AttendanceApiController@getComplaints');
$router->post('/api/attendance/complaint', 'AttendanceApiController@submitComplaint');
$router->post('/api/attendance/complaint/{id}/resolve', 'AttendanceApiController@resolveComplaint');

// API – Quiz + Discussion (Phase 4 extensions for session-based access)
$router->get('/api/teacher/session/{id}/quizzes-discussions', 'TeacherQuizzesController@getBySession');
$router->post('/api/teacher/quizzes', 'TeacherQuizzesController@createQuizWithQuestions');
$router->post('/api/teacher/discussions', 'TeacherQuizzesController@createDiscussionInSession');
$router->get('/api/discussions/{id}/replies', 'TeacherQuizzesController@getReplies');
$router->post('/api/discussions/{id}/reply', 'TeacherQuizzesController@addReplyByAny');
$router->post('/api/quiz/{id}/submit', 'QuizApiController@submit');
$router->get('/api/quizzes/{id}/submissions', 'QuizApiController@submissions');
$router->get('/api/teacher/discussions/{id}/submissions', 'TeacherQuizzesController@getDiscussionSubmissions');
$router->post('/api/teacher/discussions/replies/{id}/grade', 'TeacherQuizzesController@gradeReply');


// Lấy URI hiện tại và Method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Dispatch
$router->dispatch($requestUri, $requestMethod);
