<?php ob_start(); ?>

<!-- Styles cho giao diện Sinh viên -->
<style>
    .student-card {
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
    
    .student-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
    }
    
    .student-avatar-large {
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
    
    .student-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .student-username-badge {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 12px;
        border: 1px solid #cbd5e1;
    }
    
    .student-email {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .student-card-actions {
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
        <h2 class="fw-bold mb-1" style="color: var(--text-main); font-size: 1.6rem; letter-spacing: -0.5px;">Quản lý Sinh viên</h2>
        <div class="text-muted small">Danh sách sinh viên, quản lý tài khoản và thông tin liên hệ</div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <!-- Chuyển đổi View Grid/List -->
        <div class="btn-group btn-group-sm" style="border-radius: 6px; overflow: hidden;">
            <button class="btn btn-control-schedule active" id="btnGridView" onclick="setStudentView('grid')" title="Xem dạng lưới">
                <i class="bi bi-grid-3x3-gap-fill"></i> Lưới
            </button>
            <button class="btn btn-control-schedule" id="btnListView" onclick="setStudentView('list')" title="Xem dạng danh sách">
                <i class="bi bi-list-ul"></i> Bảng
            </button>
        </div>
        
        <button class="btn btn-primary-modern btn-sm px-3 fw-bold d-flex align-items-center gap-1" onclick="openStudentModal()" style="height: 31px; border-radius: 6px; font-size: 0.85rem; padding: 4px 12px; background: #0284c7; border: 1px solid #0284c7;">
            <i class="bi bi-person-plus" style="font-size: 0.9rem;"></i> Thêm sinh viên
        </button>
    </div>
</div>

<!-- Hàng bộ lọc filter theo Khóa và Ngành học -->
<div class="card border-0 shadow-sm mb-4 p-3 bg-white" style="border-radius: 12px;">
    <div class="row g-3 align-items-center">
        <div class="col-md-3">
            <label class="form-label fw-semibold text-secondary small mb-1">Tìm kiếm sinh viên</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchName" class="form-control bg-light border-start-0" placeholder="Tên hoặc tài khoản..." oninput="filterStudents()">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-secondary small mb-1">Lọc theo Khóa</label>
            <select id="filterCohort" class="form-select form-select-sm bg-light" onchange="filterStudents()">
                <option value="">-- Tất cả các Khóa --</option>
                <option value="K65">K65</option>
                <option value="K66">K66</option>
                <option value="K67">K67</option>
                <option value="K68">K68</option>
            </select>
        </div>
        <div class="col-md-6 d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label fw-semibold text-secondary small mb-1">Lọc theo Ngành học</label>
                <select id="filterMajor" class="form-select form-select-sm bg-light" onchange="filterStudents()">
                    <option value="">-- Tất cả các Ngành --</option>
                </select>
            </div>
            <button class="btn btn-outline-secondary btn-sm fw-semibold d-flex align-items-center justify-content-center" onclick="resetFilters()" style="height: 31px; width: 31px;" title="Reset bộ lọc">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
</div>

<!-- Container động chứa danh sách sinh viên -->
<div id="studentContainer">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-2">Đang tải dữ liệu sinh viên...</div>
    </div>
</div>

<!-- Student Modal (Create/Edit) -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="studentModalTitle">Thêm sinh viên mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="studentForm">
                    <input type="hidden" id="studentId">
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Họ và tên đệm</label>
                            <input type="text" class="form-control" id="studentLastName" placeholder="Ví dụ: Trần Thị" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Tên</label>
                            <input type="text" class="form-control" id="studentFirstName" placeholder="Sinh Viên 1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tên đăng nhập (Username)</label>
                        <input type="text" class="form-control" id="studentUsername" placeholder="Ví dụ: student1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Email liên lạc</label>
                        <input type="email" class="form-control" id="studentEmail" placeholder="Ví dụ: student1@example.com" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Khóa đào tạo</label>
                            <select class="form-select" id="studentCohort" required>
                                <option value="">-- Chọn khóa --</option>
                                <option value="K65">K65</option>
                                <option value="K66">K66</option>
                                <option value="K67">K67</option>
                                <option value="K68">K68</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Ngành học</label>
                            <select class="form-select" id="studentMajor" required>
                                <option value="">-- Chọn ngành --</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info py-2.5 mb-0 border-0 text-primary small d-flex align-items-center" style="background-color: #eff6ff;">
                        <i class="bi bi-info-circle-fill fs-5 me-2.5 opacity-80"></i>
                        <span>Mật khẩu mặc định khi tạo tài khoản sinh viên mới là: <b>123456</b></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveStudent()" style="background: #0284c7; border-color: #0284c7;">Lưu thông tin</button>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/students.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
