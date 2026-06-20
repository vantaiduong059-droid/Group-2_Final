<?php ob_start(); ?>

<style>
    .config-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-main);
        margin-bottom: 6px;
    }
    .cpi-badge {
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.85rem;
    }
    .cpi-high { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .cpi-mid { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .cpi-low { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

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
</style>

<div class="d-flex flex-column gap-4">
    <!-- Chọn lớp giảng dạy -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Điểm tương tác & CPI lớp học phần</h3>
            <div class="text-muted small">Xem bảng điểm CPI của sinh viên và cấu hình quy tắc tính điểm riêng của lớp.</div>
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
        <i class="bi bi-award fs-1"></i>
        <h5 class="fw-bold mt-3">Vui lòng chọn một lớp học phần phụ trách ở góc trên để xem dữ liệu.</h5>
    </div>

    <!-- Khung chức năng chính -->
    <div id="mainContentArea" style="display: none;" class="row g-4">
        <!-- Bảng điểm CPI của lớp bên trái (col-md-7) -->
        <div class="col-12 col-md-7">
            <div class="card-modern p-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-person-lines-fill text-muted me-2"></i>Bảng Điểm CPI lớp học</h5>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
                                <th class="text-center">Điểm danh</th>
                                <th class="text-center">Phát biểu</th>
                                <th class="text-end">Chỉ số CPI</th>
                            </tr>
                        </thead>
                        <tbody id="studentCpiTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Đang tải bảng điểm...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cấu hình rules lớp học bên phải (col-md-5) -->
        <div class="col-12 col-md-5">
            <div class="card-modern p-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-sliders text-muted me-2"></i>Quy tắc & Trọng số của lớp</h5>
                <div class="text-muted small mb-4">Giảng viên có thể thiết lập quy tắc riêng để override cấu hình chung của nhà trường. Để trống các ô để kế thừa cài đặt hệ thống.</div>
                
                <form id="courseConfigForm">
                    <!-- Điểm số -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Có mặt</label>
                            <input type="number" step="1" class="form-control" name="rule_present_points" placeholder="Kế thừa">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Đi muộn</label>
                            <input type="number" step="1" class="form-control" name="rule_late_points" placeholder="Kế thừa">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Vắng mặt</label>
                            <input type="number" step="1" class="form-control" name="rule_absent_points" placeholder="Kế thừa">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Hệ số phát biểu</label>
                            <input type="number" step="1" class="form-control" name="rule_interaction_points" placeholder="Kế thừa">
                        </div>
                    </div>

                    <!-- Trọng số -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Trọng số Chuyên cần (%)</label>
                            <input type="number" min="0" max="100" class="form-control" id="classAttWeight" name="rule_attendance_weight" placeholder="Kế thừa">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Trọng số Quiz (%)</label>
                            <input type="number" min="0" max="100" class="form-control" id="classQuizWeight" name="rule_quiz_weight" placeholder="Kế thừa">
                        </div>
                    </div>

                    <!-- Ngưỡng cảnh báo -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Giới hạn vắng (buổi)</label>
                            <input type="number" min="1" class="form-control" name="rule_absent_limit" placeholder="Kế thừa">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Ngưỡng cpi thấp (đ)</label>
                            <input type="number" min="0" max="100" class="form-control" name="rule_low_cpi_threshold" placeholder="Kế thừa">
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary-modern w-100 fw-bold mt-3" onclick="saveCourseConfigs()">Lưu thay đổi quy tắc lớp</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// Nạp file JS tương ứng
$extraJs = '<script src="' . BASE_URL . '/assets/js/teacher_engagement.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php'; 
?>
