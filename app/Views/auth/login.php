<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - EduPortal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
        }
        
        /* Cột trái chứa hình nền trường học */
        .login-image-section {
            flex: 1;
            background: url('<?= BASE_URL ?>/assets/images/school_bg.png') no-repeat center center;
            background-size: cover;
            position: relative;
            display: none;
        }
        
        /* Hiển thị cột trái trên màn hình lớn */
        @media (min-width: 992px) {
            .login-image-section { display: flex; align-items: flex-end; padding: 3rem; }
        }

        /* Overlay tối nhẹ để chữ dễ đọc (nếu có) */
        .login-image-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.6) 100%);
        }

        .welcome-text {
            position: relative;
            z-index: 10;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* Cột phải chứa form đăng nhập */
        .login-form-section {
            width: 100%;
            max-width: 500px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            box-shadow: -10px 0 30px rgba(0,0,0,0.05);
            z-index: 20;
        }

        @media (min-width: 992px) {
            .login-form-section { min-width: 480px; width: 35%; }
        }

        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .role-btn {
            flex: 1;
        }

        /* Tùy chỉnh input radio ẩn đi */
        .btn-check:checked + .btn-outline-primary {
            background-color: #eef2ff;
            color: #4f46e5;
            border-color: #4f46e5;
            font-weight: 600;
        }
        
        .btn-outline-primary {
            color: #6b7280;
            border-color: #d1d5db;
        }

        .btn-outline-primary:hover {
            background-color: #f9fafb;
            color: #374151;
            border-color: #9ca3af;
        }

        .form-control-lg {
            font-size: 1rem;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .form-control-lg:focus {
            background-color: #fff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
        }

        .btn-login { 
            background: #4f46e5; 
            color: white; 
            font-weight: 600; 
            padding: 14px; 
            border-radius: 8px; 
            border: none;
            transition: all 0.2s;
        }
        .btn-login:hover { background: #4338ca; color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

        .logo-box {
            display: inline-block;
            background: #4f46e5;
            color: white;
            width: 48px; height: 48px;
            border-radius: 12px;
            text-align: center;
            line-height: 48px;
            font-size: 24px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body>

<!-- Cột Trái: Ảnh trường học -->
<div class="login-image-section">
    <div class="login-image-overlay"></div>
    <div class="welcome-text">
        <h1 class="fw-bold mb-2">Đại học Quốc Gia</h1>
        <p class="fs-5 mb-0 opacity-75">Hệ thống Cổng thông tin & Theo dõi chuyên cần</p>
    </div>
</div>

<!-- Cột Phải: Form Đăng nhập -->
<div class="login-form-section">
    <div>
        <div class="logo-box"><i class="bi bi-mortarboard-fill"></i></div>
        <h3 class="fw-bold text-dark mb-1">Đăng nhập hệ thống</h3>
        <p class="text-muted mb-4">Vui lòng chọn vai trò và nhập thông tin để tiếp tục.</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2.5 px-3 mb-3 small border-0 d-flex align-items-center gap-2" style="border-radius: 8px; background-color: #fef2f2; color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <span class="fw-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/login" method="POST">
            
            <!-- Chọn Vai trò -->
            <div class="role-selector">
                <input type="radio" class="btn-check" name="role" id="role_student" value="student" checked>
                <label class="btn btn-outline-primary role-btn" for="role_student">
                    <i class="bi bi-person me-1"></i> Sinh viên
                </label>

                <input type="radio" class="btn-check" name="role" id="role_teacher" value="teacher">
                <label class="btn btn-outline-primary role-btn" for="role_teacher">
                    <i class="bi bi-person-workspace me-1"></i> Giảng viên
                </label>

                <input type="radio" class="btn-check" name="role" id="role_admin" value="admin">
                <label class="btn btn-outline-primary role-btn" for="role_admin">
                    <i class="bi bi-shield-lock me-1"></i> Admin
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fw-medium small">Tên đăng nhập / Mã số</label>
                <input type="text" name="username" class="form-control form-control-lg" placeholder="Nhập mã sinh viên hoặc tài khoản..." required>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-muted fw-medium small mb-0">Mật khẩu</label>
                    <a href="#" class="text-decoration-none small text-primary fw-medium">Quên mật khẩu?</a>
                </div>
                <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" required>
            </div>
            
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-login btn-lg d-flex justify-content-center align-items-center">
                    Đăng nhập <i class="bi bi-arrow-right-short fs-4 ms-1"></i>
                </button>
            </div>
        </form>

        <div class="mt-5 text-muted small border-top pt-3">
            <p class="mb-1"><i class="bi bi-info-circle me-1"></i> <strong>Tài khoản test:</strong></p>
            <ul class="mb-0 text-secondary ps-3">
                <li>Admin: <code>admin</code> / <code>admin</code> (hoặc <code>123456</code>)</li>
                <li>Sinh viên: <code>student1</code> / <code>123456</code></li>
                <li>Giảng viên: <code>teacher1</code> / <code>123456</code></li>
            </ul>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
