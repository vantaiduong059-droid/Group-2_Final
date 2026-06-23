<?php
// core/Router.php

class Router {
    private $routes = [];

    // Thêm một route GET
    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    // Thêm một route POST
    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }

    // Thêm một route PUT
    public function put($uri, $action) {
        $this->routes['PUT'][$uri] = $action;
    }

    // Thêm một route DELETE
    public function delete($uri, $action) {
        $this->routes['DELETE'][$uri] = $action;
    }

    public function dispatch($uri, $method) {
        // Tách query string nếu có
        $uri = explode('?', $uri)[0];
        
        // Loại bỏ đường dẫn cơ sở của thư mục chứa project
        // Giả sử request URI là /ins3064/final_project/public/api/users
        // Ta lấy phần URI thực sự bằng cách cắt bớt /ins3064/final_project/public
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // /ins3064/final_project/public
        if (strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/'; // route mặc định
        } else if (substr($uri, 0, 1) !== '/') {
            $uri = '/' . $uri;
        }

        // Kiểm tra xem route có tồn tại không
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $action) {
                // Hỗ trợ dynamic route, ví dụ: api/users/{id}
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_]+)', $route);
                $pattern = "@^" . $pattern . "$@D";

                if (preg_match($pattern, $uri, $matches)) {
                    // Xóa các keys là số trong mảng matches (do preg_match tạo ra)
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    // Xử lý action (Controller@method)
                    list($controllerName, $methodName) = explode('@', $action);

                    // Đảm bảo Controller được nạp
                    if (file_exists("../app/Controllers/Api/" . $controllerName . ".php")) {
                        require_once "../app/Controllers/Api/" . $controllerName . ".php";
                    } else if (file_exists("../app/Controllers/Web/" . $controllerName . ".php")) {
                        require_once "../app/Controllers/Web/" . $controllerName . ".php";
                    } else {
                        $this->sendNotFound("Controller $controllerName not found");
                        return;
                    }

                    $controller = new $controllerName();
                    
                    if (method_exists($controller, $methodName)) {
                        call_user_func_array([$controller, $methodName], array_values($params));
                        return;
                    } else {
                        $this->sendNotFound("Method $methodName not found in $controllerName");
                        return;
                    }
                }
            }
        }

        $this->sendNotFound("Route not found for $method $uri");
    }

    private function sendNotFound($message) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => $message]);
    }
}
