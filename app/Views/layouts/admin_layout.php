<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Trang chủ' ?> - EduManager</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <div class="small text-muted" style="font-size: 0.7rem; font-weight: 500;">Hệ thống quản lý sinh viên</div>
                </div>
            </div>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="<?= BASE_URL ?>/admin/dashboard" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i> Trang chủ
            </a>
            <a href="<?= BASE_URL ?>/admin/students" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Sinh viên
            </a>
            <a href="<?= BASE_URL ?>/admin/teachers" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'teachers') !== false) ? 'active' : '' ?>">
                <i class="bi bi-person-workspace"></i> Giảng viên
            </a>
            <a href="<?= BASE_URL ?>/admin/courses" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'courses') !== false) ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i> Lớp học
            </a>
            <a href="<?= BASE_URL ?>/admin/sessions" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'sessions') !== false) ? 'active' : '' ?>">
                <i class="bi bi-calendar-event"></i> Lịch học
            </a>
            <a href="<?= BASE_URL ?>/admin/interactions" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'interactions') !== false) ? 'active' : '' ?>">
                <i class="bi bi-chat-left-text"></i> Tương tác lớp học
            </a>
            <a href="<?= BASE_URL ?>/admin/engagement" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'engagement') !== false) ? 'active' : '' ?>">
                <i class="bi bi-award"></i> Điểm tương tác
            </a>
            <a href="<?= BASE_URL ?>/admin/alerts" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'alerts') !== false) ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle"></i> Cảnh báo
            </a>
        </div>
        
        <!-- User Profile Bottom -->
        <div class="sidebar-footer">
            <div class="sidebar-profile">
                <img src="https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=fff" alt="User Avatar">
                <div class="sidebar-profile-info w-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sidebar-profile-name"><?= isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : 'Quản trị viên' ?></div>
                        <div class="sidebar-profile-role">Quản trị viên</div>
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
                <button class="btn btn-light me-2" id="sidebarToggle" title="Ẩn/Hiện Menu"><i class="bi bi-list fs-4"></i></button>
                <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-light me-3" title="Trang chủ">
                    <i class="bi bi-house-door-fill"></i>
                </a>
                
                <!-- Search Bar -->
                <div class="search-bar d-none d-md-block position-relative">
                    <i class="bi bi-search"></i>
                    <input type="text" id="globalSearchInput" class="form-control" placeholder="Tìm sinh viên, lớp học, giảng viên...">
                    <!-- Dropdown gợi ý tìm kiếm -->
                    <div id="searchDropdown" class="search-results-dropdown">
                        <!-- Nội dung gợi ý -->
                    </div>
                </div>
                
                <div class="ms-auto d-flex align-items-center gap-3 topbar-actions">
                    <!-- Dropdown Năm học -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-3 py-2 fw-medium text-muted d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: 1px solid var(--border-color-darker); font-size: 0.9rem;">
                            <i class="bi bi-mortarboard text-primary"></i> Năm học 2025 - 2026 <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px;">
                            <li><a class="dropdown-item active" href="#">Năm học 2025 - 2026</a></li>
                            <li><a class="dropdown-item" href="#">Năm học 2024 - 2025</a></li>
                        </ul>
                    </div>

                    <!-- Notification Bell -->
                    <div class="dropdown" id="notificationDropdownContainer">
                        <button class="btn-icon position-relative" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span id="unreadNotifBadge" class="badge-notif d-none"></span>
                        </button>
                        <ul id="notifList" class="dropdown-menu dropdown-menu-end shadow border-0 p-2" aria-labelledby="notifDropdown" style="width: 320px; border-radius: 16px; max-height: 400px; overflow-y: auto;">
                            <li><h6 class="dropdown-header text-dark fw-bold">Thông báo mới nhất</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="notifPlaceholder" class="text-center py-3 text-muted small">Không có thông báo mới nào.</li>
                            <li class="dropdown-divider notif-divider"></li>
                            <li class="text-center py-1 notif-all-link"><a href="<?= BASE_URL ?>/admin/notifications" class="text-decoration-none small fw-semibold text-primary">Xem tất cả</a></li>
                        </ul>
                    </div>
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
    
    // 1. Toggle Sidebar
    const toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // 3. Search Bar AJAX with Debounce
    const searchInput = document.getElementById('globalSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');
    let searchTimeout = null;

    if (searchInput && searchDropdown) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const val = e.target.value.trim();
            
            if (val === '') {
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`${BASE_URL}/api/search?q=${encodeURIComponent(val)}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            renderSearchResults(res.data, val);
                        }
                    })
                    .catch(err => console.error(err));
            }, 350);
        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = 'none';
            }
        });
        
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim() !== '') {
                searchDropdown.style.display = 'block';
            }
        });
    }

    function renderSearchResults(data, query) {
        searchDropdown.innerHTML = '';
        let hasData = false;

        // Render Lớp học phần
        if (data.courses && data.courses.length > 0) {
            hasData = true;
            searchDropdown.insertAdjacentHTML('beforeend', '<div class="category-title">Lớp học phần</div>');
            data.courses.forEach(c => {
                searchDropdown.insertAdjacentHTML('beforeend', `
                    <a href="${BASE_URL}/admin/courses?search=${encodeURIComponent(c.code)}" class="result-item">
                        <i class="bi bi-journal-bookmark me-2 text-muted"></i>
                        <strong>${c.code}</strong> - ${c.name} (${c.class_code})
                    </a>
                `);
            });
        }

        // Render Sinh viên
        if (data.students && data.students.length > 0) {
            hasData = true;
            searchDropdown.insertAdjacentHTML('beforeend', '<div class="category-title">Sinh viên</div>');
            data.students.forEach(s => {
                searchDropdown.insertAdjacentHTML('beforeend', `
                    <a href="${BASE_URL}/admin/students?search=${encodeURIComponent(s.username)}" class="result-item">
                        <i class="bi bi-people me-2 text-muted"></i>
                        <strong>${s.full_name}</strong> (${s.username}) - ${s.email}
                    </a>
                `);
            });
        }

        // Render Giảng viên
        if (data.teachers && data.teachers.length > 0) {
            hasData = true;
            searchDropdown.insertAdjacentHTML('beforeend', '<div class="category-title">Giảng viên</div>');
            data.teachers.forEach(t => {
                searchDropdown.insertAdjacentHTML('beforeend', `
                    <a href="${BASE_URL}/admin/teachers?search=${encodeURIComponent(t.username)}" class="result-item">
                        <i class="bi bi-person-workspace me-2 text-muted"></i>
                        <strong>${t.full_name}</strong> (${t.username}) - ${t.email}
                    </a>
                `);
            });
        }

        if (!hasData) {
            searchDropdown.innerHTML = '<div class="no-result"><i class="bi bi-info-circle me-1"></i>Không tìm thấy kết quả</div>';
        }

        searchDropdown.style.display = 'block';
    }

    // 4. Notification AJAX and Mark Read
    const unreadBadge = document.getElementById('unreadNotifBadge');
    const notifList = document.getElementById('notifList');
    const notifDropdownBtn = document.getElementById('notifDropdown');

    function loadNotifications() {
        fetch(`${BASE_URL}/api/notifications`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    // Update badge
                    if (res.unread_count > 0) {
                        unreadBadge.textContent = res.unread_count;
                        unreadBadge.classList.remove('d-none');
                    } else {
                        unreadBadge.classList.add('d-none');
                    }

                    // Render list
                    const listItems = notifList.querySelectorAll('.notif-item');
                    listItems.forEach(el => el.remove());

                    if (res.data && res.data.length > 0) {
                        const placeholder = document.getElementById('notifPlaceholder');
                        if (placeholder) placeholder.style.display = 'none';

                        res.data.forEach(n => {
                            const unreadStyle = n.is_read == 0 ? 'background-color: var(--primary-light); border-left: 3px solid var(--primary);' : '';
                            const timeStr = formatRelativeTime(new Date(n.created_at));
                            const notifHtml = `
                                <li class="notif-item" style="${unreadStyle}">
                                    <a class="dropdown-item py-2 d-flex gap-3 align-items-start" href="${n.link ? BASE_URL + n.link : '#'}" style="white-space: normal;">
                                        <div class="bg-primary-subtle text-primary p-2 rounded-circle" style="line-height:1;"><i class="bi bi-bell-fill"></i></div>
                                        <div style="flex: 1;">
                                            <div class="fw-bold small">${n.title}</div>
                                            <div class="small text-muted" style="font-size:0.8rem; margin-top:2px;">${n.message}</div>
                                            <div class="small text-muted" style="font-size:0.7rem; margin-top:4px;"><i class="bi bi-clock me-1"></i>${timeStr}</div>
                                        </div>
                                    </a>
                                </li>
                            `;
                            const allLink = notifList.querySelector('.notif-all-link');
                            if (allLink) {
                                allLink.insertAdjacentHTML('beforebegin', notifHtml);
                            } else {
                                notifList.insertAdjacentHTML('beforeend', notifHtml);
                            }
                        });
                    } else {
                        const placeholder = document.getElementById('notifPlaceholder');
                        if (placeholder) placeholder.style.display = 'block';
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // Load ngay khi trang tải
    loadNotifications();

    // Mark as read when click bell dropdown
    if (notifDropdownBtn) {
        notifDropdownBtn.addEventListener('click', () => {
            if (!unreadBadge.classList.contains('d-none')) {
                // Đánh dấu đã đọc trong DB
                fetch(`${BASE_URL}/api/notifications/read`, { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            unreadBadge.classList.add('d-none');
                        }
                    })
                    .catch(err => console.error(err));
            }
        });
    }

    // Helper: Tính thời gian tương đối
    function formatRelativeTime(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHrs = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHrs / 24);

        if (diffMins < 1) return 'Vừa xong';
        if (diffMins < 60) return `${diffMins} phút trước`;
        if (diffHrs < 24) return `${diffHrs} giờ trước`;
        return `${diffDays} ngày trước`;
    }
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if(isset($extraJs)) echo $extraJs; ?>
</body>
</html>
