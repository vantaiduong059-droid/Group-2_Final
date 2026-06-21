// public/assets/js/alerts.js

let teachersList = []; // Danh sách giáo viên để gán cố vấn học tập

document.addEventListener('DOMContentLoaded', () => {
    loadTeachers();
    loadAlerts();
    loadAlertConfigs();
});

// Tải danh sách giáo viên
function loadTeachers() {
    fetch(`${BASE_URL}/api/teachers`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                teachersList = res.data;
            }
        })
        .catch(err => console.error('Lỗi tải danh sách giảng viên', err));
}

// Tải danh sách cảnh báo
function loadAlerts() {
    fetch(`${BASE_URL}/api/alerts`)
        .then(response => response.json())
        .then(res => {
            const tbody = document.getElementById('alertTableBody');
            tbody.innerHTML = '';
            
            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach((alert) => {
                    const tr = document.createElement('tr');
                    
                    // Trạng thái badge
                    const statusClass = alert.status === 'resolved' ? 'status-resolved' : 'status-pending';
                    const statusText = alert.status === 'resolved' ? 'Đã xử lý' : 'Chờ xử lý';
                    const statusBadge = `<span class="status-badge ${statusClass}">${statusText}</span>`;

                    // Dropdown chọn Cố vấn học tập (Advisor)
                    let advisorSelect = `<select class="form-select form-select-sm" onchange="assignAdvisor(${alert.id}, this.value)" style="width: 170px;">`;
                    advisorSelect += `<option value="">-- Chọn cố vấn --</option>`;
                    teachersList.forEach(t => {
                        const selected = alert.advisor_id == t.id ? 'selected' : '';
                        advisorSelect += `<option value="${t.id}" ${selected}>${t.full_name}</option>`;
                    });
                    advisorSelect += `</select>`;

                    // Ghi chú của giáo viên
                    const notesHtml = alert.notes 
                        ? `<div class="small text-truncate" style="max-width: 200px;" title="${alert.notes}"><i class="bi bi-chat-dots-fill text-muted me-1"></i>${alert.notes}</div>` 
                        : '<span class="text-muted small">--</span>';

                    tr.innerHTML = `
                        <td>
                            <div class="fw-bold text-dark">${alert.user_name}</div>
                            <div class="text-muted small">${alert.student_code}</div>
                        </td>
                        <td>
                            <span class="fw-semibold text-primary">${alert.course_code}</span>
                            <div class="text-muted small text-truncate" style="max-width: 150px;">${alert.course_name}</div>
                        </td>
                        <td class="text-danger fw-semibold" style="font-size:0.85rem; max-width:220px;">${alert.message}</td>
                        <td>${statusBadge}</td>
                        <td>${advisorSelect}</td>
                        <td class="text-end">${notesHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Không có cảnh báo nào trong hệ thống.</td></tr>';
            }
        })
        .catch(err => {
            showToast('Lỗi khi tải dữ liệu cảnh báo.', 'danger');
            console.error(err);
        });
}

// Gán cố vấn học tập
function assignAdvisor(alertId, advisorId) {
    fetch(`${BASE_URL}/api/alerts/${alertId}/advisor`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ advisor_id: advisorId })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadAlerts(); // Tải lại bảng để đồng bộ dữ liệu
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra khi gán cố vấn.', 'danger');
        console.error(err);
    });
}

// Tải cấu hình ngưỡng
function loadAlertConfigs() {
    fetch(`${BASE_URL}/api/admin/configs`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const data = res.data;
                const form = document.getElementById('alertConfigForm');
                
                const limitInput = form.querySelector('[name="default_absent_limit"]');
                const thresholdInput = form.querySelector('[name="default_low_cpi_threshold"]');
                
                if (limitInput && data['default_absent_limit']) {
                    limitInput.value = data['default_absent_limit'].value;
                }
                if (thresholdInput && data['default_low_cpi_threshold']) {
                    thresholdInput.value = data['default_low_cpi_threshold'].value;
                }
            }
        })
        .catch(err => console.error('Lỗi tải cấu hình ngưỡng', err));
}

// Lưu cấu hình ngưỡng
function saveAlertConfigs() {
    const form = document.getElementById('alertConfigForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

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
            loadAlertConfigs();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi lưu cấu hình.', 'danger');
    });
}
