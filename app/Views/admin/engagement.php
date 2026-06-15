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
</style>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Điểm tương tác</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Điểm Tương tác & CPI Hệ thống</h3>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cấu hình hệ thống bên trái (col-md-5) -->
        <div class="col-12 col-md-5">
            <div class="card-modern p-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-gear-fill text-muted me-2"></i>Cấu hình CPI & Ngưỡng mặc định</h5>
                <div class="text-muted small mb-4">Các giá trị mặc định áp dụng toàn hệ thống khi giảng viên không thiết lập quy tắc riêng cho lớp của mình.</div>
                
                <form id="systemConfigForm">
                    <!-- Điểm số mặc định -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Có mặt mặc định</label>
                            <input type="number" step="1" class="form-control" name="default_rule_present_points" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Đi muộn mặc định</label>
                            <input type="number" step="1" class="form-control" name="default_rule_late_points" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Điểm Vắng mặc định</label>
                            <input type="number" step="1" class="form-control" name="default_rule_absent_points" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Hệ số phát biểu mặc định</label>
                            <input type="number" step="1" class="form-control" name="default_rule_interaction_points" required>
                        </div>
                    </div>

                    <!-- Trọng số -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Trọng số Chuyên cần (%)</label>
                            <input type="number" min="0" max="100" class="form-control" id="sysAttWeight" name="default_rule_attendance_weight" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Trọng số Quiz (%)</label>
                            <input type="number" min="0" max="100" class="form-control" id="sysQuizWeight" name="default_rule_quiz_weight" required>
                        </div>
                    </div>

                    <!-- Ngưỡng cảnh báo -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="config-label">Giới hạn vắng mặc định</label>
                            <input type="number" min="1" class="form-control" name="default_absent_limit" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="config-label">Ngưỡng cpi thấp mặc định</label>
                            <input type="number" min="0" max="100" class="form-control" name="default_low_cpi_threshold" required>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary-modern w-100 fw-bold mt-3" onclick="saveSystemConfigs()">Lưu cấu hình hệ thống</button>
                </form>
            </div>
        </div>

        <!-- Danh sách CPI sinh viên bên phải (col-md-7) -->
        <div class="col-12 col-md-7">
            <div class="card-modern p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-award-fill text-muted me-2"></i>Bảng Điểm CPI Sinh viên</h5>
                    <div style="width: 230px;">
                        <select class="form-select" id="courseFilter" onchange="loadStudentCPI()">
                            <option value="">-- Chọn lớp học phần --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['code'] ?> - <?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
                                <th class="text-center">Điểm chuyên cần</th>
                                <th class="text-center">Điểm phát biểu</th>
                                <th class="text-end">Chỉ số CPI</th>
                            </tr>
                        </thead>
                        <tbody id="studentCpiTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Vui lòng chọn lớp học phần để xem bảng điểm CPI của sinh viên.</td>
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
    loadSystemConfigs();
    
    // Đồng bộ trọng số khi nhập
    const attWeightInput = document.getElementById('sysAttWeight');
    const quizWeightInput = document.getElementById('sysQuizWeight');

    attWeightInput.addEventListener('input', () => {
        const val = parseInt(attWeightInput.value);
        if (val >= 0 && val <= 100) {
            quizWeightInput.value = 100 - val;
        }
    });

    quizWeightInput.addEventListener('input', () => {
        const val = parseInt(quizWeightInput.value);
        if (val >= 0 && val <= 100) {
            attWeightInput.value = 100 - val;
        }
    });
});

function loadSystemConfigs() {
    fetch(`${BASE_URL}/api/admin/configs`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const data = res.data;
                const form = document.getElementById('systemConfigForm');
                
                for (const key in data) {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key].value;
                    }
                }
            }
        })
        .catch(err => console.error('Lỗi tải cấu hình hệ thống', err));
}

function saveSystemConfigs() {
    const form = document.getElementById('systemConfigForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    // Check trọng số tổng bằng 100%
    const totalW = parseInt(data['default_rule_attendance_weight']) + parseInt(data['default_rule_quiz_weight']);
    if (totalW !== 100) {
        showToast('Tổng trọng số Chuyên cần và Quiz phải bằng 100%', 'warning');
        return;
    }

    fetch(`${BASE_URL}/api/admin/configs`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadSystemConfigs();
            loadStudentCPI(); // Tải lại điểm CPI nếu đang chọn lớp
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi lưu cấu hình.', 'danger');
    });
}

function loadStudentCPI() {
    const courseId = document.getElementById('courseFilter').value;
    const tbody = document.getElementById('studentCpiTableBody');

    if (!courseId) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Vui lòng chọn lớp học phần để xem bảng điểm CPI của sinh viên.</td></tr>`;
        return;
    }

    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin me-2"></i>Đang tải điểm CPI sinh viên...</td></tr>`;

    fetch(`${BASE_URL}/api/admin/courses/${courseId}/engagement`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const list = res.data.scores;
                if (!list || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Lớp học này chưa có dữ liệu điểm CPI hoặc chưa có sinh viên đăng ký.</td></tr>`;
                    return;
                }

                tbody.innerHTML = '';
                list.forEach(s => {
                    let cpiClass = 'cpi-high';
                    if (s.total_score < 50) {
                        cpiClass = 'cpi-low';
                    } else if (s.total_score < 75) {
                        cpiClass = 'cpi-mid';
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><span class="fw-semibold text-dark">${s.student_name}</span></td>
                        <td><span class="text-muted small">${s.student_email.split('@')[0]}</span></td>
                        <td class="text-center">${s.attendance_points}đ</td>
                        <td class="text-center">${s.interaction_points}đ</td>
                        <td class="text-end">
                            <span class="cpi-badge ${cpiClass}">${s.total_score}đ</span>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Lỗi tải dữ liệu từ máy chủ.</td></tr>`;
        });
}
</script>

<?php 
$extraJs = '';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
