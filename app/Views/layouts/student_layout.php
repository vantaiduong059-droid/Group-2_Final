<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= isset($title) ? htmlspecialchars($title) : 'Sinh viên' ?> - EduManager</title>
    <meta name="description" content="Cổng sinh viên EduManager - Theo dõi chuyên cần và tương tác lớp học">

    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="EduManager">
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">

    <!-- PWA Manifest & Icons -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/images/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= BASE_URL ?>/assets/images/icon-192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">

    <style>
    /* Mobile Bottom Navigation */
    #mobileBottomNav {
        display: none;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        z-index: 1050;
        padding-bottom: env(safe-area-inset-bottom, 0);
        box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
    }
    #mobileBottomNav a {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 4px 8px;
        text-decoration: none;
        color: #6b7280;
        font-size: 0.65rem;
        font-weight: 500;
        transition: color 0.2s;
    }
    #mobileBottomNav a.active, #mobileBottomNav a:hover { color: #3b82f6; }
    #mobileBottomNav a i { font-size: 1.4rem; margin-bottom: 2px; }
    /* Install banner */
    #pwaInstallBanner {
        display: none;
        position: fixed;
        bottom: 70px; left: 12px; right: 12px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff;
        border-radius: 14px;
        padding: 14px 18px;
        z-index: 1060;
        box-shadow: 0 8px 24px rgba(59,130,246,0.4);
        align-items: center;
        gap: 12px;
    }
    @media (max-width: 767.98px) {
        #mobileBottomNav { display: flex !important; }
        #page-content-wrapper { padding-bottom: 70px !important; }
        #sidebar-wrapper { display: none !important; }
        #wrapper { display: block !important; }
    }
    </style>
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
                    <div class="small text-muted" style="font-size: 0.7rem; font-weight: 500;">Cổng Sinh Viên</div>
                </div>
            </div>
        </div>

        <div class="list-group list-group-flush mt-3">
            <?php
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $isActive = function($path) use ($currentPath) {
                return strpos($currentPath, $path) !== false ? 'active' : '';
            };
            ?>
            <a href="<?= BASE_URL ?>/student/dashboard" class="list-group-item list-group-item-action <?= $isActive('/student/dashboard') ?>">
                <i class="bi bi-person-badge me-2"></i> Chuyên cần &amp; Tương tác
            </a>
            <a href="<?= BASE_URL ?>/student/schedule" class="list-group-item list-group-item-action <?= $isActive('/student/schedule') ?>">
                <i class="bi bi-calendar-week me-2"></i> Lịch học
            </a>
            <a href="<?= BASE_URL ?>/student/my-courses" class="list-group-item list-group-item-action <?= $isActive('/student/my-courses') ?>">
                <i class="bi bi-journal-bookmark me-2"></i> Học phần của tôi
            </a>
            <a href="<?= BASE_URL ?>/student/profile" class="list-group-item list-group-item-action <?= $isActive('/student/profile') ?>">
                <i class="bi bi-person-circle me-2"></i> Hồ sơ cá nhân
            </a>
        </div>

        <!-- User Profile Bottom -->
        <div class="sidebar-footer">
            <div class="sidebar-profile">
                <a href="<?= BASE_URL ?>/student/profile" title="Hồ sơ cá nhân">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=3b82f6&color=fff&bold=true" alt="Avatar">
                </a>
                <div class="sidebar-profile-info w-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sidebar-profile-name" style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></div>
                        <div class="sidebar-profile-role">Sinh viên</div>
                    </div>
                    <a href="<?= BASE_URL ?>/logout" class="text-muted" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid px-4 py-2">
                <button class="btn btn-light d-md-none me-3" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <div class="ms-auto d-flex align-items-center gap-3 topbar-actions">
                    <div class="btn btn-light rounded-pill px-3 py-2 fw-medium text-muted d-flex align-items-center gap-2" style="border: 1px solid var(--border-color-darker); font-size: 0.9rem;">
                        <i class="bi bi-calendar-event text-primary"></i> Học kỳ II (2025 - 2026)
                    </div>
                    <!-- Chuông thông báo -->
                    <div class="position-relative" id="notifBellWrapper">
                        <button class="btn btn-light rounded-circle p-2" id="btnNotifBell" title="Thông báo" style="width:40px;height:40px;border:1px solid var(--border-color-darker);">
                            <i class="bi bi-bell fs-6"></i>
                        </button>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge" style="font-size:0.65rem;">0</span>
                        <!-- Dropdown thông báo -->
                        <div class="card shadow-lg border-0 d-none" id="notifDropdown" style="position:absolute;right:0;top:48px;width:320px;z-index:9999;border-radius:12px;max-height:400px;overflow-y:auto;">
                            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background:var(--bg-card,#fff);border-bottom:1px solid var(--border-color,#e5e7eb);">
                                <span class="fw-bold small">Thông báo</span>
                                <button class="btn btn-link btn-sm text-muted p-0" id="btnMarkAllRead">Dầ đọc tất cả</button>
                            </div>
                            <div id="notifList" class="py-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <?= $content ?>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Thông báo (Notification Bell)
    (function initNotifications() {
        const bell = document.getElementById('btnNotifBell');
        const badge = document.getElementById('notifBadge');
        const dropdown = document.getElementById('notifDropdown');
        const notifList = document.getElementById('notifList');
        const markAllBtn = document.getElementById('btnMarkAllRead');
        if (!bell) return;

        function loadNotifications() {
            fetch(BASE_URL + '/api/notifications?limit=10')
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') return;
                    const count = res.unread_count || 0;
                    if (count > 0) {
                        badge.innerText = count > 9 ? '9+' : count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                    renderNotifList(res.data || []);
                }).catch(() => {});
        }

        function renderNotifList(items) {
            notifList.innerHTML = '';
            if (!items.length) {
                notifList.innerHTML = '<div class="text-center text-muted small py-3">Không có thông báo nào</div>';
                return;
            }
            items.forEach(n => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 border-bottom ' + (n.is_read == 0 ? 'bg-primary-subtle' : '');
                div.style.cursor = n.link ? 'pointer' : 'default';
                div.innerHTML = `
                    <div class="d-flex gap-2 align-items-start">
                        <div class="${n.is_read == 0 ? 'text-primary' : 'text-muted'} mt-1"><i class="bi bi-bell-fill small"></i></div>
                        <div>
                            <div class="fw-semibold small" style="font-size:0.82rem;">${n.title || 'Thông báo'}</div>
                            <div class="text-muted" style="font-size:0.78rem;">${n.message || ''}</div>
                        </div>
                    </div>`;
                if (n.link) div.onclick = () => window.location.href = n.link;
                notifList.appendChild(div);
            });
        }

        bell.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('d-none');
            loadNotifications();
        });

        document.addEventListener('click', function() {
            dropdown.classList.add('d-none');
        });

        dropdown.addEventListener('click', e => e.stopPropagation());

        markAllBtn && markAllBtn.addEventListener('click', function() {
            fetch(BASE_URL + '/api/notifications/mark-read', { method: 'POST' })
                .then(r => r.json()).then(() => { badge.classList.add('d-none'); loadNotifications(); });
        });

        // Load lần đầu
        loadNotifications();
    })();
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>

