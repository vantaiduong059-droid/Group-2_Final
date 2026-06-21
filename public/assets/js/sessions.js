// public/assets/js/sessions.js

let currentDate = new Date(); // Ngày neo hiện tại để xác định tuần hiển thị
let sessionsList = [];       // Danh sách buổi học từ CSDL
let filterType = 'all';      // Bộ lọc: 'all', 'study', 'exam'
let currentSessionId = null; // ID buổi học đang được sửa đổi

// Bảng mapping tiết học -> giờ học cố định chuẩn của trường
const PERIOD_TIME_MAP = {
    1: { start: "07:00", end: "07:50" },
    2: { start: "07:50", end: "08:40" },
    3: { start: "09:00", end: "09:50" },
    4: { start: "09:50", end: "10:40" },
    5: { start: "10:40", end: "11:30" },
    6: { start: "13:00", end: "13:50" },
    7: { start: "13:50", end: "14:40" },
    8: { start: "15:00", end: "15:50" },
    9: { start: "15:50", end: "16:40" },
    10: { start: "16:40", end: "17:30" }
};

document.addEventListener('DOMContentLoaded', () => {
    // 1. Khởi tạo ngày mặc định trên Datepicker là hôm nay (theo múi giờ Việt Nam)
    const todayStr = formatDateToYYYYMMDD(currentDate);
    document.getElementById('scheduleDatePicker').value = todayStr;

    // 2. Đăng ký các sự kiện điều hướng & bộ lọc
    document.getElementById('scheduleDatePicker').addEventListener('change', (e) => {
        if(e.target.value) {
            currentDate = new Date(e.target.value);
            renderWeeklySchedule();
        }
    });

    const filterRadios = document.querySelectorAll('input[name="scheduleFilter"]');
    filterRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            filterType = e.target.value;
            renderWeeklySchedule();
        });
    });

    // 3. Đăng ký các sự kiện tự động tính giờ và check trùng sớm trên modal
    const periodInput = document.getElementById('sessionPeriod');
    if (periodInput) {
        periodInput.addEventListener('input', updateTimesFromPeriod);
    }

    const triggerFields = ['sessionDate', 'sessionStartTime', 'sessionEndTime', 'sessionRoom'];
    triggerFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', checkConflictEarly);
            el.addEventListener('input', checkConflictEarly);
        }
    });

    // Khởi tạo Flatpickr cho giờ bắt đầu và kết thúc (24h)
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#sessionStartTime", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            allowInput: true,
            minuteIncrement: 5, // step 5 phút cho mũi tên tăng/giảm gọn gàng
            static: true,       // Gán popup ngay dưới input để không bị lệch/tràn khỏi modal
            onChange: function() {
                checkConflictEarly();
            }
        });
        flatpickr("#sessionEndTime", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            allowInput: true,
            minuteIncrement: 5, // step 5 phút cho mũi tên tăng/giảm gọn gàng
            static: true,       // Gán popup ngay dưới input để không bị lệch/tràn khỏi modal
            onChange: function() {
                checkConflictEarly();
            }
        });

        // Bật tính năng cuộn chuột trên ô input (mỗi nấc cuộn tăng/giảm 5 phút)
        enableWheelOnTimeInput('sessionStartTime');
        enableWheelOnTimeInput('sessionEndTime');

        // Bật tự động nhận diện gõ số nhanh khi rời khỏi input (ví dụ: 0900 -> 09:00)
        enableAutoTimeFormat('sessionStartTime');
        enableAutoTimeFormat('sessionEndTime');
    }

    // 4. Load danh sách buổi học và khóa học dropdown
    loadSessions();
    loadCoursesDropdown();
});

// Tự động cập nhật giờ bắt đầu/kết thúc dựa trên Tiết học
function updateTimesFromPeriod() {
    const periodVal = document.getElementById('sessionPeriod').value.trim();
    if (!periodVal) return;
    
    const matches = periodVal.match(/\d+/g);
    if (matches && matches.length > 0) {
        const startPeriod = parseInt(matches[0]);
        const endPeriod = parseInt(matches[matches.length - 1]);
        
        if (PERIOD_TIME_MAP[startPeriod] && PERIOD_TIME_MAP[endPeriod]) {
            const startTime = PERIOD_TIME_MAP[startPeriod].start;
            const endTime = PERIOD_TIME_MAP[endPeriod].end;
            
            setTimePickerValue('sessionStartTime', startTime);
            setTimePickerValue('sessionEndTime', endTime);
            
            // Kích hoạt check trùng ca học sớm
            checkConflictEarly();
        }
    }
}

