<?php
/**
 * EduManager - Automated Test Script
 * Kiểm thử tất cả endpoints và logic quan trọng
 * Truy cập: http://localhost/ins3064/final_project/test_runner.php
 */

// Không dùng layout, output thẳng HTML
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';
require_once '../core/Model.php';

$results = [];
$passed = 0;
$failed = 0;

function test($name, $fn) {
    global $results, $passed, $failed;
    try {
        $result = $fn();
        if ($result === true || (is_array($result) && !empty($result['ok']))) {
            $passed++;
            $results[] = ['name' => $name, 'status' => 'PASS', 'detail' => is_array($result) ? ($result['detail'] ?? '') : ''];
        } else {
            $failed++;
            $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => is_array($result) ? ($result['detail'] ?? 'Returned false') : 'Returned false'];
        }
    } catch (Throwable $e) {
        $failed++;
        $results[] = ['name' => $name, 'status' => 'ERROR', 'detail' => $e->getMessage()];
    }
}

$db = Database::getInstance()->getConnection();

// =============================================
// TEST 1: Database Tables
// =============================================
$required_tables = ['users', 'courses', 'course_students', 'class_sessions', 
                    'attendance_records', 'alerts', 'engagement_scores', 
                    'interaction_logs', 'quiz_sessions', 'quiz_questions',
                    'class_discussions', 'discussion_replies', 'system_configs',
                    'notifications', 'quiz_submissions', 'attendance_methods'];

foreach ($required_tables as $table) {
    test("DB Table Exists: $table", function() use ($db, $table) {
        $r = $db->query("SHOW TABLES LIKE '$table'");
        return $r->fetchColumn() ? true : ['ok' => false, 'detail' => "Table '$table' NOT FOUND"];
    });
}

// =============================================
// TEST 2: Column Existence
// =============================================
$required_columns = [
    'courses' => ['rule_present_points', 'rule_late_points', 'rule_absent_points', 
                  'rule_interaction_points', 'rule_attendance_weight', 'rule_quiz_weight',
                  'rule_absent_limit', 'rule_low_cpi_threshold'],
    'alerts' => ['status', 'notes', 'advisor_id'],
];
foreach ($required_columns as $table => $cols) {
    foreach ($cols as $col) {
        test("Column Exists: $table.$col", function() use ($db, $table, $col) {
            $r = $db->query("SHOW COLUMNS FROM $table LIKE '$col'");
            return $r->fetchColumn() ? true : ['ok' => false, 'detail' => "Column '$table.$col' MISSING"];
        });
    }
}

// =============================================
// TEST 3: Data Counts
// =============================================
test("Users: Admin exists", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'");
    $cnt = (int)$r->fetchColumn();
    return $cnt > 0 ? ['ok' => true, 'detail' => "$cnt admin(s)"] : ['ok' => false, 'detail' => 'No admin user found'];
});

test("Users: 10 Teachers", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'");
    $cnt = (int)$r->fetchColumn();
    return $cnt === 10 ? ['ok' => true, 'detail' => "$cnt teachers"] : ['ok' => false, 'detail' => "Expected 10 teachers, got $cnt"];
});

test("Users: 100 Students", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM users WHERE role='student'");
    $cnt = (int)$r->fetchColumn();
    return $cnt === 100 ? ['ok' => true, 'detail' => "$cnt students"] : ['ok' => false, 'detail' => "Expected 100, got $cnt"];
});

test("Courses: 10 Courses (Summer)", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM courses");
    $cnt = (int)$r->fetchColumn();
    return $cnt >= 10 ? ['ok' => true, 'detail' => "$cnt courses"] : ['ok' => false, 'detail' => "$cnt courses (expected >=10)"];
});

test("CourseStudents: Enrollment Exists (>=10)", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM course_students");
    $cnt = (int)$r->fetchColumn();
    return $cnt >= 10 ? ['ok' => true, 'detail' => "$cnt enrollments total"] : ['ok' => false, 'detail' => "$cnt (expected >=10)"];
});

test("SystemConfigs: 8 configs", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM system_configs");
    $cnt = (int)$r->fetchColumn();
    return $cnt === 8 ? ['ok' => true, 'detail' => "$cnt configs"] : ['ok' => false, 'detail' => "Expected 8, got $cnt"];
});

test("Sessions: Exists (Summer)", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM class_sessions WHERE session_date >= '2026-06-15'");
    $cnt = (int)$r->fetchColumn();
    return $cnt > 0 ? ['ok' => true, 'detail' => "$cnt summer sessions"] : ['ok' => false, 'detail' => 'No summer sessions found'];
});

