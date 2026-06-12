// public/assets/js/alerts.js

document.addEventListener('DOMContentLoaded', () => {
    loadAlerts();
});

function loadAlerts() {
    fetch(`${BASE_URL}/api/alerts`)
        .then(response => response.json())
        .then(res => {
            const tbody = document.getElementById('alertTableBody');
            tbody.innerHTML = '';
            
            if(res.status === 'success' && res.data.length > 0) {
                res.data.forEach((alert, index) => {
                    const tr = document.createElement('tr');
                    
                    // Highlight unread alerts
                    if (alert.is_read == 0) {
                        tr.classList.add('table-warning');
                    }
                    
                    let roleBadge = alert.role === 'teacher' ? '<span class="badge bg-primary">Giảng viên</span>' : '<span class="badge bg-success">Sinh viên</span>';

                    tr.innerHTML = `
                        <td class="ps-4 fw-medium text-muted">${index + 1}</td>
                        <td class="fw-bold ${alert.is_read == 0 ? 'text-danger' : ''}">${alert.message}</td>
                        <td>${roleBadge} ${alert.user_name}</td>
                        <td class="text-primary">${alert.course_name}</td>
                        <td class="text-muted"><small>${new Date(alert.created_at).toLocaleString('vi-VN')}</small></td>
                        <td class="text-end pe-4">
                            ${alert.is_read == 0 ? 
                                `<button class="btn btn-sm btn-outline-success" onclick="markAsRead(${alert.id})"><i class="bi bi-check2-all"></i> Đã xử lý</button>` 
                                : '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Đã đọc</span>'
                            }
                        </td>
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

function markAsRead(id) {
    fetch(`${BASE_URL}/api/alerts/${id}`, {
        method: 'PUT'
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            showToast(res.message, 'success');
            loadAlerts(); // Reload table
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra', 'danger');
        console.error(err);
    });
}
