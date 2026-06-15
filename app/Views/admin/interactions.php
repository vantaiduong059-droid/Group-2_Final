<?php ob_start(); ?>

<style>
    .summary-badge-card {
        border-radius: 16px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.25s ease;
    }
    .summary-badge-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }
    .badge-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .badge-blue { background-color: rgba(37, 99, 235, 0.1); color: #2563eb; }
    .badge-green { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-yellow { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    
    .nav-tabs-modern {
        border-bottom: 2px solid var(--border-color-darker);
        gap: 8px;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: all 0.2s ease;
        background: transparent;
    }
    .nav-tabs-modern .nav-link:hover {
        color: var(--primary);
        background: rgba(37, 99, 235, 0.04);
    }
    .nav-tabs-modern .nav-link.active {
        color: var(--primary);
        background: transparent;
    }
    .nav-tabs-modern .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
    }
    .interaction-type-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .type-question { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .type-answer { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .type-discussion { background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
</style>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tương tác lớp học</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Giám sát Tương tác Lớp học</h3>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div style="width: 250px;">
                <select class="form-select" id="courseFilter" onchange="loadData()">
                    <option value="">-- Tất cả lớp học phần --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code']) ?> - <?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary-modern btn-sm px-3 fw-bold d-flex align-items-center gap-2" onclick="loadData()" style="height: 38px;">
                <i class="bi bi-arrow-clockwise"></i> Làm mới dữ liệu
            </button>
        </div>
    </div>

    <!-- Thẻ tóm tắt -->
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="summary-badge-card">
                <div class="badge-icon-wrapper badge-blue">
                    <i class="bi bi-hand-index-thumb-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Tổng lượt Tương tác</div>
                    <h3 class="fw-bold mb-0 mt-1" id="totalInteractionsCount">--</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="summary-badge-card">
                <div class="badge-icon-wrapper badge-green">
                    <i class="bi bi-question-square-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Tổng số Mini-Quiz</div>
                    <h3 class="fw-bold mb-0 mt-1" id="totalQuizzesCount">--</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="summary-badge-card">
                <div class="badge-icon-wrapper badge-yellow">
                    <i class="bi bi-chat-right-text-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Chủ đề Thảo luận</div>
                    <h3 class="fw-bold mb-0 mt-1" id="totalDiscussionsCount">--</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs điều hướng -->
    <div class="card-modern p-4">
        <ul class="nav nav-tabs nav-tabs-modern mb-4" id="interactionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="logs-tab" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button" role="tab">
                    <i class="bi bi-list-stars me-1"></i> Nhật ký tương tác
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#tab-quizzes" type="button" role="tab">
                    <i class="bi bi-patch-question me-1"></i> Danh sách Mini-Quiz
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="discussions-tab" data-bs-toggle="tab" data-bs-target="#tab-discussions" type="button" role="tab">
                    <i class="bi bi-chat-quote me-1"></i> Thảo luận lớp học
                </button>
            </li>
        </ul>

        <div class="tab-content" id="interactionTabsContent">
            <!-- Tab 1: Log Tương tác -->
            <div class="tab-pane fade show active" id="tab-logs" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
                                <th>Khóa học / Lớp HP</th>
                                <th>Hình thức tương tác</th>
                                <th class="text-center">Điểm cộng</th>
                                <th class="text-end">Thời gian ghi nhận</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu tương tác...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Mini Quiz -->
            <div class="tab-pane fade" id="tab-quizzes" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tiêu đề Quiz</th>
                                <th>Khóa học / Lớp HP</th>
                                <th>Thời gian hoạt động</th>
                                <th class="text-center">Thang điểm</th>
                                <th class="text-center">Lượt sinh viên nộp bài</th>
                                <th class="text-end">Trạng thái hoạt động</th>
                            </tr>
                        </thead>
                        <tbody id="quizzesTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải danh sách Quiz...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Thảo luận -->
            <div class="tab-pane fade" id="tab-discussions" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Chủ đề thảo luận</th>
                                <th>Nội dung tóm tắt</th>
                                <th>Khóa học / Lớp HP</th>
                                <th>Người khởi tạo</th>
                                <th class="text-center">Số phản hồi</th>
                                <th class="text-end">Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody id="discussionsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải danh sách thảo luận...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadData();
});

function loadData() {
    // Show spinner
    document.getElementById('logsTableBody').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu tương tác...</td></tr>`;
    document.getElementById('quizzesTableBody').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải danh sách Quiz...</td></tr>`;
    document.getElementById('discussionsTableBody').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải danh sách thảo luận...</td></tr>`;

    const courseId = document.getElementById('courseFilter').value;
    let url = `${BASE_URL}/api/admin/interactions/summary`;
    if (courseId) {
        url += `?course_id=${courseId}`;
    }

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const data = res.data;
                
                // Update stats
                document.getElementById('totalInteractionsCount').textContent = data.logs.length;
                document.getElementById('totalQuizzesCount').textContent = data.quizzes.length;
                document.getElementById('totalDiscussionsCount').textContent = data.discussions.length;

                // Render Logs
                renderLogs(data.logs);

                // Render Quizzes
                renderQuizzes(data.quizzes);

                // Render Discussions
                renderDiscussions(data.discussions);
            }
        })
        .catch(err => {
            console.error('Lỗi tải tóm tắt tương tác', err);
            showToast('Lỗi kết nối máy chủ.', 'danger');
        });
}

function renderLogs(logs) {
    const tbody = document.getElementById('logsTableBody');
    if (!logs || logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có hoạt động tương tác nào trên hệ thống.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    logs.forEach(l => {
        const typeClasses = {
            'question': 'type-question',
            'answer': 'type-answer',
            'discussion': 'type-discussion'
        };
        const typeTexts = {
            'question': 'Đặt câu hỏi',
            'answer': 'Trả lời',
            'discussion': 'Thảo luận'
        };

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="fw-semibold">${l.student_name}</span></td>
            <td><span class="text-muted small">${l.student_code}</span></td>
            <td>
                <span class="fw-semibold text-primary">${l.course_code}</span>
                <div class="small text-muted text-truncate" style="max-width: 200px;">${l.course_name}</div>
            </td>
            <td>
                <span class="interaction-type-badge ${typeClasses[l.type] || 'bg-light'}">
                    ${typeTexts[l.type] || l.type}
                </span>
            </td>
            <td class="text-center"><span class="badge bg-success-subtle text-success fw-bold">+${l.points_awarded}</span></td>
            <td class="text-end text-muted small">${formatDateTime(new Date(l.created_at))}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderQuizzes(quizzes) {
    const tbody = document.getElementById('quizzesTableBody');
    if (!quizzes || quizzes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có Mini-Quiz nào được tạo.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    quizzes.forEach(q => {
        const now = new Date();
        const start = new Date(q.start_time);
        const end = new Date(q.end_time);
        
        let statusHtml = '';
        if (now < start) {
            statusHtml = '<span class="badge bg-warning text-dark">Lên lịch</span>';
        } else if (now > end) {
            statusHtml = '<span class="badge bg-secondary">Đã đóng</span>';
        } else {
            statusHtml = '<span class="badge bg-success animate-pulse">Đang mở</span>';
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="fw-semibold">${q.title}</span></td>
            <td>
                <span class="fw-semibold text-primary">${q.course_code}</span>
                <div class="small text-muted text-truncate" style="max-width: 200px;">${q.course_name}</div>
            </td>
            <td class="small">
                <div>Bắt đầu: ${formatDateTime(start)}</div>
                <div class="text-muted">Hết hạn: ${formatDateTime(end)}</div>
            </td>
            <td class="text-center"><span class="fw-bold">${q.total_marks}đ</span></td>
            <td class="text-center">
                <span class="badge bg-info-subtle text-info px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                    <i class="bi bi-people-fill me-1"></i>${q.submission_count}
                </span>
            </td>
            <td class="text-end">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderDiscussions(discussions) {
    const tbody = document.getElementById('discussionsTableBody');
    if (!discussions || discussions.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có chủ đề thảo luận nào.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    discussions.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="fw-semibold text-dark">${d.title}</span></td>
            <td><div class="text-muted text-truncate small" style="max-width: 250px;" title="${d.content}">${d.content}</div></td>
            <td>
                <span class="fw-semibold text-primary">${d.course_code}</span>
                <div class="small text-muted text-truncate" style="max-width: 150px;">${d.course_name}</div>
            </td>
            <td>
                <span class="fw-semibold">${d.creator_name}</span>
                <div class="small text-muted" style="font-size: 0.75rem;">${d.creator_role === 'teacher' ? 'Giảng viên' : 'Sinh viên'}</div>
            </td>
            <td class="text-center">
                <span class="badge bg-primary-subtle text-primary px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                    <i class="bi bi-chat-text-fill me-1"></i>${d.reply_count}
                </span>
            </td>
            <td class="text-end text-muted small">${formatDateTime(new Date(d.created_at))}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Helper formatting date
function formatDateTime(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}
</script>

<?php 
$extraJs = '';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
