<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Giảng viên' ?> - EduManager</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-mortarboard-fill fs-6" style="background:none; padding:0; color:white;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 text-dark" style="line-height:1.2;">EduManager</div>
                    <div class="small text-muted" style="font-size: 0.7rem; font-weight: 500;">Cổng Giảng Viên</div>
                </div>
            </div>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="<?= BASE_URL ?>/teacher/dashboard" class="list-group-item list-group-item-action active">
                <i class="bi bi-journal-bookmark"></i> Lớp học giảng dạy
            </a>
            <a href="<?= BASE_URL ?>/admin/sessions" class="list-group-item list-group-item-action">
                <i class="bi bi-calendar-check"></i> Xem lịch dạy
            </a>
        </div>
        
        <!-- User Profile Bottom -->
        <div class="sidebar-footer">
            <div class="sidebar-profile">
                <img src="https://ui-avatars.com/api/?name=Teacher&background=10b981&color=fff" alt="User Avatar">
                <div class="sidebar-profile-info w-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sidebar-profile-name" style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= $_SESSION['user']['full_name'] ?></div>
                        <div class="sidebar-profile-role">Giảng viên</div>
                    </div>
                    <a href="<?= BASE_URL ?>/logout" class="text-muted" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid px-4 py-2">
                <button class="btn btn-light d-md-none me-3" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
                
                <div class="search-bar d-none d-md-block">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" placeholder="Tìm kiếm sinh viên, bài học...">
                </div>
                
                <div class="ms-auto d-flex align-items-center gap-3 topbar-actions">
                    <!-- Học kỳ hiện tại -->
                    <div class="btn btn-light rounded-pill px-3 py-2 fw-medium text-muted d-flex align-items-center gap-2" style="border: 1px solid var(--border-color-darker); font-size: 0.9rem;">
                        <i class="bi bi-calendar-event text-success"></i> Học kỳ II (2025 - 2026)
                    </div>

                    <!-- Theme toggle -->
                    <button class="btn-icon">
                        <i class="bi bi-sun"></i>
                    </button>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <?= $content ?>
        </div>
    </div>
    <!-- /#page-content-wrapper -->
</div>

<!-- Toast Container cho các thông báo JS -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if(isset($extraJs)) echo $extraJs; ?>
</body>
</html>
