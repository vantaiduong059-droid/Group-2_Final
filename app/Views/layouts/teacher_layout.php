<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Giảng viên' ?> - EduManager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<?php 
$noSidebar = isset($noSidebar) ? $noSidebar : (preg_match('/\/teacher\/dashboard/', $_SERVER['REQUEST_URI'])); 
$avatarUrl = $_SESSION['user']['avatar_url'] ?? null;
$initials = urlencode($_SESSION['user']['full_name'] ?? 'GV');
$avatarSrc = $avatarUrl ?: "https://ui-avatars.com/api/?name={$initials}&background=10b981&color=fff";
?>
<div class="d-flex <?= $noSidebar ? 'no-sidebar' : '' ?>" id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-mortarboard-fill fs-6" style="color:white;background:none;padding:0;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 text-dark" style="line-height:1.2;">EduManager</div>
                    <div class="small text-muted" style="font-size: 0.7rem; font-weight: 500;">Cổng Giảng Viên</div>
                </div>
            </div>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="<?= BASE_URL ?>/teacher/dashboard" class="list-group-item list-group-item-action <?= (preg_match('/\/teacher\/dashboard/', $_SERVER['REQUEST_URI'])) ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i> Trang chủ
            </a>
            <a href="<?= BASE_URL ?>/teacher/my-courses" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'my-courses') !== false || strpos($_SERVER['REQUEST_URI'], 'course-students') !== false) ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i> Lớp học của tôi
            </a>
            <a href="<?= BASE_URL ?>/teacher/sessions" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'sessions') !== false || strpos($_SERVER['REQUEST_URI'], 'session-detail') !== false) ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Lịch học
            </a>
            <a href="<?= BASE_URL ?>/teacher/quizzes" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'quizzes') !== false) ? 'active' : '' ?>">
                <i class="bi bi-chat-left-text"></i> Quiz & Thảo luận
            </a>
            <a href="<?= BASE_URL ?>/teacher/engagement" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'engagement') !== false) ? 'active' : '' ?>">
                <i class="bi bi-award"></i> Điểm tương tác
            </a>
            <a href="<?= BASE_URL ?>/teacher/alerts" class="list-group-item list-group-item-action <?= (strpos($_SERVER['REQUEST_URI'], 'alerts') !== false) ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle"></i> Cảnh báo lớp
            </a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid px-4 py-2">
                <?php if ($noSidebar): ?>
                    <!-- Logo khi không có Sidebar (Dashboard) -->
                    <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="<?= BASE_URL ?>/teacher/dashboard">
                        <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-mortarboard-fill fs-6" style="color:white;background:none;padding:0;"></i>
                        </div>
                        <span class="fw-bold fs-5 text-dark" style="line-height:1.2;">EduManager</span>
                    </a>
                <?php endif; ?>
                
                <!-- Nút Hamburger Menu luôn hiển thị cạnh nút Trang chủ -->
                <button class="btn btn-light me-2" id="sidebarToggle" title="Ẩn/Hiện Menu"><i class="bi bi-list fs-4"></i></button>
                
                <a href="<?= BASE_URL ?>/teacher/dashboard" class="btn btn-light me-3" title="Trang chủ">
                    <i class="bi bi-house-door-fill"></i>
                </a>
                
                <div class="search-bar d-none d-md-block me-3">
                    <i class="bi bi-search"></i>
                    <input type="text" id="globalSearchInput" class="form-control" placeholder="Tìm kiếm sinh viên, lớp...">
                    <div id="searchDropdown" class="search-dropdown" style="display:none;"></div>
                </div>
                
                <div class="ms-auto d-flex align-items-center gap-3 topbar-actions">
                    <!-- Dropdown học kỳ đồng bộ -->
                    <div class="dropdown" id="semesterDropdown">
                        <button class="btn btn-light rounded-pill px-3 py-2 fw-medium text-muted d-flex align-items-center gap-2 border" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.9rem;">
                            <i class="bi bi-calendar-event text-success"></i>
                            <span id="currentSemesterText">Học kỳ hè (2025-2026)</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="#" data-semester="summer">Học kỳ hè (2025-2026)</a></li>
                            <li><a class="dropdown-item" href="#" data-semester="semester1">Học kỳ 1 (2025-2026)</a></li>
                            <li><a class="dropdown-item" href="#" data-semester="semester2">Học kỳ 2 (2025-2026)</a></li>
                        </ul>
                    </div>

                    <!-- Chuông thông báo Dropdown -->
                    <div class="dropdown" id="notificationDropdown">
                        <button class="btn-icon position-relative dropdown-toggle-custom" data-bs-toggle="dropdown" aria-expanded="false" id="notifBtn" title="Thông báo">
                            <i class="bi bi-bell"></i>
                            <span class="notif-badge" id="notifBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;border-radius:50%;font-size:0.65rem;width:16px;height:16px;align-items:center;justify-content:center;font-weight:700;z-index: 10;">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-0 shadow border-0" style="width: 320px; border-radius: 12px; overflow: hidden; max-height: 400px; z-index: 1050;">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Thông báo</h6>
                                <span class="badge bg-primary-subtle text-primary" id="notifUnreadCount">0 mới</span>
                            </div>
                            <div id="notifListContainer" style="max-height: 280px; overflow-y: auto;">
                                <div class="text-center py-4 text-muted small">Không có thông báo nào.</div>
                            </div>
                            <div class="p-2 border-top text-center bg-light">
                                <a href="<?= BASE_URL ?>/teacher/notifications" class="text-decoration-none small fw-semibold text-primary">Xem tất cả</a>
                            </div>
                        </ul>
                    </div>

                    <!-- Avatar + Tên click mở dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none gap-2 dropdown-toggle-custom" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                            <span class="d-none d-md-inline text-dark fw-medium" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                            <i class="bi bi-chevron-down text-muted small d-none d-md-inline"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher/profile"><i class="bi bi-person-circle me-2"></i>Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <?= $content ?>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    
    // Toggle Sidebar
    const toggleBtn = document.getElementById('sidebarToggle');
    const wrapper = document.getElementById('wrapper');
    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', () => {
            const isDashboard = window.location.pathname.includes('/teacher/dashboard');
            if (isDashboard) {
                if (wrapper.classList.contains('no-sidebar')) {
                    wrapper.classList.remove('no-sidebar');
                    if (window.innerWidth < 768) {
                        wrapper.classList.add('toggled');
                    }
                } else {
                    wrapper.classList.add('no-sidebar');
                    wrapper.classList.remove('toggled');
                }
            } else {
                wrapper.classList.toggle('toggled');
            }
        });
    }

    // Sync Học kỳ bằng localStorage
    const savedSem = localStorage.getItem('selected_semester') || 'summer';
    const semMap = {
        'summer': 'Học kỳ hè (2025-2026)',
        'semester1': 'Học kỳ 1 (2025-2026)',
        'semester2': 'Học kỳ 2 (2025-2026)'
    };
    const currentSemText = document.getElementById('currentSemesterText');
    if (currentSemText && semMap[savedSem]) {
        currentSemText.textContent = semMap[savedSem];
    }
    const dropdownItems = document.querySelectorAll('#semesterDropdown .dropdown-item');
    dropdownItems.forEach(item => {
        if (item.getAttribute('data-semester') === savedSem) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const sem = item.getAttribute('data-semester');
            localStorage.setItem('selected_semester', sem);
            if (currentSemText && semMap[sem]) {
                currentSemText.textContent = semMap[sem];
            }
            dropdownItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            window.dispatchEvent(new Event('semesterChanged'));
        });
    });

    // AJAX Chuông thông báo
    function loadNotifications() {
        fetch(`${BASE_URL}/api/notifications?limit=10`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    const count = res.unread_count || 0;
                    const badge = document.getElementById('notifBadge');
                    const unreadText = document.getElementById('notifUnreadCount');
                    
                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                    
                    if (unreadText) {
                        unreadText.textContent = `${count} mới`;
                    }

                    // Render dropdown list
                    const container = document.getElementById('notifListContainer');
                    if (container) {
                        const list = res.data || [];
                        if (list.length === 0) {
                            container.innerHTML = '<div class="text-center py-4 text-muted small">Không có thông báo nào.</div>';
                        } else {
                            container.innerHTML = list.map(n => {
                                const unreadStyle = n.is_read == 0 ? 'background-color: rgba(99, 102, 241, 0.04); font-weight: 500;' : '';
                                const dateStr = new Date(n.created_at).toLocaleDateString('vi-VN', {month:'numeric',day:'numeric',hour:'2-digit',minute:'2-digit'});
                                const link = n.link || '#';
                                return `
                                    <div class="px-3 py-2 border-bottom notif-dropdown-item" style="${unreadStyle} cursor:pointer;" onclick="location.href='${BASE_URL}${link}'">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small text-dark">${n.title}</span>
                                            <span class="text-muted" style="font-size:0.65rem;">${dateStr}</span>
                                        </div>
                                        <div class="text-muted small text-truncate" style="font-size:0.75rem;">${n.message}</div>
                                    </div>
                                `;
                            }).join('');
                        }
                    }
                }
            })
            .catch(err => console.error('Lỗi tải thông báo:', err));
    }

    // Đánh dấu đã đọc khi click mở chuông
    const notifBtn = document.getElementById('notifBtn');
    if (notifBtn) {
        notifBtn.addEventListener('click', () => {
            fetch(`${BASE_URL}/api/notifications/read`, { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const badge = document.getElementById('notifBadge');
                        if (badge) badge.style.display = 'none';
                        const unreadText = document.getElementById('notifUnreadCount');
                        if (unreadText) unreadText.textContent = '0 mới';
                    }
                })
                .catch(err => console.error(err));
        });
    }

    // Khởi tạo chạy lần đầu và định kỳ 60 giây
    loadNotifications();
    setInterval(loadNotifications, 60000);

    // Search debounce
    let searchTimer;
    const searchInput = document.getElementById('globalSearchInput');
    const searchDrop = document.getElementById('searchDropdown');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = searchInput.value.trim();
            if (q.length < 2) { searchDrop.style.display = 'none'; return; }
            searchTimer = setTimeout(() => {
                fetch(`${BASE_URL}/api/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json()).then(res => {
                        if (!res.data || Object.values(res.data).every(v => v.length === 0)) {
                            searchDrop.innerHTML = '<div class="p-3 text-muted small">Không tìm thấy kết quả</div>';
                        } else {
                            let html = '';
                            if (res.data.students?.length) { html += '<div class="search-group-title">Sinh viên</div>'; res.data.students.forEach(s => { html += `<a class="search-item" href="${BASE_URL}/admin/students">${s.full_name} <span class="text-muted">${s.email}</span></a>`; }); }
                            if (res.data.teachers?.length) { html += '<div class="search-group-title">Giảng viên</div>'; res.data.teachers.forEach(t => { html += `<a class="search-item" href="${BASE_URL}/teacher/dashboard">${t.full_name}</a>`; }); }
                            if (res.data.courses?.length) { html += '<div class="search-group-title">Lớp học</div>'; res.data.courses.forEach(c => { html += `<a class="search-item" href="${BASE_URL}/teacher/my-courses">${c.name} <span class="text-muted">${c.code}</span></a>`; }); }
                            searchDrop.innerHTML = html;
                        }
                        searchDrop.style.display = 'block';
                    });
            }, 350);
        });
        document.addEventListener('click', e => { if (!searchInput.contains(e.target)) searchDrop.style.display = 'none'; });
    }
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if(isset($extraJs)) echo $extraJs; ?>
</body>
</html>
