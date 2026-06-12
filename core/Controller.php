<?php
// core/Controller.php

class Controller {

    // Helper: Trả về JSON response cho các API RESTful
    protected function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Helper: Hiển thị View (HTML)
    protected function view($viewPath, $data = []) {
        // Biến $data thành các biến cục bộ
        extract($data);
        
        $file = "../app/Views/" . $viewPath . ".php";
        if (file_exists($file)) {
            require_once $file;
        } else {
            die("View does not exist: " . $viewPath);
        }
    }

    // Helper: Lấy input dữ liệu từ body của Request (dùng cho JSON post trong Fetch API)
    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}
