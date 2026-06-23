// public/assets/js/teacher_alerts.js

let currentCourseId = null;
let alertsList = [];

document.addEventListener('DOMContentLoaded', () => {
    onCourseChange();
});

function onCourseChange() {
    const selector = document.getElementById('courseSelector');
    currentCourseId = selector.value;

    const noWarning = document.getElementById('noCourseWarning');
    const mainArea = document.getElementById('mainContentArea');

    if (!currentCourseId) {
        noWarning.style.display = 'block';
        mainArea.style.display = 'none';
        return;
    }

    noWarning.style.display = 'none';
    mainArea.style.display = 'block';

    loadClassAlerts();
}

// Tải danh sách cảnh báo của lớp học phần
function loadClassAlerts() {
    const tbody = document.getElementById('classAlertTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải danh sách cảnh báo...</td></tr>';

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/alerts`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                alertsList = res.data;
                renderAlertsTable(alertsList);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Không thể tải dữ liệu cảnh báo.</td></tr>';
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Lỗi kết nối máy chủ.</td></tr>';
        });
}

function renderAlertsTable(list) {
    const tbody = document.getElementById('classAlertTableBody');
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Lớp học phần này chưa có cảnh báo học tập nào.</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    list.forEach(a => {
        // Trạng thái badge
        const statusClass = a.status === 'resolved' ? 'status-resolved' : 'status-pending';
        const statusText = a.status === 'resolved' ? 'Đã xử lý' : 'Chờ xử lý';
        const statusBadge = `<span class="status-badge ${statusClass}">${statusText}</span>`;

        // Nút thao tác
        let actionBtn = '';
        if (a.status !== 'resolved') {
            actionBtn = `<button class="btn btn-sm btn-outline-primary fw-bold" onclick='openResolveModal(${JSON.stringify(a)})'><i class="bi bi-pencil-square"></i> Xử lý</button>`;
        } else {
            actionBtn = `<button class="btn btn-sm btn-outline-secondary fw-semibold" onclick='openResolveModal(${JSON.stringify(a)})'><i class="bi bi-eye"></i> Chi tiết</button>`;
        }

        const dateStr = new Date(a.created_at).toLocaleString('vi-VN');
        const advisorName = a.advisor_name ? a.advisor_name : '<span class="text-muted italic small">Chưa gán</span>';

        tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${a.user_name}</div>
                <div class="text-muted small">${a.student_email}</div>
            </td>
            <td><span class="fw-semibold text-muted small">${a.student_code}</span></td>
            <td class="text-danger fw-semibold" style="font-size:0.85rem; max-width: 250px;">${a.message}</td>
            <td class="text-muted small">${dateStr}</td>
            <td>${advisorName}</td>
            <td>${statusBadge}</td>
            <td class="text-end">${actionBtn}</td>
        `;
        tbody.appendChild(tr);
    });
}

function openResolveModal(a) {
    document.getElementById('resolveAlertForm').reset();
    document.getElementById('resolveAlertId').value = a.id;
    document.getElementById('resolveStudentName').value = `${a.user_name} (${a.student_code})`;
    document.getElementById('resolveViolationText').value = a.message;
    document.getElementById('resolveNotes').value = a.notes || '';
    document.getElementById('resolveStatus').value = a.status;

    // Nếu đã resolved thì disable form lưu
    const saveBtn = document.querySelector('#resolveAlertModal .btn-primary-modern');
    if (a.status === 'resolved') {
        document.getElementById('resolveNotes').readOnly = true;
        document.getElementById('resolveStatus').disabled = true;
        if (saveBtn) saveBtn.style.display = 'none';
    } else {
        document.getElementById('resolveNotes').readOnly = false;
        document.getElementById('resolveStatus').disabled = false;
        if (saveBtn) saveBtn.style.display = 'inline-block';
    }

    const modal = new bootstrap.Modal(document.getElementById('resolveAlertModal'));
    modal.show();
}

function saveResolveAlert() {
    const form = document.getElementById('resolveAlertForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const alertId = document.getElementById('resolveAlertId').value;
    const data = {
        notes: document.getElementById('resolveNotes').value,
        status: document.getElementById('resolveStatus').value
    };

    fetch(`${BASE_URL}/api/teacher/alerts/${alertId}/resolve`, {
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
            // Hide modal
            const modalEl = document.getElementById('resolveAlertModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            
            loadClassAlerts();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Có lỗi xảy ra khi lưu kết quả.', 'danger');
    });
}
