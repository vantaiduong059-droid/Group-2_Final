<?php ob_start(); ?>

<style>
    .notif-page-item {
        padding: 16px;
        border-radius: 12px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
        margin-bottom: 12px;
        cursor: pointer;
        display: flex;
        gap: 16px;
        align-items: flex-start;
    }
    .notif-page-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
        background: rgba(59, 130, 246, 0.02);
    }
    .notif-page-item.unread {
        border-left: 4px solid var(--primary);
        background: rgba(99, 102, 241, 0.03);
    }
    .notif-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .notif-icon-box.warning { background: #fee2e2; color: #ef4444; }
    .notif-icon-box.info { background: #eff6ff; color: #3b82f6; }
    .notif-icon-box.general { background: #f0fdf4; color: #22c55e; }
    
    .nav-pills-custom .nav-link {
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.88rem;
        padding: 6px 16px;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        background: transparent;
        transition: all 0.2s;
    }
    .nav-pills-custom .nav-link.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
</style>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb -->
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Thông báo</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0">Hộp thư thông báo</h3>
    </div>

    <!-- Filters & Actions -->
    <div class="card-modern p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="nav nav-pills nav-pills-custom gap-2" id="notifFilterPills">
                <button class="nav-link active" data-filter="all">Tất cả</button>
                <button class="nav-link" data-filter="warning">Cảnh báo học tập</button>
                <button class="nav-link" data-filter="general">Thông báo chung</button>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="markAllAsReadPage()">
                <i class="bi bi-check-all me-1"></i>Đánh dấu đã đọc tất cả
            </button>
        </div>
    </div>

    <!-- Notifications List -->
    <div>
        <div id="notificationsPageList">
            <div class="text-center py-5 text-muted"><i class="bi bi-arrow-repeat spin fs-4 d-block mb-2"></i>Đang tải thông báo...</div>
        </div>

        <!-- Load More Pagination -->
        <div class="text-center mt-4" id="paginationBox" style="display:none;">
            <button class="btn btn-primary-modern px-4 py-2" id="btnLoadMore" onclick="loadMoreNotifications()">Tải thêm thông báo</button>
        </div>
    </div>
</div>

<script>
let currentOffset = 0;
const limitPerPage = 10;
let allNotifications = [];
let activeFilter = 'all';
let totalNotifications = 0;

document.addEventListener('DOMContentLoaded', () => {
    loadPageNotifications(true);

    // Setup filter click events
    document.querySelectorAll('#notifFilterPills .nav-link').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('#notifFilterPills .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.getAttribute('data-filter');
            currentOffset = 0;
            loadPageNotifications(true);
        });
    });
});

function loadPageNotifications(isReset = false) {
    if (isReset) {
        currentOffset = 0;
        allNotifications = [];
        document.getElementById('notificationsPageList').innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-arrow-repeat spin fs-4 d-block mb-2"></i>Đang tải thông báo...</div>';
    }

    fetch(`${BASE_URL}/api/notifications?limit=${limitPerPage}&offset=${currentOffset}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                totalNotifications = res.total_count;
                const data = res.data || [];
                
                allNotifications = allNotifications.concat(data);
                renderPageNotifications();
            } else {
                document.getElementById('notificationsPageList').innerHTML = `<div class="text-center py-4 text-danger">Lỗi: ${res.message}</div>`;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('notificationsPageList').innerHTML = '<div class="text-center py-4 text-danger">Có lỗi xảy ra khi kết nối máy chủ.</div>';
        });
}

function renderPageNotifications() {
    const listContainer = document.getElementById('notificationsPageList');
    
    // Lọc theo loại
    let filtered = allNotifications;
    if (activeFilter === 'warning') {
        filtered = allNotifications.filter(n => n.title.toLowerCase().includes('cảnh báo') || n.message.toLowerCase().includes('cảnh báo'));
    } else if (activeFilter === 'general') {
        filtered = allNotifications.filter(n => !n.title.toLowerCase().includes('cảnh báo') && !n.message.toLowerCase().includes('cảnh báo'));
    }

    if (filtered.length === 0) {
        listContainer.innerHTML = `
            <div class="card-modern py-5 text-center text-muted">
                <i class="bi bi-mailbox2 fs-2 mb-2 d-block text-muted"></i>
                Hộp thư trống. Không có thông báo nào phù hợp bộ lọc này.
            </div>
        `;
        document.getElementById('paginationBox').style.display = 'none';
        return;
    }

    listContainer.innerHTML = filtered.map(n => {
        const isWarning = n.title.toLowerCase().includes('cảnh báo') || n.message.toLowerCase().includes('cảnh báo');
        const iconClass = isWarning ? 'warning' : 'general';
        const icon = isWarning ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>';
        const dateStr = new Date(n.created_at).toLocaleString('vi-VN');
        const unreadClass = n.is_read == 0 ? 'unread' : '';
        const targetLink = n.link || '#';

        return `
            <div class="notif-page-item ${unreadClass}" onclick="handleNotifClick('${targetLink}')">
                <div class="notif-icon-box ${iconClass}">
                    ${icon}
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                        <h6 class="fw-bold text-dark mb-0 small" style="font-size:0.95rem;">${n.title}</h6>
                        <span class="text-muted" style="font-size:0.72rem;"><i class="bi bi-clock me-1"></i>${dateStr}</span>
                    </div>
                    <p class="text-muted mb-0 small" style="font-size:0.83rem;">${n.message}</p>
                </div>
            </div>
        `;
    }).join('');

    // Hiển thị nút Tải thêm nếu còn dữ liệu trong db
    if (allNotifications.length < totalNotifications) {
        document.getElementById('paginationBox').style.display = 'block';
    } else {
        document.getElementById('paginationBox').style.display = 'none';
    }
}

function loadMoreNotifications() {
    currentOffset += limitPerPage;
    loadPageNotifications(false);
}

function markAllAsReadPage() {
    fetch(`${BASE_URL}/api/notifications/read`, { method: 'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                // Reload data
                loadPageNotifications(true);
                // Cập nhật chuông thông báo nếu có hàm load
                if (typeof loadNotifications === 'function') loadNotifications();
            }
        })
        .catch(err => console.error(err));
}

function handleNotifClick(link) {
    if (link && link !== '#') {
        window.location.href = BASE_URL + link;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
