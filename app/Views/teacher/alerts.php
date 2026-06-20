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
</style>

<div class="d-flex flex-column gap-4">
    <!-- Chọn lớp giảng dạy -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Cảnh báo lớp học</h3>
            <div class="text-muted small">Theo dõi và xử lý các cảnh báo vắng vượt ngưỡng / điểm tương tác CPI thấp của sinh viên.</div>
        </div>
        <div style="width: 250px;">
            <label class="form-label fw-semibold text-secondary small mb-1">Chọn lớp học phần phụ trách</label>
            <select class="form-select" id="courseSelector" onchange="onCourseChange()">
                <option value="">-- Chọn lớp học --</option>
                <?php foreach ($myCourses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['code'] ?> - <?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Dữ liệu trống khi chưa chọn lớp -->
    <div id="noCourseWarning" class="card-modern py-5 text-center text-muted">
        <i class="bi bi-exclamation-triangle fs-1"></i>
        <h5 class="fw-bold mt-3">Vui lòng chọn một lớp học phần phụ trách ở góc trên để quản lý cảnh báo.</h5>
    </div>

    <!-- Khung chức năng chính -->
    <div id="mainContentArea" style="display: none;" class="card-modern p-4">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-bell-fill text-muted me-2"></i>Danh sách cảnh báo lớp học</h5>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Sinh viên</th>
                        <th>Mã SV</th>
                        <th>Nội dung cảnh báo</th>
                        <th>Thời gian gửi</th>
                        <th>Cố vấn học tập</th>
                        <th>Trạng thái xử lý</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="classAlertTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Đang tải cảnh báo...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal xử lý cảnh báo -->
<div class="modal fade" id="resolveAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Xử lý cảnh báo học tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resolveAlertForm">
                    <input type="hidden" id="resolveAlertId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Sinh viên bị cảnh báo</label>
                        <input type="text" class="form-control bg-light" id="resolveStudentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nội dung vi phạm</label>
                        <textarea class="form-control bg-light" id="resolveViolationText" rows="2" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Ghi chú xử lý / Biện pháp nhắc nhở</label>
                        <textarea class="form-control" id="resolveNotes" rows="3" placeholder="Ví dụ: Đã gọi điện nhắc nhở gia đình, yêu cầu gặp mặt trực tiếp..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Thay đổi trạng thái</label>
                        <select class="form-select" id="resolveStatus">
                            <option value="resolved">Đã giải quyết (Resolved)</option>
                            <option value="pending">Chờ giải quyết (Pending)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveResolveAlert()">Lưu kết quả</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Nạp file JS tương ứng
$extraJs = '<script src="' . BASE_URL . '/assets/js/teacher_alerts.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php'; 
?>
