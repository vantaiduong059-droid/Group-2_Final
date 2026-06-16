<?php
// app/Controllers/Api/SearchApiController.php
require_once '../core/Controller.php';
require_once '../config/database.php';

class SearchApiController extends Controller {
    
    public function search() {
        // Chỉ Admin mới được thực hiện tìm kiếm toàn cục
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        if ($q === '') {
            $this->jsonResponse([
                'status' => 'success',
                'data' => [
                    'students' => [],
                    'teachers' => [],
                    'courses' => []
                ]
            ]);
        }

        $db = Database::getInstance()->getConnection();
        $keyword = "%$q%";

                // 1. Tìm học sinh (students)
        $stmtStudents = $db->prepare("
            SELECT id, username, last_name, first_name, full_name, email 
            FROM users 
            WHERE role = 'student' 
            AND (full_name LIKE :keyword1 OR username LIKE :keyword2) 
            ORDER BY first_name ASC, last_name ASC
            LIMIT 5
        ");
        $stmtStudents->execute(['keyword1' => $keyword, 'keyword2' => $keyword]);
        $students = $stmtStudents->fetchAll();

        // 2. Tìm giáo viên (teachers)
        $stmtTeachers = $db->prepare("
            SELECT id, username, last_name, first_name, full_name, email 
            FROM users 
            WHERE role = 'teacher' 
            AND (full_name LIKE :keyword1 OR username LIKE :keyword2) 
            ORDER BY first_name ASC, last_name ASC
            LIMIT 5
        ");
        $stmtTeachers->execute(['keyword1' => $keyword, 'keyword2' => $keyword]);
        $teachers = $stmtTeachers->fetchAll();

        // 3. Tìm lớp học phần (courses)
        $stmtCourses = $db->prepare("
            SELECT id, code, class_code, name 
            FROM courses 
            WHERE name LIKE :keyword1 OR code LIKE :keyword2 OR class_code LIKE :keyword3 
            ORDER BY name ASC
            LIMIT 5
        ");
        $stmtCourses->execute(['keyword1' => $keyword, 'keyword2' => $keyword, 'keyword3' => $keyword]);
        $courses = $stmtCourses->fetchAll();

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'students' => $students,
                'teachers' => $teachers,
                'courses' => $courses
            ]
        ]);
    }
}
