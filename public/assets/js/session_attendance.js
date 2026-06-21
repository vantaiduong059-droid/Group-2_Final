// public/assets/js/session_attendance.js

let studentList = []; // Lưu danh sách sinh viên hiện tại để xử lý hàng loạt

document.addEventListener('DOMContentLoaded', () => {
    loadSessionDetails();
    loadAttendanceList();
});

// Tải thông tin chi tiết buổi học
function loadSessionDetails() {
    fetch(`${BASE_URL}/api/sessions/${SESSION_ID}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const s = res.data;
                document.getElementById('infoCourseName').textContent = s.course_name;
                document.getElementById('infoCourseCode').textContent = `${s.course_code} - ${s.course_class_code || 'N/A'}`;
                document.getElementById('infoTeacherName').textContent = s.teacher_name || 'Chưa phân công';
                
                const sDate = formatDateToDDMMYYYY(new Date(s.session_date));
                document.getElementById('infoDateTime').textContent = `${sDate} (${s.start_time.substring(0, 5)} - ${s.end_time.substring(0, 5)})`;
                document.getElementById('infoRoom').textContent = `Phòng: ${s.room || 'N/A'}`;
                
                // Trạng thái badge
                const badgeContainer = document.getElementById('infoStatusBadge');
                let badgeHtml = '';
                const btnToggle = document.getElementById('btnToggleSession');
                
                if (s.status === 'scheduled') {
                    badgeHtml = '<span class="badge bg-warning text-dark">Sắp diễn ra</span>';
                    btnToggle.textContent = 'Mở buổi học';
                    btnToggle.className = 'btn btn-sm btn-success px-3 fw-bold';
                    btnToggle.onclick = () => startSession();
                    btnToggle.style.display = 'inline-block';
                } else if (s.status === 'active') {
                    badgeHtml = '<span class="badge bg-danger animate-pulse">Đang học (Mở cổng)</span>';
                    btnToggle.textContent = 'Kết thúc buổi học';
                    btnToggle.className = 'btn btn-sm btn-danger px-3 fw-bold';
                    btnToggle.onclick = () => stopSession();
                    btnToggle.style.display = 'inline-block';
                } else {
                    badgeHtml = '<span class="badge bg-success">Đã hoàn thành</span>';
                    btnToggle.style.display = 'none';
                }
                badgeContainer.innerHTML = badgeHtml;
            } else {
                showToast('Không thể tải thông tin buổi học.', 'danger');
            }
        })
        .catch(err => {
            console.error('Lỗi tải thông tin buổi học', err);
            showToast('Lỗi kết nối máy chủ.', 'danger');
        });
}

// Tải danh sách sinh viên và trạng thái điểm danh
function loadAttendanceList() {
    fetch(`${BASE_URL}/api/sessions/${SESSION_ID}/attendance`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                studentList = res.data;
                renderAttendanceTable(studentList);
            } else {
                showToast('Không thể tải danh sách điểm danh.', 'danger');
            }
        })
        .catch(err => {
            console.error('Lỗi tải danh sách điểm danh', err);
            showToast('Lỗi tải dữ liệu sinh viên.', 'danger');
        });
}

// Render bảng điểm danh sinh viên
function renderAttendanceTable(list) {
    const tbody = document.getElementById('attendanceTableBody');
    if (!list || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Không có sinh viên nào đăng ký học phần này.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    list.forEach(student => {
        const tr = document.createElement('tr');
        
        // Xác định class active cho các nút điểm danh
        const isPresent = student.status === 'present' ? 'active-present' : '';
        const isLate = student.status === 'late' ? 'active-late' : '';
        const isAbsent = student.status === 'absent' ? 'active-absent' : '';
        const isExcused = student.status === 'excused' ? 'active-excused' : '';

        // Format thời gian điểm danh
        const recordedTime = student.recorded_at ? formatTimeOnly(new Date(student.recorded_at)) : '--:--';

        tr.innerHTML = `
            <td><span class="fw-semibold">${student.username}</span></td>
            <td>
                <div class="table-user-cell">
                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=eff6ff&color=3b82f6" class="table-user-avatar" alt="Avatar">
                    <span class="table-user-name">${student.full_name}</span>
                </div>
            </td>
            <td><span class="text-muted small">${student.email}</span></td>
            <td class="text-center">
                <div class="btn-group" role="group">
                    <button type="button" class="attendance-status-btn ${isPresent}" onclick="updateStatus(${student.student_id}, 'present')">Đi học</button>
                    <button type="button" class="attendance-status-btn ${isLate}" onclick="updateStatus(${student.student_id}, 'late')">Muộn</button>
                    <button type="button" class="attendance-status-btn ${isAbsent}" onclick="updateStatus(${student.student_id}, 'absent')">Vắng</button>
                    <button type="button" class="attendance-status-btn ${isExcused}" onclick="updateStatus(${student.student_id}, 'excused')">Phép</button>
                </div>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span class="fw-bold" id="points-count-${student.student_id}">${student.interaction_points || 0}</span>
                    <button class="btn-counter" onclick="addInteractionPoint(${student.student_id})" title="Cộng 1 điểm phát biểu">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="text-end text-muted small">${recordedTime}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Cập nhật trạng thái điểm danh thủ công qua API
function updateStatus(studentId, status) {
    fetch(`${BASE_URL}/api/sessions/${SESSION_ID}/attendance`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            student_id: studentId,
            status: status
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            showToast('Đã cập nhật điểm danh thành công.', 'success');
            loadAttendanceList(); // Tải lại danh sách để đồng bộ trạng thái & thời gian
        } else {
            showToast(res.message || 'Lỗi cập nhật điểm danh.', 'danger');
        }
    })
    .catch(err => {
        console.error('Lỗi cập nhật điểm danh', err);
        showToast('Không thể kết nối máy chủ.', 'danger');
    });
}

