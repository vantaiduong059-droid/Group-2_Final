// public/assets/js/courses.js

let currentCourseId = null;
let coursesList = [];
let currentView = localStorage.getItem('courseView') || 'grid'; // Lưu trạng thái hiển thị trong localStorage

document.addEventListener('DOMContentLoaded', () => {
    loadCourses();
    loadTeachersDropdown();
    
    // Đồng bộ trạng thái active của nút view
    syncViewButtons();
});

// Đồng bộ class active cho các nút chuyển view
function syncViewButtons() {
    const btnGrid = document.getElementById('btnGridView');
    const btnList = document.getElementById('btnListView');
    if(!btnGrid || !btnList) return;

    if(currentView === 'grid') {
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    } else {
        btnGrid.classList.remove('active');
        btnList.classList.add('active');
    }
}

// Chuyển đổi qua lại giữa Grid và List
function setCourseView(viewType) {
    currentView = viewType;
    localStorage.setItem('courseView', viewType);
    syncViewButtons();
    renderCourses();
}

// R - Lấy danh sách Khóa học từ API CSDL gốc
function loadCourses() {
    fetch(`${BASE_URL}/api/courses`)
        .then(response => response.json())
        .then(res => {
            if(res.status === 'success') {
                coursesList = res.data;
                
                // Tự động lọc nếu có từ khóa search trên URL
                const urlParams = new URLSearchParams(window.location.search);
                const searchKeyword = urlParams.get('search');
                if (searchKeyword) {
                    coursesList = coursesList.filter(c => 
                        c.code.toLowerCase().includes(searchKeyword.toLowerCase()) || 
                        c.name.toLowerCase().includes(searchKeyword.toLowerCase())
                    );
                }
                
                renderCourses();
            } else {
                showToast('Không thể tải dữ liệu lớp học phần', 'danger');
            }
        })
        .catch(err => {
            showToast('Lỗi tải dữ liệu', 'danger');
            console.error(err);
        });
}

// Mảng màu gradient pastel cực sang trọng cho Card môn học
const pastelGradients = [
    'linear-gradient(135deg, #3b82f6, #1d4ed8)', // Xanh dương
    'linear-gradient(135deg, #10b981, #047857)', // Xanh lá
    'linear-gradient(135deg, #8b5cf6, #6d28d9)', // Tím
    'linear-gradient(135deg, #f59e0b, #d97706)', // Cam/Vàng
    'linear-gradient(135deg, #ec4899, #be185d)', // Hồng
    'linear-gradient(135deg, #06b6d4, #0891b2)', // Cyan
    'linear-gradient(135deg, #f43f5e, #e11d48)'  // Đỏ
];

// Hàm lấy màu ngẫu nhiên nhưng cố định dựa trên ID môn học
function getGradientById(id) {
    const index = id % pastelGradients.length;
    return pastelGradients[index];
}

// Hàm lấy mã màu đơn sắc mờ cho avatar giảng viên
const avatarColors = [
    '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#f43f5e'
];
function getAvatarColorById(id) {
    const index = id % avatarColors.length;
    return avatarColors[index];
}

