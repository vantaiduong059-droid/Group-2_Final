<?php ob_start(); ?>

<!-- Styles cho giao diện Lớp học phần -->
<style>
    .course-card {
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -1px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .course-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
    }
    
    .course-card-header {
        padding: 16px 20px;
        position: relative;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .course-card-code {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(4px);
        padding: 3px 8px;
        border-radius: 4px;
    }
    
    .course-card-class-code {
        font-size: 0.75rem;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(4px);
        padding: 3px 8px;
        border-radius: 4px;
    }
    
    .course-card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
        line-height: 1.4;
        min-height: 50px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .course-desc {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 15px;
        line-height: 1.5;
        min-height: 40px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .course-info-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 0.82rem;
        color: #475569;
        font-weight: 500;
    }
    
    .course-info-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .course-info-item i {
        color: #0284c7;
        font-size: 0.95rem;
    }
    
    .course-teacher {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.85rem;
    }
    
    .teacher-avatar-mini {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        color: #ffffff;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .course-card-actions {
        padding: 12px 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    .badge-tc {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
    }
</style>

<!-- Tiêu đề & Công cụ -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main); font-size: 1.6rem; letter-spacing: -0.5px;">Quản lý Lớp học phần</h2>
        <div class="text-muted small">Danh sách học phần và phân công giảng dạy chi tiết</div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <!-- Chuyển đổi View Grid/List -->
        <div class="btn-group btn-group-sm" style="border-radius: 6px; overflow: hidden;">
            <button class="btn btn-control-schedule active" id="btnGridView" onclick="setCourseView('grid')" title="Xem dạng lưới">
                <i class="bi bi-grid-3x3-gap-fill"></i> Lưới
            </button>
            <button class="btn btn-control-schedule" id="btnListView" onclick="setCourseView('list')" title="Xem dạng danh sách">
                <i class="bi bi-list-ul"></i> Bảng
            </button>
        </div>
        
        <button class="btn btn-primary-modern btn-sm px-3 fw-bold d-flex align-items-center gap-1" onclick="openCreateModal()" style="height: 31px; border-radius: 6px; font-size: 0.85rem; padding: 4px 12px; background: #0284c7; border: 1px solid #0284c7;">
            <i class="bi bi-plus-lg" style="font-size: 0.85rem;"></i> Thêm lớp học
        </button>
    </div>
</div>

<!-- Container động chứa dữ liệu khóa học (Sẽ được JS render dạng Grid hoặc Table) -->
<div id="courseContainer">
    <!-- Nội dung tải qua JS -->
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-2">Đang tải dữ liệu khóa học...</div>
    </div>
</div>

<!-- Course Modal (Create/Edit) -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Thêm khóa học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="courseForm">
                    <input type="hidden" id="courseId">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Mã môn học</label>
                            <input type="text" class="form-control" id="courseCode" placeholder="VD: INS3064" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Mã Lớp học phần</label>
                            <input type="text" class="form-control" id="classCode" placeholder="VD: INS306401" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Số tín chỉ</label>
                            <input type="number" class="form-control" id="courseCredits" min="1" max="4" value="3" oninput="calcPeriods()" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Tên lớp học phần</label>
                            <input type="text" class="form-control" id="courseName" placeholder="Ví dụ: Phát triển ứng dụng Web" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Số tiết (Tự tính)</label>
                            <input type="number" class="form-control bg-light fw-bold text-primary" id="coursePeriods" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Mô tả học phần</label>
                        <textarea class="form-control" id="courseDesc" rows="3" placeholder="Tóm tắt nội dung chính học phần..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Giảng viên phụ trách</label>
                        <select class="form-select" id="teacherId" required>
                            <option value="">-- Chọn giảng viên --</option>
                            <!-- Options load via JS -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveCourse()" style="background: #0284c7; border-color: #0284c7;">Lưu thông tin</button>
            </div>
        </div>
    </div>
</div>

<!-- Course Details Modal (Khung thông tin chi tiết & Quản lý sinh viên) -->
<div class="modal fade" id="courseDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Thông tin lớp học phần & Danh sách Sinh viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="courseDetailsBody" style="padding: 1.5rem 2rem 2rem;">
                <!-- Nội dung được nạp động từ JS -->
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/courses.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
