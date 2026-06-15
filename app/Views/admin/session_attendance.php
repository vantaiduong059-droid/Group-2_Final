<?php ob_start(); ?>

<!-- Cấu hình CSS tùy chỉnh cho trang điểm danh -->
<style>
    .attendance-status-btn {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .attendance-status-btn:hover {
        background: #f8fafc;
        color: #334155;
    }
    .attendance-status-btn.active-present {
        background-color: var(--success-light);
        border-color: var(--success);
        color: var(--success);
    }
    .attendance-status-btn.active-late {
        background-color: var(--warning-light);
        border-color: var(--warning);
        color: var(--warning);
    }
    .attendance-status-btn.active-absent {
        background-color: var(--danger-light);
        border-color: var(--danger);
        color: var(--danger);
    }
    .attendance-status-btn.active-excused {
        background-color: #f1f5f9;
        border-color: #94a3b8;
        color: #475569;
    }
    .info-card-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 2px;
    }
    .info-card-val {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
    }
    .btn-counter {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color-darker);
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .btn-counter:hover {
        background: var(--primary-light);
        color: var(--primary);
        border-color: var(--primary);
    }
</style>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb điều hướng -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/sessions" class="text-decoration-none text-muted">Lịch học</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết điểm danh</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Chi tiết điểm danh buổi học</h3>
        </div>
        <a href="<?= BASE_URL ?>/admin/sessions" class="btn btn-light rounded-pill px-3 py-2 fw-semibold text-muted d-flex align-items-center gap-2" style="border: 1px solid var(--border-color-darker); font-size: 0.9rem;">
            <i class="bi bi-arrow-left"></i> Quay lại Lịch học
        </a>
    </div>

    <!-- Thông tin tổng quan buổi học -->
    <div class="card-modern">
        <div class="row g-4" id="sessionInfoContainer">
            <div class="col-6 col-md-3">
                <div class="info-card-label">Khóa học / Lớp học phần</div>
                <div class="info-card-val text-primary" id="infoCourseName">Đang tải...</div>
                <div class="small text-muted" id="infoCourseCode">--</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-card-label">Giảng viên phụ trách</div>
                <div class="info-card-val" id="infoTeacherName">Đang tải...</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="info-card-label">Thời gian & Phòng học</div>
                <div class="info-card-val" id="infoDateTime">Đang tải...</div>
                <div class="small text-muted" id="infoRoom">--</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="info-card-label">Trạng thái điểm danh</div>
                <div id="infoStatusBadge"><span class="badge bg-secondary">--</span></div>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-center justify-content-end gap-2">
                <button class="btn btn-sm btn-primary-modern px-3 fw-bold" id="btnToggleSession" style="display:none;"></button>
            </div>
        </div>
    </div>

    <!-- Bảng danh sách sinh viên điểm danh -->
    <div class="card-modern">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-people-fill text-muted me-2"></i>Danh sách sinh viên lớp học phần</h5>
                <div class="text-muted small">Nhấp chọn trạng thái để cập nhật điểm danh và cộng điểm tương tác trực tiếp.</div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="small fw-semibold text-secondary">Chọn nhanh cả lớp:</span>
                <button class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="bulkMark('present')"><i class="bi bi-check-all"></i> Đi học hết</button>
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="bulkMark('absent')"><i class="bi bi-x-all"></i> Vắng hết</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-modern" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Mã sinh viên</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th class="text-center" style="width: 320px;">Trạng thái điểm danh</th>
                        <th class="text-center" style="width: 120px;">Điểm phát biểu</th>
                        <th class="text-end">Thời gian ghi nhận</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải danh sách sinh viên...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const SESSION_ID = <?= $sessionId ?>;
</script>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/session_attendance.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