// =============================================
// TEST 4: Password Verification
// =============================================
test("Auth: Admin password '123456' valid", function() use ($db) {
    $r = $db->query("SELECT password FROM users WHERE username='admin' LIMIT 1");
    $hash = $r->fetchColumn();
    return password_verify('123456', $hash) ? true : ['ok' => false, 'detail' => 'Password hash does not match "123456"'];
});

test("Auth: Teacher1 password '123456' valid", function() use ($db) {
    $r = $db->query("SELECT password FROM users WHERE username='teacher1' LIMIT 1");
    $hash = $r->fetchColumn();
    return password_verify('123456', $hash) ? true : ['ok' => false, 'detail' => 'Teacher password mismatch'];
});

// =============================================
// TEST 5: Controller Files Exist
// =============================================
$controllers = [
    'Web/AdminController.php',
    'Web/AdminInteractionController.php',
    'Web/AdminEngagementController.php',
    'Web/TeacherController.php',
    'Web/TeacherQuizzesController.php',
    'Web/TeacherEngagementController.php',
    'Web/TeacherAlertsController.php',
    'Api/AlertApiController.php',
    'Api/SessionApiController.php',
    'Api/CourseApiController.php',
    'Api/StudentApiController.php',
];
foreach ($controllers as $ctrl) {
    test("Controller: $ctrl", function() use ($ctrl) {
        return file_exists("../app/Controllers/$ctrl") ? true : ['ok' => false, 'detail' => "File not found"];
    });
}

// =============================================
// TEST 6: View Files Exist
// =============================================
$views = [
    'admin/dashboard.php', 'admin/students.php', 'admin/teachers.php',
    'admin/courses.php', 'admin/sessions.php', 'admin/alerts.php',
    'admin/interactions.php', 'admin/engagement.php',
    'teacher/dashboard.php', 'teacher/quizzes_discussions.php',
    'teacher/engagement.php', 'teacher/alerts.php', 'teacher/sessions.php',
    'layouts/admin_layout.php', 'layouts/teacher_layout.php',
    'auth/login.php',
];
foreach ($views as $view) {
    test("View: $view", function() use ($view) {
        return file_exists("../app/Views/$view") ? true : ['ok' => false, 'detail' => "File not found"];
    });
}

// =============================================
// TEST 7: JS Asset Files Exist
// =============================================
$jsFiles = ['courses.js', 'teachers.js', 'students.js', 'sessions.js', 
            'alerts.js', 'teacher_quizzes.js', 'teacher_engagement.js', 'teacher_alerts.js'];
foreach ($jsFiles as $js) {
    test("JS Asset: $js", function() use ($js) {
        return file_exists("assets/js/$js") ? true : ['ok' => false, 'detail' => "Not found"];
    });
}

// =============================================
// TEST 8: Route Definitions in index.php
// =============================================
$routeContent = file_get_contents('index.php');
$routes_to_check = [
    '/admin/interactions' => 'AdminInteractionController',
    '/admin/engagement' => 'AdminEngagementController',
    '/teacher/quizzes' => 'TeacherQuizzesController',
    '/teacher/engagement' => 'TeacherEngagementController',
    '/teacher/alerts' => 'TeacherAlertsController',
    '/teacher/sessions' => 'TeacherController@sessions',
    '/api/admin/configs' => 'AdminEngagementController@getConfigs',
    '/api/admin/courses/{id}/engagement' => 'AdminEngagementController@getCourseEngagement',
    '/api/admin/interactions/summary' => 'AdminInteractionController@getSummary',
    '/api/teacher/courses/{id}/quizzes' => 'TeacherQuizzesController@getQuizzes',
];
foreach ($routes_to_check as $route => $handler) {
    test("Route: $route", function() use ($routeContent, $route, $handler) {
        if (strpos($routeContent, $route) !== false) {
            return ['ok' => true, 'detail' => "Found → $handler"];
        }
        return ['ok' => false, 'detail' => "Route '$route' not found in index.php"];
    });
}

// =============================================
// TEST 9: Repository Methods
// =============================================
require_once '../core/Controller.php';
require_once '../app/Repositories/SessionRepository.php';
require_once '../app/Models/ClassSession.php';

test("SessionRepository: getSessionsByDateRange method exists", function() {
    return method_exists('SessionRepository', 'getSessionsByDateRange') ? true : ['ok' => false, 'detail' => 'Method missing'];
});

