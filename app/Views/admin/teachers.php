<?php ob_start(); ?>

<!-- Styles cho giao diện Giáo viên -->
<style>
    .teacher-card {
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -1px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 20px;
        text-align: center;
        position: relative;
    }
    
    .teacher-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
    }
    
    .teacher-avatar-large {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        color: #ffffff;
        font-weight: 700;
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        margin-bottom: 14px;
    }
    
    .teacher-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .teacher-username-badge {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 12px;
        border: 1px solid #cbd5e1;
    }
    
    .teacher-email {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .teacher-classes-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        width: 100%;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 12px;
    }
    
    .teacher-classes-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-start;
        width: 100%;
        min-height: 50px;
    }
    
    .class-chip {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .class-chip:hover {
        background-color: #dbeafe;
        color: #1e40af;
        transform: translateY(-1px);
    }
    
    .teacher-card-actions {
        position: absolute;
        top: 12px;
        right: 12px;
        display: flex;
        gap: 4px;
    }
    
    .btn-card-action {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        font-size: 0.8rem;
        transition: all 0.15s ease;
    }
    
    .btn-card-action:hover {
        background: #f8fafc;
        transform: scale(1.05);
    }
    
    .btn-card-action.edit:hover {
        color: #2563eb;
        border-color: #bfdbfe;
    }
    
    .btn-card-action.delete:hover {
        color: #dc2626;
        border-color: #fca5a5;
    }
    
    .btn-control-schedule {
        font-weight: 600;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 0.85rem;
        padding: 5px 12px;
        transition: all 0.15s ease;
    }
    
    .btn-control-schedule:hover {
        background: #f8fafc;
        color: #0284c7;
        border-color: #94a3b8;
    }
    
    .btn-control-schedule.active {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
    }
</style>

<!-- Tiêu đề & Công cụ -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main); font-size: 1.6rem; letter-spacing: -0.5px;">Quản lý Giáo viên</h2>
        <div class="text-muted small">Danh sách giảng viên phụ trách giảng dạy các lớp học phần</div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <!-- Chuyển đổi View Grid/List -->
        <div class="btn-group btn-group-sm" style="border-radius: 6px; overflow: hidden;">
            <button class="btn btn-control-schedule active" id="btnGridView" onclick="setTeacherView('grid')" title="Xem dạng lưới">
                <i class="bi bi-grid-3x3-gap-fill"></i> Lưới
            </button>
            <button class="btn btn-control-schedule" id="btnListView" onclick="setTeacherView('list')" title="Xem dạng danh sách">
                <i class="bi bi-list-ul"></i> Bảng
            </button>
        </div>
        
        <button class="btn btn-primary-modern btn-sm px-3 fw-bold d-flex align-items-center gap-1" onclick="openTeacherModal()" style="height: 31px; border-radius: 6px; font-size: 0.85rem; padding: 4px 12px; background: #0284c7; border: 1px solid #0284c7;">
            <i class="bi bi-person-plus" style="font-size: 0.9rem;"></i> Thêm giáo viên
        </button>
    </div>
</div>

<!-- Container động chứa danh sách giáo viên -->
<div id="teacherContainer">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-2">Đang tải dữ liệu giảng viên...</div>
    </div>
</div>

<!-- Teacher Modal (Create/Edit) -->
<div class="modal fade" id="teacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="teacherModalTitle">Thêm giảng viên mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="teacherForm">
                    <input type="hidden" id="teacherId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Họ và Tên</label>
                        <input type="text" class="form-control" id="teacherName" placeholder="Ví dụ: Nguyễn Văn Giao Viên" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tên đăng nhập (Username)</label>
                        <input type="text" class="form-control" id="teacherUsername" placeholder="Ví dụ: teacher1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Email liên lạc</label>
                        <input type="email" class="form-control" id="teacherEmail" placeholder="Ví dụ: teacher1@example.com" required>
                    </div>
                    <div class="alert alert-info py-2.5 mb-0 border-0 text-primary small d-flex align-items-center" style="background-color: #eff6ff;">
                        <i class="bi bi-info-circle-fill fs-5 me-2.5 opacity-80"></i>
                        <span>Mật khẩu mặc định khi tạo tài khoản giảng viên mới là: <b>123456</b></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveTeacher()" style="background: #0284c7; border-color: #0284c7;">Lưu thông tin</button>
            </div>
        </div>
    </div>
</div>

<!-- Course Details Modal (Khi bấm vào các chip môn học) -->
<div class="modal fade" id="courseDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Thông tin chi tiết học phần</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="courseDetailsBody">
                <div class="text-center py-4">Đang tải...</div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/teachers.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
