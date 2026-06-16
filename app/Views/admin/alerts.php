<?php ob_start(); ?>

<style>
    .status-badge {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .status-pending { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-resolved { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }

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
    .config-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-main);
        margin-bottom: 6px;
    }
</style>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hệ thống Cảnh báo</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Quản lý Cảnh báo & Cố vấn Học tập</h3>
        </div>
    </div>

    <!-- Khung Tabs -->
    <div class="card-modern p-4">
        <ul class="nav nav-tabs nav-tabs-modern mb-4" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#tab-list" type="button" role="tab">
                    <i class="bi bi-list-check me-1"></i> Danh sách Cảnh báo đã gửi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#tab-config" type="button" role="tab">
                    <i class="bi bi-sliders me-1"></i> Cấu hình Ngưỡng Cảnh báo mặc định
                </button>
            </li>
        </ul>

        <div class="tab-content" id="alertTabsContent">
            <!-- Tab 1: Danh sách Cảnh báo -->
            <div class="tab-pane fade show active" id="tab-list" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Sinh viên</th>
                                <th>Khóa học</th>
                                <th>Nội dung cảnh báo</th>
                                <th>Trạng thái xử lý</th>
                                <th style="width: 200px;">Cố vấn học tập</th>
                                <th class="text-end">Ghi chú của GV</th>
                            </tr>
                        </thead>
                        <tbody id="alertTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải dữ liệu cảnh báo...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Cấu hình Ngưỡng -->
            <div class="tab-pane fade" id="tab-config" role="tabpanel">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="p-3 border rounded-3 bg-light">
                            <h6 class="fw-bold mb-3"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Cài đặt mặc định toàn trường</h6>
                            <form id="alertConfigForm">
                                <div class="mb-3">
                                    <label class="config-label">Giới hạn số buổi vắng tối đa (vượt quá sẽ cảnh báo)</label>
                                    <input type="number" min="1" class="form-control" name="default_absent_limit" required>
                                    <div class="form-text">Mặc định là 3 buổi học.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="config-label">Ngưỡng điểm CPI thấp cảnh báo (nhỏ hơn sẽ cảnh báo)</label>
                                    <input type="number" min="0" max="100" class="form-control" name="default_low_cpi_threshold" required>
                                    <div class="form-text">Mặc định là 50/100đ CPI.</div>
                                </div>
                                <button type="button" class="btn btn-primary-modern fw-bold" onclick="saveAlertConfigs()">Lưu cấu hình ngưỡng</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/alerts.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