// Cảnh báo sớm trùng phòng + giờ + ngày học
function checkConflictEarly() {
    const date = document.getElementById('sessionDate').value;
    const start = document.getElementById('sessionStartTime').value;
    const end = document.getElementById('sessionEndTime').value;
    const room = document.getElementById('sessionRoom').value.trim();
    const alertBox = document.getElementById('conflictAlert');
    
    if (!alertBox) return;
    
    if (!date || !start || !end || !room) {
        alertBox.classList.add('d-none');
        alertBox.innerHTML = '';
        return;
    }
    
    if (start >= end) {
        alertBox.classList.remove('d-none');
        alertBox.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i>Giờ kết thúc phải lớn hơn giờ bắt đầu.';
        return;
    }
    
    let url = `${BASE_URL}/api/sessions/check-conflict?date=${date}&start=${start}&end=${end}&room=${encodeURIComponent(room)}`;
    if (currentSessionId) {
        url += `&exclude_id=${currentSessionId}`;
    }
    
    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'conflict') {
                alertBox.classList.remove('d-none');
                alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${res.message}`;
            } else {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }
        })
        .catch(err => {
            console.error('Lỗi kiểm tra trùng lịch sớm:', err);
        });
}

// Lấy danh sách buổi học từ API
function loadSessions() {
    fetch(`${BASE_URL}/api/sessions`)
        .then(response => response.json())
        .then(res => {
            if(res.status === 'success') {
                sessionsList = res.data;
                renderWeeklySchedule();
            } else {
                showToast('Không thể tải dữ liệu lịch học.', 'danger');
            }
        })
        .catch(err => {
            showToast('Lỗi kết nối máy chủ khi tải buổi học.', 'danger');
            console.error(err);
        });
}

// Lấy danh sách khóa học cho dropdown select
function loadCoursesDropdown() {
    fetch(`${BASE_URL}/api/courses`)
        .then(response => response.json())
        .then(res => {
            const select = document.getElementById('sessionCourseId');
            if(res.status === 'success') {
                // Xóa các option cũ trừ option đầu tiên
                select.innerHTML = '<option value="">-- Chọn khóa học --</option>';
                res.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = `${course.code} - ${course.name}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Lỗi tải danh sách khóa học', err));
}

// Lấy ngày thứ 2 của tuần chứa date
function getStartOfWeek(date) {
    const tempDate = new Date(date);
    const day = tempDate.getDay();
    // getDay() trả về 0 cho Chủ nhật, 1 cho Thứ 2, ..., 6 cho Thứ 7
    const diff = tempDate.getDate() - day + (day === 0 ? -6 : 1); 
    return new Date(tempDate.setDate(diff));
}