// Render dữ liệu lên màn hình theo trạng thái view
function renderCourses() {
    const container = document.getElementById('courseContainer');
    if(!container) return;
    container.innerHTML = '';

    if (coursesList.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-folder-x fs-1 d-block mb-2"></i>Không có dữ liệu lớp học phần.</div>';
        return;
    }

    if (currentView === 'grid') {
        // 1. RENDER DẠNG LƯỚI (GRID VIEW) - Cực kỳ hiện đại
        const row = document.createElement('div');
        row.className = 'row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4';
        
        coursesList.forEach(c => {
            const col = document.createElement('div');
            col.className = 'col';
            
            const gradient = getGradientById(c.id);
            const avatarBg = getAvatarColorById(c.teacher_id || c.id);
            const initialLetter = c.teacher_name ? c.teacher_name.charAt(0) : '?';
            
            col.innerHTML = `
                <div class="course-card">
                    <!-- Header màu gradient pastel -->
                    <div class="course-card-header" style="background: ${gradient};">
                        <span class="course-card-code" title="Mã môn học">${c.code}</span>
                        <span class="course-card-class-code" title="Mã lớp học phần">${c.class_code}</span>
                    </div>
                    
                    <!-- Body thẻ -->
                    <div class="course-card-body">
                        <h4 class="course-title" title="${c.name}">${c.name}</h4>
                        <p class="course-desc" title="${c.description || ''}">${c.description || 'Chưa có mô tả ngắn nào cho môn học này.'}</p>
                        
                        <div class="course-info-row">
                            <div class="course-info-item">
                                <i class="bi bi-card-checklist"></i>
                                <span>${c.credits} Tín chỉ</span>
                            </div>
                            <div class="course-info-item">
                                <i class="bi bi-clock-history"></i>
                                <span>${c.periods || (c.credits * 15)} Tiết</span>
                            </div>
                        </div>
                        
                        <!-- Giảng viên phụ trách -->
                        <div class="course-teacher">
                            <div class="teacher-avatar-mini" style="background: ${avatarBg};">
                                ${initialLetter}
                            </div>
                            <div>
                                <div class="text-muted small" style="font-size: 0.72rem;">Giảng viên</div>
                                <span class="fw-bold text-dark">${c.teacher_name || 'Chưa gán'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dưới đáy Card: Các thao tác -->
                    <div class="course-card-actions">
                        <button class="btn btn-sm btn-link text-primary fw-bold text-decoration-none p-0 d-flex align-items-center gap-1" onclick="showCourseDetails(${c.id})">
                            <i class="bi bi-info-circle"></i> Chi tiết lớp
                        </button>
                        <div>
                            <button class="btn btn-sm btn-light text-primary me-1" onclick='openEditModal(${JSON.stringify(c).replace(/"/g, '&quot;')})' title="Chỉnh sửa">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteCourse(${c.id})" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            row.appendChild(col);
        });
        container.appendChild(row);
    } else {
        // 2. RENDER DẠNG BẢNG (LIST VIEW) - Thiết kế tinh tế
        const cardModern = document.createElement('div');
        cardModern.className = 'card-modern';
        
        const tableResponsive = document.createElement('div');
        tableResponsive.className = 'table-responsive';
        
        tableResponsive.innerHTML = `
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="ps-4 py-3" style="width: 60px;">STT</th>
                        <th class="py-3">Mã HP</th>
                        <th class="py-3">Mã Lớp HP</th>
                        <th class="py-3">Tên lớp học phần</th>
                        <th class="py-3" style="width: 100px;">Tín chỉ</th>
                        <th class="py-3" style="width: 100px;">Số tiết</th>
                        <th class="py-3">Giảng viên giảng dạy</th>
                        <th class="py-3 text-end pe-4" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    ${coursesList.map((c, index) => `
                        <tr>
                            <td class="ps-4 fw-medium text-muted">${index + 1}</td>
                            <td><span class="badge bg-light text-dark fw-bold border" style="font-size: 0.8rem;">${c.code}</span></td>
                            <td><span class="text-primary fw-bold">${c.class_code}</span></td>
                            <td>
                                <a href="#" onclick="showCourseDetails(${c.id}); return false;" class="text-decoration-none fw-bold text-dark hover-primary">${c.name}</a>
                            </td>
                            <td><span class="badge-tc">${c.credits} TC</span></td>
                            <td class="fw-semibold text-secondary">${c.periods || (c.credits * 15)} tiết</td>
                            <td>
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-semibold"><i class="bi bi-person-badge text-primary me-1"></i>${c.teacher_name || 'N/A'}</span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary me-1" onclick='openEditModal(${JSON.stringify(c).replace(/"/g, '&quot;')})' title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="deleteCourse(${c.id})" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        cardModern.appendChild(tableResponsive);
        container.appendChild(cardModern);
    }
}

// Tải danh sách giảng viên đổ vào select input
function loadTeachersDropdown() {
    fetch(`${BASE_URL}/api/teachers`)
        .then(response => response.json())
        .then(res => {
            const select = document.getElementById('teacherId');
            if(res.status === 'success') {
                // Giữ lại option đầu tiên
                select.innerHTML = '<option value="">-- Chọn giảng viên --</option>';
                res.data.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.full_name;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Lỗi tải danh sách giảng viên', err));
}

// Mở ca học cố định hàng tuần (động)
function addScheduleRow(data = null) {
    const container = document.getElementById('scheduleRowsContainer');
    if(!container) return;
    
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center schedule-row';
    
    const selectedDay = data ? data.day_of_week : 2;
    const startTime = data ? data.start_time.substring(0, 5) : '08:00';
    const endTime = data ? data.end_time.substring(0, 5) : '10:00';
    const room = data ? data.room : '';
    
    row.innerHTML = `
        <div class="col-md-3">
            <select class="form-select form-select-sm schedule-day" required>
                <option value="2" ${selectedDay == 2 ? 'selected' : ''}>Thứ hai</option>
                <option value="3" ${selectedDay == 3 ? 'selected' : ''}>Thứ ba</option>
                <option value="4" ${selectedDay == 4 ? 'selected' : ''}>Thứ tư</option>
                <option value="5" ${selectedDay == 5 ? 'selected' : ''}>Thứ năm</option>
                <option value="6" ${selectedDay == 6 ? 'selected' : ''}>Thứ sáu</option>
                <option value="7" ${selectedDay == 7 ? 'selected' : ''}>Thứ bảy</option>
                <option value="8" ${selectedDay == 8 ? 'selected' : ''}>Chủ nhật</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="time" class="form-control form-control-sm schedule-start" value="${startTime}" required>
        </div>
        <div class="col-md-2">
            <input type="time" class="form-control form-control-sm schedule-end" value="${endTime}" required>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm schedule-room" value="${room}" placeholder="Phòng học, vd: PM 101" required>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="this.closest('.schedule-row').remove()" title="Xóa ca học">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

// Mở modal tạo mới
function openCreateModal() {
    currentCourseId = null;
    document.getElementById('modalTitle').innerText = 'Thêm lớp học phần mới';
    document.getElementById('courseForm').reset();
    document.getElementById('courseCredits').value = 3;
    document.getElementById('coursePeriods').value = 45;
    document.getElementById('totalSessions').value = 15;
    
    const container = document.getElementById('scheduleRowsContainer');
    if(container) container.innerHTML = '';
    addScheduleRow(); // Mặc định có 1 ca học trống
    
    const modal = new bootstrap.Modal(document.getElementById('courseModal'));
    modal.show();
}

// Tính số tiết & số buổi tự động dựa trên tín chỉ
function updateCreditsAndSessions() {
    const credits = parseInt(document.getElementById('courseCredits').value) || 0;
    document.getElementById('coursePeriods').value = credits * 15;
    document.getElementById('totalSessions').value = credits * 5;
}

// Mở modal chỉnh sửa và lấy lịch từ API
function openEditModal(course) {
    currentCourseId = course.id;
    document.getElementById('modalTitle').innerText = 'Chỉnh sửa lớp học phần';
    
    document.getElementById('courseCode').value = course.code;
    document.getElementById('classCode').value = course.class_code;
    document.getElementById('courseCredits').value = course.credits;
    document.getElementById('coursePeriods').value = course.periods;
    document.getElementById('totalSessions').value = course.total_sessions || 15;
    document.getElementById('courseName').value = course.name;
    document.getElementById('courseDesc').value = course.description || '';
    document.getElementById('teacherId').value = course.teacher_id || '';
    
    const container = document.getElementById('scheduleRowsContainer');
    if(container) container.innerHTML = '';
    
    // Nạp lịch từ API để chắc chắn đầy đủ
    fetch(`${BASE_URL}/api/courses/${course.id}`)
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const c = res.data;
                if(c.schedules && c.schedules.length > 0) {
                    c.schedules.forEach(sc => addScheduleRow(sc));
                } else {
                    addScheduleRow();
                }
            } else {
                addScheduleRow();
            }
        })
        .catch(err => {
            console.error(err);
            addScheduleRow();
        });
        
    const modal = new bootstrap.Modal(document.getElementById('courseModal'));
    modal.show();
}

// C/U - Lưu khóa học kèm lịch học cố định
function saveCourse() {
    const schedules = [];
    const rows = document.querySelectorAll('.schedule-row');
    let valid = true;
    
    rows.forEach(row => {
        const day = row.querySelector('.schedule-day').value;
        const start = row.querySelector('.schedule-start').value;
        const end = row.querySelector('.schedule-end').value;
        const room = row.querySelector('.schedule-room').value.trim();
        
        if (!day || !start || !end || !room) {
            valid = false;
            return;
        }
        
        if (start >= end) {
            valid = false;
            showToast('Giờ kết thúc ca học phải lớn hơn giờ bắt đầu', 'warning');
            return;
        }
        
        schedules.push({
            day_of_week: parseInt(day),
            start_time: start + ':00',
            end_time: end + ':00',
            room: room
        });
    });
    
    if (!valid) {
        if (schedules.length === 0 && rows.length > 0) {
            showToast('Vui lòng điền đủ thông tin cho các ca học', 'warning');
        }
        return;
    }
    
    if (schedules.length === 0) {
        showToast('Vui lòng thêm ít nhất một ca học cố định', 'warning');
        return;
    }

    const data = {
        code: document.getElementById('courseCode').value.trim(),
        class_code: document.getElementById('classCode').value.trim(),
        credits: parseInt(document.getElementById('courseCredits').value),
        periods: parseInt(document.getElementById('coursePeriods').value),
        total_sessions: parseInt(document.getElementById('totalSessions').value),
        name: document.getElementById('courseName').value.trim(),
        description: document.getElementById('courseDesc').value.trim(),
        teacher_id: document.getElementById('teacherId').value,
        schedules: schedules
    };

    if(!data.code || !data.class_code || !data.name || !data.credits || !data.total_sessions || !data.teacher_id) {
        showToast('Vui lòng điền đầy đủ các trường bắt buộc', 'warning');
        return;
    }

    const url = currentCourseId ? `${BASE_URL}/api/courses/${currentCourseId}` : `${BASE_URL}/api/courses`;
    const method = currentCourseId ? 'PUT' : 'POST';

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
            bootstrap.Modal.getInstance(document.getElementById('courseModal')).hide();
            loadCourses();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra khi lưu thông tin.', 'danger');
        console.error(err);
    });
}

// D - Xóa khóa học
function deleteCourse(id) {
    if(confirm('Bạn có chắc chắn muốn xóa lớp học phần này? Mọi dữ liệu liên kết như sinh viên, điểm danh và tương tác sẽ bị ảnh hưởng!')) {
        fetch(`${BASE_URL}/api/courses/${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                loadCourses();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => {
            showToast('Có lỗi xảy ra', 'danger');
        });
    }
}

// Xem chi tiết lớp học phần kèm quản lý danh sách sinh viên trực quan (2 cột)
function showCourseDetails(courseId) {
    const modal = new bootstrap.Modal(document.getElementById('courseDetailsModal'));
    modal.show();
    const body = document.getElementById('courseDetailsBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>Đang tải thông tin lớp học phần...</div>';
    
    fetch(`${BASE_URL}/api/courses/${courseId}`)
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const c = res.data;
                
                body.innerHTML = `
                    <div class="row g-4">
                        <!-- CỘT 1: THÔNG TIN HỌC PHẦN -->
                        <div class="col-lg-5 border-end pe-4">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Thông tin học phần</h5>
                            
                            <div class="mb-3">
                                <label class="text-secondary small fw-semibold d-block mb-1">Tên lớp học phần</label>
                                <div class="fw-bold text-dark" style="line-height:1.35; font-size:1.1rem;">${c.name}</div>
                            </div>
                            
                            <div class="row mb-3 bg-light p-2.5 rounded border g-0">
                                <div class="col-6 ps-2">
                                    <label class="text-muted small d-block mb-0.5">Mã môn học</label>
                                    <span class="badge bg-dark fw-bold">${c.code}</span>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small d-block mb-0.5">Mã Lớp HP</label>
                                    <span class="badge bg-primary fw-bold">${c.class_code}</span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="text-secondary small d-block mb-1">Số tín chỉ</label>
                                    <div class="fw-bold"><i class="bi bi-card-checklist text-success me-1"></i>${c.credits} tín chỉ</div>
                                </div>
                                <div class="col-6">
                                    <label class="text-secondary small d-block mb-1">Thời lượng tiết</label>
                                    <div class="fw-bold"><i class="bi bi-clock-history text-danger me-1"></i>${c.periods} tiết học</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="text-secondary small fw-semibold d-block mb-1">Giảng viên đảm nhiệm</label>
                                <div class="fw-bold text-dark"><i class="bi bi-person-badge text-primary me-1.5"></i>${c.teacher_name || 'Chưa phân công'}</div>
                            </div>
                            
                            <div class="mb-0">
                                <label class="text-secondary small d-block mb-1">Mô tả tóm tắt</label>
                                <div class="text-muted small bg-light p-3 rounded" style="max-height:120px; overflow-y:auto; line-height:1.45;">
                                    ${c.description || 'Chưa có mô tả cho học phần này.'}
                                </div>
                            </div>
                        </div>
                        
                        <!-- CỘT 2: QUẢN LÝ THÀNH VIÊN LỚP HỌC PHẦN -->
                        <div class="col-lg-7 ps-4">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-people-fill text-success me-2"></i>Danh sách sinh viên theo học</span>
                                <span class="badge bg-success-subtle text-success fs-6" id="detailStudentCountBadge">0</span>
                            </h5>
                            
                            <!-- Bảng danh sách sinh viên của môn -->
                            <div class="table-responsive mb-3" style="max-height: 250px; border-radius: 8px;">
                                <table class="table table-sm table-hover table-striped align-middle border mb-0" style="font-size:0.875rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-2">Họ và tên</th>
                                            <th class="py-2">Email</th>
                                            <th class="py-2 text-end pe-3" style="width: 80px;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="courseStudentsTableBody">
                                        <tr><td colspan="3" class="text-center py-3 text-muted">Đang tải danh sách sinh viên...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Form thêm sinh viên mới vào lớp -->
                            <div class="p-3 bg-light rounded border">
                                <h6 class="fw-bold text-dark mb-2" style="font-size:0.9rem;"><i class="bi bi-person-plus text-primary me-2"></i>Thêm sinh viên mới vào lớp học phần</h6>
                                <div class="row g-2">
                                    <div class="col-sm-9">
                                        <select class="form-select form-select-sm" id="enrollStudentSelect">
                                            <option value="">-- Chọn sinh viên --</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <button class="btn btn-sm btn-primary w-100" onclick="enrollStudent(${c.id})" style="background:#0284c7; border-color:#0284c7;">Thêm vào</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Tải sinh viên thuộc lớp
                loadCourseStudents(c.id);
            } else {
                body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>${res.message}</div>`;
            }
        })
        .catch(err => {
            body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-wifi-off me-2"></i>Lỗi kết nối máy chủ không thể lấy chi tiết.</div>`;
            console.error(err);
        });
}

// Lấy danh sách SV của lớp và SV rảnh hệ thống
function loadCourseStudents(courseId) {
    // 1. Tải SV thuộc lớp học phần
    fetch(`${BASE_URL}/api/courses/${courseId}/students`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('courseStudentsTableBody');
            const badge = document.getElementById('detailStudentCountBadge');
            tbody.innerHTML = '';
            
            if(res.status === 'success') {
                const students = res.data;
                badge.innerText = students.length;
                
                if(students.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">Chưa có sinh viên nào trong lớp này.</td></tr>';
                    loadUnenrolledStudents(courseId, []);
                    return;
                }
                
                students.forEach(s => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-3 fw-medium">${s.full_name}</td>
                        <td class="text-muted">${s.email}</td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-link text-danger p-0 border-0" onclick="unenrollStudent(${courseId}, ${s.id})" title="Xóa khỏi lớp">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                
                // 2. Tải SV chưa tham gia lớp để đổ vào Select dropdown
                loadUnenrolledStudents(courseId, students);
            }
        });
}

// Lấy danh sách SV rảnh (chưa học lớp này)
function loadUnenrolledStudents(courseId, enrolledStudents) {
    fetch(`${BASE_URL}/api/students`)
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const allStudents = res.data;
                const select = document.getElementById('enrollStudentSelect');
                select.innerHTML = '<option value="">-- Chọn sinh viên --</option>';
                
                // Lọc những sinh viên chưa đăng ký lớp này
                const unenrolled = allStudents.filter(s => !enrolledStudents.some(es => es.id == s.id));
                
                unenrolled.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = `${s.full_name} (${s.email})`;
                    select.appendChild(opt);
                });
            }
        });
}

// Thêm sinh viên vào lớp
function enrollStudent(courseId) {
    const studentId = document.getElementById('enrollStudentSelect').value;
    if(!studentId) {
        showToast('Vui lòng chọn sinh viên cần thêm.', 'warning');
        return;
    }
    
    fetch(`${BASE_URL}/api/courses/${courseId}/students`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId })
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            showToast(res.message, 'success');
            loadCourseStudents(courseId); // Reload table
            loadCourses(); // Cập nhật lại số lượng sinh viên ở danh sách ngoài
        } else {
            showToast(res.message, 'danger');
        }
    });
}

// Xóa sinh viên khỏi lớp
function unenrollStudent(courseId, studentId) {
    if(confirm('Bạn có chắc chắn muốn xóa sinh viên này khỏi lớp học phần? Dữ liệu điểm danh của sinh viên này trong lớp sẽ bị xóa!')) {
        fetch(`${BASE_URL}/api/courses/${courseId}/students/${studentId}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                loadCourseStudents(courseId); // Reload
                loadCourses(); // Reload list ngoài
            } else {
                showToast(res.message, 'danger');
            }
        });
    }
}