require_once '../app/Repositories/EngagementRepository.php';
require_once '../app/Models/Engagement.php';
test("EngagementRepository: recalculateScore method exists", function() {
    return method_exists('EngagementRepository', 'recalculateScore') ? true : ['ok' => false, 'detail' => 'Method missing'];
});

require_once '../app/Repositories/SystemConfigRepository.php';
require_once '../app/Models/SystemConfig.php';
test("SystemConfigRepository: getConfigsArray method exists", function() {
    return method_exists('SystemConfigRepository', 'getConfigsArray') ? true : ['ok' => false, 'detail' => 'Method missing'];
});

// =============================================
// TEST 10: Functional - API Logic
// =============================================
test("Functional: SystemConfig getConfigsArray returns 8 keys", function() use ($db) {
    $repo = new SystemConfigRepository(new SystemConfig());
    $configs = $repo->getConfigsArray();
    $cnt = count($configs);
    return $cnt === 8 ? ['ok' => true, 'detail' => "8 config keys loaded"] : ['ok' => false, 'detail' => "Expected 8, got $cnt"];
});

test("Functional: CourseStudents - each course has <=30 students", function() use ($db) {
    $r = $db->query("SELECT course_id, COUNT(*) as cnt FROM course_students GROUP BY course_id HAVING cnt > 30");
    $violations = $r->fetchAll();
    if (empty($violations)) {
        return ['ok' => true, 'detail' => 'All courses have <=30 students'];
    }
    $details = array_map(fn($v) => "course_id={$v['course_id']}:{$v['cnt']}sv", $violations);
    return ['ok' => false, 'detail' => 'Courses with >30: ' . implode(', ', $details)];
});

test("Functional: All courses have a teacher assigned", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM courses WHERE teacher_id IS NULL");
    $cnt = (int)$r->fetchColumn();
    return $cnt === 0 ? ['ok' => true, 'detail' => 'All courses have teacher_id'] : ['ok' => false, 'detail' => "$cnt courses without teacher"];
});

test("Functional: Sessions date within summer (Jun-Aug 2026)", function() use ($db) {
    $r = $db->query("SELECT COUNT(*) FROM class_sessions WHERE session_date >= '2026-06-15' AND session_date <= '2026-08-01'");
    $cnt = (int)$r->fetchColumn();
    return $cnt > 0 ? ['ok' => true, 'detail' => "$cnt summer sessions"] : ['ok' => false, 'detail' => 'No sessions in summer range'];
});

// =============================================
// TEST 11: Helper Classes (Tái cấu trúc)
// =============================================
require_once '../app/Helpers/AttendanceSessionHelper.php';
require_once '../app/Helpers/AttendanceStatsHelper.php';
require_once '../app/Helpers/AlertEngine.php';
require_once '../app/Helpers/ScheduleHelper.php';

test("AttendanceSessionHelper: Class exists", function() {
    return class_exists('AttendanceSessionHelper') ? true : ['ok' => false, 'detail' => 'AttendanceSessionHelper class missing'];
});

test("AttendanceSessionHelper: getStatus returns format array", function() {
    $session = [
        'status' => 'inactive',
        'attendance_expires_at' => null
    ];
    $statusInfo = AttendanceSessionHelper::getStatus($session);
    return (is_array($statusInfo) && isset($statusInfo['status']) && isset($statusInfo['remaining_minutes'])) 
        ? ['ok' => true, 'detail' => 'Returned status: ' . $statusInfo['status']] 
        : ['ok' => false, 'detail' => 'Returned invalid format'];
});

test("AttendanceStatsHelper: Class exists", function() {
    return class_exists('AttendanceStatsHelper') ? true : ['ok' => false, 'detail' => 'AttendanceStatsHelper class missing'];
});

test("AttendanceStatsHelper: getStudentStats returns valid response/null", function() {
    $stats = AttendanceStatsHelper::getStudentStats(999999, 999999);
    return ($stats === null || is_array($stats)) ? true : ['ok' => false, 'detail' => 'Returned invalid format'];
});

test("AlertEngine: Class exists", function() {
    return class_exists('AlertEngine') ? true : ['ok' => false, 'detail' => 'AlertEngine class missing'];
});

test("AlertEngine: checkStudent returns array", function() {
    $alerts = AlertEngine::checkStudent(999999, 999999, false);
    return is_array($alerts) ? true : ['ok' => false, 'detail' => 'Expected array of alerts, got something else'];
});