// Định dạng ngày Date thành YYYY-MM-DD
function formatDateToYYYYMMDD(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Định dạng ngày thành DD/MM/YYYY
function formatDateToDDMMYYYY(date) {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

// Render lịch học tuần
function renderWeeklySchedule() {
    const startOfWeek = getStartOfWeek(currentDate);
    
    // Đồng bộ lại Datepicker
    document.getElementById('scheduleDatePicker').value = formatDateToYYYYMMDD(currentDate);

    // Mảng chứa các chuỗi ngày YYYY-MM-DD từ thứ 2 đến chủ nhật (index 0 đến 6)
    const weekDaysStr = [];
    const dayTitles = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];

    // Cập nhật tiêu đề các cột ngày tháng
    for (let i = 0; i < 7; i++) {
        const nextDay = new Date(startOfWeek);
        nextDay.setDate(startOfWeek.getDate() + i);
        
        const nextDayStr = formatDateToYYYYMMDD(nextDay);
        weekDaysStr.push(nextDayStr);

        const colId = `col-day${i + 2}`;
        const headerCell = document.getElementById(colId);
        if(headerCell) {
            headerCell.innerHTML = `${dayTitles[i]}<br><small class="text-white-50">${formatDateToDDMMYYYY(nextDay)}</small>`;
        }
    }

    // Xóa trắng tất cả các ô buổi học
    for (let dayIndex = 2; dayIndex <= 8; dayIndex++) {
        const morningCell = document.getElementById(`cell-morning-${dayIndex}`);
        const afternoonCell = document.getElementById(`cell-afternoon-${dayIndex}`);
        if(morningCell) morningCell.innerHTML = '';
        if(afternoonCell) afternoonCell.innerHTML = '';
    }

    // Phân bổ danh sách buổi học vào các ô
    sessionsList.forEach(s => {
        const sDate = s.session_date; // Dạng YYYY-MM-DD
        const dayIndex = weekDaysStr.indexOf(sDate);
        
        if (dayIndex !== -1) {
            // Buổi học nằm trong tuần đang xem
            const colNum = dayIndex + 2; // Thứ 2 có id kết thúc là 2, Thứ 3 là 3, v.v.

            // Phân biệt Ca: Sáng bắt đầu trước 12:00:00, Chiều bắt đầu từ 12:00:00 trở đi
            const isMorning = s.start_time < "12:00:00";
            const targetCellId = isMorning ? `cell-morning-${colNum}` : `cell-afternoon-${colNum}`;
            const targetCell = document.getElementById(targetCellId);

            if (targetCell) {
                // Xác định xem đây là Lịch học hay Lịch thi dựa vào note hoặc course_name
                const hasExamKeyword = (s.note && (s.note.toLowerCase().includes('thi') || s.note.toLowerCase().includes('exam') || s.note.toLowerCase().includes('kiểm tra'))) || 
                                      (s.course_name && s.course_name.toLowerCase().includes('thi học kỳ'));
                
                // Lọc theo bộ lọc
                if (filterType === 'study' && hasExamKeyword) return;
                if (filterType === 'exam' && !hasExamKeyword) return;

                const cardClass = hasExamKeyword ? 'session-card-exam' : 'session-card-normal';

                // Tạo thẻ buổi học
                const card = document.createElement('div');
                card.className = `session-item-card ${cardClass}`;
                
                // Xác định hình thức điểm danh hiện tại của buổi học
                let methodText = '<span class="text-muted">Chưa mở</span>';
                if (s.attendance_code) {
                    methodText = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5">Mã Code: ${s.attendance_code}</span>`;
                } else if (s.qr_token) {
                    methodText = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5">Quét QR</span>`;
                } else if (s.status === 'completed') {
                    methodText = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Hoàn thành</span>`;
                }

                // Tạo nội dung HTML
                let noteHtml = s.note ? `<div class="session-note-box text-truncate" title="${s.note}"><i class="bi bi-info-circle me-1"></i>Ghi chú: ${s.note}</div>` : '';
                
                card.innerHTML = `
                    <div class="session-course-title" title="${s.course_name}">${s.course_name}</div>
                    <div class="session-meta-line fw-semibold small text-muted">
                        ${s.course_code} - ${s.course_class_code || 'N/A'}
                    </div>
                    <div class="session-meta-line mt-1">
                        <i class="bi bi-hash"></i><span>Tiết: ${s.period || '1 - 3'}</span>
                    </div>
                    <div class="session-meta-line">
                        <i class="bi bi-clock"></i><span>Giờ: ${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)}</span>
                    </div>
                    <div class="session-meta-line">
                        <i class="bi bi-geo-alt"></i><span class="text-truncate" title="${s.room || 'Phòng học 102'}">${s.room || 'Phòng học 102'}</span>
                    </div>
                    <div class="session-meta-line">
                        <i class="bi bi-person-badge"></i><span>GV: ${s.teacher_name || 'Chưa phân công'} (${s.teacher_email || 'N/A'})</span>
                    </div>
                    <div class="session-meta-line mt-1.5">
                        <i class="bi bi-check2-square"></i><span>Điểm danh: ${methodText}</span>
                    </div>
                    ${noteHtml}
                    
                    <!-- Nút thao tác nhanh dành cho Admin -->
                    <div class="session-card-actions">
                        <a class="btn-card-action attendance text-decoration-none" href="${BASE_URL}/admin/sessions/${s.id}/attendance" onclick="event.stopPropagation();" title="Điểm danh">
                            <i class="bi bi-calendar-check text-success"></i>
                        </a>
                        <button class="btn-card-action edit" title="Sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-card-action delete" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;

                // Bấm vào card thì mở modal chỉnh sửa
                card.addEventListener('click', () => {
                    handleEditClick(s);
                });

                // Đăng ký sự kiện click cho các nút hành động của card để tránh lỗi cú pháp JSON
                const btnEdit = card.querySelector('.btn-card-action.edit');
                if (btnEdit) {
                    btnEdit.addEventListener('click', (e) => {
                        e.stopPropagation();
                        handleEditClick(s);
                    });
                }

                const btnDelete = card.querySelector('.btn-card-action.delete');
                if (btnDelete) {
                    btnDelete.addEventListener('click', (e) => {
                        e.stopPropagation();
                        deleteSession(s.id);
                    });
                }

                targetCell.appendChild(card);
            }
        }
    });

    // Nếu các ô trống hoàn toàn, có thể hiển thị nền caro mặc định (đã làm bằng CSS)
}

// Đi tới tuần chứa ngày hiện tại thực tế
function goToCurrentWeek() {
    currentDate = new Date();
    renderWeeklySchedule();
}

// Tiến / Lùi tuần
function navigateWeek(offset) {
    currentDate.setDate(currentDate.getDate() + (offset * 7));
    renderWeeklySchedule();
}

// In lịch học
function printSchedule() {
    const printContent = document.getElementById('printArea').innerHTML;
    const originalContent = document.body.innerHTML;
    
    // Tạo một cửa sổ in mới để in sạch đẹp hơn
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>In Lịch Học, Lịch Thi Theo Tuần</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
            <style>
                body { font-family: 'Inter', sans-serif; padding: 20px; background: #ffffff; }
                .schedule-table { border-collapse: collapse; width: 100%; border: 1px solid #cbd5e1; }
                .schedule-table th { background: #0284c7 !important; color: white !important; font-weight: bold; text-align: center; padding: 10px; border: 1px solid #0369a1; }
                .schedule-table td { border: 1px solid #cbd5e1; vertical-align: top; padding: 8px; }
                .shift-col { background: #f8fafc !important; font-weight: bold; color: #0369a1; text-align: center; vertical-align: middle !important; width: 60px; }
                .session-item-card { border-radius: 6px; padding: 10px; margin-bottom: 8px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-left: 5px solid #2563eb; background: #eff6ff; }
                .session-card-exam { border-left-color: #eab308; background: #fef9c3; }
                .session-course-title { font-weight: bold; font-size: 0.85rem; margin-bottom: 4px; }
                .session-meta-line { margin-bottom: 2px; }
                .session-card-actions { display: none !important; } /* Ẩn nút thao tác khi in */
                .session-note-box { margin-top: 4px; padding-top: 4px; border-top: 1px dashed rgba(0,0,0,0.1); font-style: italic; }
            </style>
        </head>
        <body>
            <h2 class="text-center fw-bold mb-4">Lịch học, lịch thi theo tuần</h2>
            ${printContent}
            <script>
                window.onload = function() {
                    window.print();
                    window.close();
                }
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Mở modal tạo mới
function openSessionModal() {
    currentSessionId = null;
    document.getElementById('sessionModalTitle').innerText = 'Tạo buổi học mới';
    document.getElementById('sessionForm').reset();
    
    clearTimePicker('sessionStartTime');
    clearTimePicker('sessionEndTime');

    // Gán ngày mặc định trong modal là ngày đang chọn trên lịch
    document.getElementById('sessionDate').value = formatDateToYYYYMMDD(currentDate);
    document.getElementById('sessionRoom').value = 'Phòng học 102, số 1 Phan Tây Nhạc';
    document.getElementById('sessionPeriod').value = '1 - 3';
    
    // Tự động tính giờ bắt đầu/kết thúc cho tiết mặc định
    updateTimesFromPeriod();

    const alertBox = document.getElementById('conflictAlert');
    if (alertBox) {
        alertBox.classList.add('d-none');
        alertBox.innerHTML = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('sessionModal'));
    modal.show();
}

// Mở modal chỉnh sửa từ sự kiện click card
function handleEditClick(s) {
    currentSessionId = s.id;
    document.getElementById('sessionModalTitle').innerText = 'Chỉnh sửa buổi học';
    
    document.getElementById('sessionCourseId').value = s.course_id;
    document.getElementById('sessionDate').value = s.session_date;
    setTimePickerValue('sessionStartTime', s.start_time);
    setTimePickerValue('sessionEndTime', s.end_time);
    document.getElementById('sessionRoom').value = s.room || '';
    document.getElementById('sessionPeriod').value = s.period || '';
    document.getElementById('sessionNote').value = s.note || '';
    document.getElementById('sessionStatus').value = s.status;

    const alertBox = document.getElementById('conflictAlert');
    if (alertBox) {
        alertBox.classList.add('d-none');
        alertBox.innerHTML = '';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('sessionModal'));
    modal.show();
}

// Tạo / Cập nhật buổi học
function saveSession() {
    const data = {
        course_id: document.getElementById('sessionCourseId').value,
        session_date: document.getElementById('sessionDate').value,
        start_time: document.getElementById('sessionStartTime').value,
        end_time: document.getElementById('sessionEndTime').value,
        room: document.getElementById('sessionRoom').value.trim(),
        period: document.getElementById('sessionPeriod').value.trim(),
        note: document.getElementById('sessionNote').value.trim(),
        status: document.getElementById('sessionStatus').value
    };

    if(!data.course_id || !data.session_date || !data.start_time || !data.end_time || !data.room || !data.period) {
        showToast('Vui lòng điền đủ các thông tin bắt buộc', 'warning');
        return;
    }

    if(data.start_time >= data.end_time) {
        showToast('Giờ kết thúc phải lớn hơn giờ bắt đầu', 'warning');
        return;
    }

    // Kiểm tra nếu đang có thông báo trùng lịch hiển thị thì ngăn cản submit
    const alertBox = document.getElementById('conflictAlert');
    if (alertBox && !alertBox.classList.contains('d-none') && alertBox.innerText.includes('đã có buổi học')) {
        showToast('Không thể lưu do bị trùng lịch học. Vui lòng chọn phòng hoặc giờ khác!', 'danger');
        return;
    }

    const url = currentSessionId ? `${BASE_URL}/api/sessions/${currentSessionId}` : `${BASE_URL}/api/sessions`;
    const method = currentSessionId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            showToast(res.message, 'success');
            // Hide modal
            const modalEl = document.getElementById('sessionModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if(modalInstance) modalInstance.hide();
            
            // Reload
            loadSessions();
        } else {
            showToast(res.message, 'danger');
            // Nếu lưu thất bại do backend trả về lỗi trùng lịch, hiển thị lên alertBox
            if (alertBox && res.message) {
                alertBox.classList.remove('d-none');
                alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${res.message}`;
            }
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra khi lưu thông tin.', 'danger');
        console.error(err);
    });
}

// Xóa buổi học
function deleteSession(id) {
    if(confirm('Bạn có chắc chắn muốn xóa buổi học này? Điểm danh và tương tác liên quan sẽ bị xóa vĩnh viễn!')) {
        fetch(`${BASE_URL}/api/sessions/${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                loadSessions();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => {
            showToast('Có lỗi xảy ra khi xóa buổi học.', 'danger');
            console.error(err);
        });
    }
}

// Toggle chế độ thu nhỏ / phóng to kích thước của bảng lịch học
function toggleCompactSchedule() {
    const table = document.getElementById('scheduleTable');
    const btn = document.getElementById('btnCompactToggle');
    const textSpan = document.getElementById('textCompactToggle');
    const icon = btn.querySelector('i');
    
    if (table.classList.contains('schedule-compact')) {
        table.classList.remove('schedule-compact');
        if(icon) {
            icon.className = 'bi bi-arrows-angle-contract';
        }
        if(textSpan) {
            textSpan.textContent = 'Thu nhỏ';
        }
        showToast('Đã quay về kích thước bình thường', 'success');
    } else {
        table.classList.add('schedule-compact');
        if(icon) {
            icon.className = 'bi bi-arrows-angle-expand';
        }
        if(textSpan) {
            textSpan.textContent = 'Mở rộng';
        }
        showToast('Đã chuyển sang chế độ thu nhỏ bảng', 'success');
    }
}

// Toggle chế độ toàn màn hình cho bảng lịch học
function toggleFullscreenSchedule() {
    const wrapper = document.getElementById('scheduleWrapper');
    const btn = document.getElementById('btnFullscreenToggle');
    
    if (wrapper.classList.contains('schedule-fullscreen-mode')) {
        wrapper.classList.remove('schedule-fullscreen-mode');
        btn.innerHTML = '<i class="bi bi-fullscreen"></i>';
        showToast('Đã thoát chế độ toàn màn hình', 'success');
    } else {
        wrapper.classList.add('schedule-fullscreen-mode');
        btn.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
        showToast('Đã mở lịch học toàn màn hình', 'success');
    }
}

// Hàm helper để gán giá trị thời gian cho Flatpickr
function setTimePickerValue(id, val) {
    const el = document.getElementById(id);
    if (el && el._flatpickr) {
        el._flatpickr.setDate(val);
    } else if (el) {
        el.value = val;
    }
}

// Hàm helper để xóa giá trị thời gian cho Flatpickr
function clearTimePicker(id) {
    const el = document.getElementById(id);
    if (el && el._flatpickr) {
        el._flatpickr.clear();
    } else if (el) {
        el.value = '';
    }
}

// Cho phép lăn chuột trực tiếp trên input để thay đổi thời gian (nửa trái đổi giờ, nửa phải đổi phút)
function enableWheelOnTimeInput(id) {
    const el = document.getElementById(id);
    if (!el) return;
    
    el.addEventListener('wheel', function(e) {
        if (!el._flatpickr) return;
        e.preventDefault();
        
        const direction = e.deltaY < 0 ? 1 : -1;
        const selectedDates = el._flatpickr.selectedDates;
        let currentDate = selectedDates[0];
        
        // Nếu chưa chọn ngày/giờ nào, khởi tạo theo giờ mặc định của input hoặc giờ hiện tại
        if (!currentDate) {
            const val = el.value || "07:00";
            const parts = val.split(':');
            currentDate = new Date();
            currentDate.setHours(parseInt(parts[0]) || 0);
            currentDate.setMinutes(parseInt(parts[1]) || 0);
        }
        
        const rect = el.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const isHour = mouseX < (rect.width / 2);
        
        if (isHour) {
            const nextHours = (currentDate.getHours() + direction + 24) % 24;
            currentDate.setHours(nextHours);
        } else {
            // Cuộn tăng/giảm theo step 5 phút cho hợp lý
            let currentMinutes = currentDate.getMinutes();
            // Làm tròn về bội số của 5 trước khi tăng/giảm để tránh số lẻ khi lăn
            currentMinutes = Math.round(currentMinutes / 5) * 5;
            const nextMinutes = (currentMinutes + direction * 5 + 60) % 60;
            currentDate.setMinutes(nextMinutes);
        }
        
        // Cập nhật giá trị vào flatpickr và kích hoạt sự kiện thay đổi
        el._flatpickr.setDate(currentDate, true);
    });
}

// Tự động nhận diện định dạng giờ nhanh khi người dùng gõ tay (ví dụ: "0900" -> "09:00", "900" -> "09:00")
function enableAutoTimeFormat(id) {
    const el = document.getElementById(id);
    if (!el) return;
    
    el.addEventListener('blur', function() {
        let val = el.value.trim().replace(/\D/g, ''); // Lấy mọi ký tự số
        
        if (val.length === 3) {
            val = '0' + val.substring(0, 1) + ':' + val.substring(1, 3);
        } else if (val.length === 4) {
            val = val.substring(0, 2) + ':' + val.substring(2, 4);
        }
        
        // Kiểm tra tính hợp lệ của HH:MM
        if (val.length === 5 && val.includes(':')) {
            const parts = val.split(':');
            const h = parseInt(parts[0]);
            const m = parseInt(parts[1]);
            
            if (h >= 0 && h < 24 && m >= 0 && m < 60) {
                if (el._flatpickr) {
                    el._flatpickr.setDate(val, true); // Gán cho flatpickr và kích hoạt sự kiện change
                } else {
                    el.value = val;
                    checkConflictEarly();
                }
            }
        }
    });
}
