<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Hệ thống Cảnh báo</h2>
        <div class="text-muted small">Cảnh báo vắng học và vi phạm</div>
    </div>
</div>

<div class="card-modern">
    <div class="table-responsive">
        <table class="table-modern">
            <thead>
                    <tr>
                        <th class="ps-4 py-3">STT</th>
                        <th class="py-3">Nội dung cảnh báo</th>
                        <th class="py-3">Tài khoản</th>
                        <th class="py-3">Khóa học</th>
                        <th class="py-3">Thời gian</th>
                        <th class="py-3 text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="alertTableBody">
                    <tr><td colspan="6" class="text-center py-4">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </table>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/alerts.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