// Cộng điểm tương tác phát biểu
function addInteractionPoint(studentId) {
    fetch(`${BASE_URL}/api/interactions`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            session_id: SESSION_ID,
            student_id: studentId,
            type: 'discussion',
            points_awarded: 1
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            showToast('Đã cộng điểm phát biểu thành công!', 'success');
            // Tăng số điểm hiển thị trên giao diện tức thì
            const span = document.getElementById(`points-count-${studentId}`);
            if (span) {
                span.textContent = parseInt(span.textContent) + 1;
            }
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        console.error('Lỗi cộng điểm tương tác', err);
        showToast('Lỗi máy chủ khi cộng điểm tương tác.', 'danger');
    });
}

// Chọn nhanh cả lớp (Bulk mark)
function bulkMark(status) {
    if (studentList.length === 0) return;
    
    if (confirm(`Bạn có chắc chắn muốn chuyển trạng thái điểm danh của tất cả sinh viên trong lớp thành "${status === 'present' ? 'Đi học' : 'Vắng mặt'}"?`)) {
        // Thực thi gọi API song song cho tất cả sinh viên
        const promises = studentList.map(s => {
            return fetch(`${BASE_URL}/api/sessions/${SESSION_ID}/attendance`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    student_id: s.student_id,
                    status: status
                })
            }).then(r => r.json());
        });

        Promise.all(promises)
            .then(results => {
                showToast('Đã cập nhật trạng thái cả lớp thành công.', 'success');
                loadAttendanceList();
            })
            .catch(err => {
                console.error('Lỗi cập nhật hàng loạt', err);
                showToast('Có lỗi xảy ra khi cập nhật hàng loạt.', 'danger');
            });
    }
}

// Mở buổi học (Kích hoạt Code điểm danh tự động)
function startSession() {
    fetch(`${BASE_URL}/api/sessions/${SESSION_ID}/start-attendance`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            method: 'Code',
            minutes: 15
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadSessionDetails();
            loadAttendanceList();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => console.error(err));
}

// Kết thúc buổi học (Đóng điểm danh, đánh dấu Completed)
function stopSession() {
    if (confirm('Bạn có chắc muốn đóng điểm danh và kết thúc buổi học này? Hệ thống sẽ tự động ghi nhận Vắng học cho các sinh viên chưa điểm danh.')) {
        fetch(`${BASE_URL}/api/sessions/${SESSION_ID}/stop-attendance`, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadSessionDetails();
                loadAttendanceList();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => console.error(err));
    }
}

// Helper: Định dạng ngày thành DD/MM/YYYY
function formatDateToDDMMYYYY(date) {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

// Helper: Định dạng thời gian hh:mm
function formatTimeOnly(date) {
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    return `${hh}:${mm}`;
}