test("ScheduleHelper: Class exists", function() {
    return class_exists('ScheduleHelper') ? true : ['ok' => false, 'detail' => 'ScheduleHelper class missing'];
});

test("ScheduleHelper: checkSessionConflict check", function() {
    $conflict = ScheduleHelper::checkSessionConflict('2026-06-15', '08:00:00', '10:00:00', 'P101');
    return ($conflict === null || is_array($conflict)) ? true : ['ok' => false, 'detail' => 'Expected null or array, got something else'];
});

test("ScheduleHelper: checkScheduleConflict check", function() {
    $conflict = ScheduleHelper::checkScheduleConflict([]);
    return $conflict === null ? true : ['ok' => false, 'detail' => 'Expected null, got conflict'];
});

// =============================================
// OUTPUT HTML
// =============================================
$total = $passed + $failed;
$pct = $total > 0 ? round(($passed / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduManager - Test Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .test-card { border-radius: 12px; border: none; }
        .PASS { color: #10b981; }
        .FAIL { color: #ef4444; }
        .ERROR { color: #f59e0b; }
        .progress-bar-custom { height: 8px; border-radius: 4px; }
        .summary-stat { border-radius: 12px; padding: 20px 28px; }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 860px;">
    <div class="text-center mb-4">
        <h2 class="fw-bold">🧪 EduManager - Test Runner</h2>
        <p class="text-muted">Automated test suite - <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="summary-stat bg-white text-center shadow-sm">
                <div class="fw-bold fs-1 text-success"><?= $passed ?></div>
                <div class="text-muted small fw-semibold">Tests PASSED</div>
            </div>
        </div>
        <div class="col-4">
            <div class="summary-stat bg-white text-center shadow-sm">
                <div class="fw-bold fs-1 <?= $failed > 0 ? 'text-danger' : 'text-success' ?>"><?= $failed ?></div>
                <div class="text-muted small fw-semibold">Tests FAILED</div>
            </div>
        </div>
        <div class="col-4">
            <div class="summary-stat bg-white text-center shadow-sm">
                <div class="fw-bold fs-1 <?= $pct >= 90 ? 'text-success' : ($pct >= 70 ? 'text-warning' : 'text-danger') ?>"><?= $pct ?>%</div>
                <div class="text-muted small fw-semibold">Success Rate</div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <div class="d-flex justify-content-between small text-muted mb-2">
            <span><?= $passed ?> passed</span>
            <span><?= $failed ?> failed</span>
        </div>
        <div class="progress progress-bar-custom">
            <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;" class="text-center">#</th>
                    <th>Test Name</th>
                    <th style="width: 80px;" class="text-center">Status</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $i => $r): ?>
                <tr>
                    <td class="text-center text-muted small"><?= $i + 1 ?></td>
                    <td class="small fw-medium"><?= htmlspecialchars($r['name']) ?></td>
                    <td class="text-center">
                        <?php if ($r['status'] === 'PASS'): ?>
                            <span class="badge bg-success-subtle text-success px-2 fw-bold" style="font-size: 0.75rem;"><i class="bi bi-check-lg me-1"></i>PASS</span>
                        <?php elseif ($r['status'] === 'FAIL'): ?>
                            <span class="badge bg-danger-subtle text-danger px-2 fw-bold" style="font-size: 0.75rem;"><i class="bi bi-x-lg me-1"></i>FAIL</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning px-2 fw-bold" style="font-size: 0.75rem;"><i class="bi bi-exclamation me-1"></i>ERR</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars($r['detail']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($failed === 0): ?>
    <div class="alert alert-success mt-4 border-0 rounded-3 shadow-sm text-center fw-bold">
        <i class="bi bi-trophy-fill me-2 text-warning"></i>
        Tất cả <?= $total ?> tests đều PASSED! Hệ thống EduManager hoạt động bình thường. ✅
    </div>
    <?php else: ?>
    <div class="alert alert-warning mt-4 border-0 rounded-3 shadow-sm text-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong><?= $failed ?> tests FAILED.</strong> Cần xem xét và sửa các mục trên.
    </div>
    <?php endif; ?>

    <div class="text-center mt-3 mb-2">
        <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] ?>/ins3064/final_project/public/login" class="btn btn-primary me-2">
            <i class="bi bi-box-arrow-in-right me-1"></i>Vào hệ thống
        </a>
        <a href="?" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Chạy lại test
        </a>
    </div>
</div>
</body>
</html>