<!-- Mobile Bottom Navigation -->
<nav id="mobileBottomNav">
    <?php
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $navItems = [
        ['url' => BASE_URL.'/student/dashboard', 'icon' => 'bi-person-badge', 'label' => 'Tương tác', 'match' => '/student/dashboard'],
        ['url' => BASE_URL.'/student/schedule',  'icon' => 'bi-calendar-week','label' => 'Lịch học',   'match' => '/student/schedule'],
        ['url' => BASE_URL.'/student/my-courses','icon' => 'bi-journal-bookmark','label' => 'Học phần', 'match' => '/student/my-courses'],
        ['url' => BASE_URL.'/student/profile',   'icon' => 'bi-person-circle', 'label' => 'Hồ sơ',    'match' => '/student/profile'],
    ];
    foreach ($navItems as $nav):
        $active = strpos($currentPath, $nav['match']) !== false ? 'active' : '';
    ?>
    <a href="<?= $nav['url'] ?>" class="<?= $active ?>">
        <i class="bi <?= $nav['icon'] ?>"></i>
        <?= $nav['label'] ?>
    </a>
    <?php endforeach; ?>
</nav>

<!-- PWA Install Banner -->
<div id="pwaInstallBanner">
    <img src="<?= BASE_URL ?>/assets/images/icon-192.png" alt="" style="width:40px;height:40px;border-radius:10px;flex-shrink:0;">
    <div style="flex:1;">
        <div style="font-size:0.9rem;font-weight:700;">Cài EduManager lên điện thoại</div>
        <div style="font-size:0.75rem;opacity:0.85;">Theo dõi lịch học mọi lúc mọi nơi</div>
    </div>
    <button id="btnInstallPwa" style="background:#fff;color:#3b82f6;border:none;border-radius:20px;padding:6px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;flex-shrink:0;">Cài</button>
    <button onclick="document.getElementById('pwaInstallBanner').style.display='none'" style="background:transparent;border:none;color:#fff;font-size:1.1rem;cursor:pointer;flex-shrink:0;">×</button>
</div>

<script>
// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
            .then(reg => console.log('[PWA] SW registered:', reg.scope))
            .catch(err => console.warn('[PWA] SW registration failed:', err));
    });
}

// PWA Install Prompt
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const banner = document.getElementById('pwaInstallBanner');
    if (banner) banner.style.display = 'flex';
});

const btnInstall = document.getElementById('btnInstallPwa');
if (btnInstall) {
    btnInstall.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        deferredPrompt = null;
        document.getElementById('pwaInstallBanner').style.display = 'none';
        if (outcome === 'accepted') {
            console.log('[PWA] App installed!');
        }
    });
}

window.addEventListener('appinstalled', () => {
    const banner = document.getElementById('pwaInstallBanner');
    if (banner) banner.style.display = 'none';
    console.log('[PWA] App installed successfully!');
});
</script>
</body>
</html>
