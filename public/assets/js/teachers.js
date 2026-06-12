// public/assets/js/teachers.js

let currentTeacherId = null;
let teachersList = [];
let currentView = localStorage.getItem('teacherView') || 'grid'; // Lưu trạng thái view

document.addEventListener('DOMContentLoaded', () => {
    loadTeachers();
    
    // Đồng bộ nút view
    syncViewButtons();
});

// Đồng bộ trạng thái active của nút view
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

// Chuyển đổi view Grid / List
function setTeacherView(viewType) {
    currentView = viewType;
    localStorage.setItem('teacherView', viewType);
    syncViewButtons();
    renderTeachers();
}

// R - Lấy danh sách Giảng viên từ API
function loadTeachers() {
    fetch(`${BASE_URL}/api/teachers`)
        .then(response => response.json())
        .then(res => {
            if(res.status === 'success') {
                teachersList = res.data;
                renderTeachers();
            } else {
                showToast('Không thể tải dữ liệu giáo viên', 'danger');
            }
        })
        .catch(err => {
            showToast('Lỗi kết nối khi tải giáo viên', 'danger');
            console.error(err);
        });
}

// Danh sách màu gradient mượt mà cho Avatar giáo viên
const avatarGradients = [
    'linear-gradient(135deg, #0284c7, #0369a1)', // Xanh dương
    'linear-gradient(135deg, #0d9488, #0f766e)', // Teal
    'linear-gradient(135deg, #7c3aed, #6d28d9)', // Tím
    'linear-gradient(135deg, #ea580c, #c2410c)', // Cam
    'linear-gradient(135deg, #db2777, #be185d)', // Hồng
    'linear-gradient(135deg, #2563eb, #1d4ed8)', // Royal Blue
    'linear-gradient(135deg, #059669, #047857)'  // Xanh lá
];

function getAvatarGradientById(id) {
    const index = id % avatarGradients.length;
    return avatarGradients[index];
}

// Render danh sách giáo viên lên màn hình
function renderTeachers() {
    const container = document.getElementById('teacherContainer');
    if(!container) return;
    container.innerHTML = '';

    if (teachersList.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-person-x fs-1 d-block mb-2"></i>Không có giảng viên nào.</div>';
        return;
    }

    if (currentView === 'grid') {
        // 1. RENDER DẠNG LƯỚI (GRID VIEW) - Cực đẹp
        const row = document.createElement('div');
        row.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4';
        
        teachersList.forEach(t => {
            const col = document.createElement('div');
            col.className = 'col';
            
            // Xử lý tạo chip cho các lớp phụ trách
            let classChipsHtml = '<span class="text-muted small fst-italic">Chưa có lớp</span>';
            if (t.teaching_classes) {
                const classes = t.teaching_classes.split('||');
                classChipsHtml = classes.map(c => {
                    const parts = c.split('::');
                    if(parts.length === 2) {
                        return `<a href="#" onclick="showCourseDetails(${parts[0]}); return false;" class="class-chip" title="Xem chi tiết lớp"><i class="bi bi-journal-bookmark"></i>${parts[1]}</a>`;
                    }
                    return '';
                }).join('');
            }

            const gradient = getAvatarGradientById(t.id);
            const initialLetter = t.full_name ? t.full_name.charAt(0) : '?';

            col.innerHTML = `
                <div class="teacher-card">
                    <!-- Nút thao tác nhanh góc trên phải card -->
                    <div class="teacher-card-actions">
                        <button class="btn-card-action edit" onclick='openTeacherEditModal(${JSON.stringify(t).replace(/"/g, '&quot;')})' title="Chỉnh sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-card-action delete" onclick="deleteTeacher(${t.id})" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <!-- Avatar lớn -->
                    <div class="teacher-avatar-large" style="background: ${gradient};">
                        ${initialLetter}
                    </div>

                    <!-- Thông tin chi tiết -->
                    <h4 class="teacher-name" title="${t.full_name}">${t.full_name}</h4>
                    <span class="teacher-username-badge">@${t.username}</span>
                    
                    <div class="teacher-email">
                        <i class="bi bi-envelope-fill text-muted"></i>
                        <span class="text-truncate" style="max-width:180px;" title="${t.email}">${t.email}</span>
                    </div>

                    <!-- Lớp phụ trách -->
                    <div class="teacher-classes-title">Các lớp đảm nhận</div>
                    <div class="teacher-classes-list">
                        ${classChipsHtml}
                    </div>
                </div>
            `;
            row.appendChild(col);
        });
        container.appendChild(row);
    } else {
        // 2. RENDER DẠNG BẢNG (LIST VIEW) - Rất ngăn nắp
        const cardModern = document.createElement('div');
        cardModern.className = 'card-modern';
        
        const tableResponsive = document.createElement('div');
        tableResponsive.className = 'table-responsive';
        
        tableResponsive.innerHTML = `
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="ps-4 py-3" style="width: 60px;">STT</th>
                        <th class="py-3">Họ và Tên giảng viên</th>
                        <th class="py-3">Các lớp phụ trách giảng dạy</th>
                        <th class="py-3" style="width: 140px;">Tên tài khoản</th>
                        <th class="py-3">Địa chỉ Email</th>
                        <th class="py-3 text-end pe-4" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    ${teachersList.map((t, index) => {
                        let classLinks = '<span class="text-muted fst-italic">Chưa phân công</span>';
                        if (t.teaching_classes) {
                            const classes = t.teaching_classes.split('||');
                            classLinks = classes.map(c => {
                                const parts = c.split('::');
                                if(parts.length === 2) {
                                    return `<a href="#" onclick="showCourseDetails(${parts[0]}); return false;" class="class-chip me-1.5 mb-1.5"><i class="bi bi-journal-bookmark me-1 text-primary"></i>${parts[1]}</a>`;
                                }
                                return '';
                            }).join('');
                        }

                        return `
                            <tr>
                                <td class="ps-4 fw-medium text-muted">${index + 1}</td>
                                <td><span class="fw-bold text-dark">${t.full_name}</span></td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center">${classLinks}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold">@${t.username}</span></td>
                                <td class="text-secondary fw-semibold">${t.email}</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light text-primary me-1" onclick='openTeacherEditModal(${JSON.stringify(t).replace(/"/g, '&quot;')})' title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteTeacher(${t.id})" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
        cardModern.appendChild(tableResponsive);
        container.appendChild(cardModern);
    }
}

// Mở modal tạo mới
function openTeacherModal() {
    currentTeacherId = null;
    document.getElementById('teacherModalTitle').innerText = 'Thêm giảng viên mới';
    document.getElementById('teacherForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('teacherModal'));
    modal.show();
}

// Mở modal chỉnh sửa
function openTeacherEditModal(teacher) {
    currentTeacherId = teacher.id;
    document.getElementById('teacherModalTitle').innerText = 'Chỉnh sửa giảng viên';
    document.getElementById('teacherName').value = teacher.full_name;
    document.getElementById('teacherUsername').value = teacher.username;
    document.getElementById('teacherEmail').value = teacher.email;
    
    const modal = new bootstrap.Modal(document.getElementById('teacherModal'));
    modal.show();
}

// C/U - Lưu giảng viên
function saveTeacher() {
    const data = {
        full_name: document.getElementById('teacherName').value.trim(),
        username: document.getElementById('teacherUsername').value.trim(),
        email: document.getElementById('teacherEmail').value.trim()
    };

    if(!data.full_name || !data.username || !data.email) {
        showToast('Vui lòng điền đầy đủ các thông tin bắt buộc', 'warning');
        return;
    }

    const url = currentTeacherId ? `${BASE_URL}/api/teachers/${currentTeacherId}` : `${BASE_URL}/api/teachers`;
    const method = currentTeacherId ? 'PUT' : 'POST';

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
            bootstrap.Modal.getInstance(document.getElementById('teacherModal')).hide();
            loadTeachers();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra khi lưu thông tin.', 'danger');
        console.error(err);
    });
}

// D - Xóa giảng viên
function deleteTeacher(id) {
    if(confirm('Bạn có chắc chắn muốn xóa giảng viên này? Các lớp học phần do giảng viên này phụ trách có thể bị ảnh hưởng!')) {
        fetch(`${BASE_URL}/api/teachers/${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                loadTeachers();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => {
            showToast('Có lỗi xảy ra khi xóa giảng viên.', 'danger');
        });
    }
}

// Hàm mở Modal xem thông tin Khóa học chi tiết kèm số lượng sinh viên thật từ DB
function showCourseDetails(courseId) {
    const modal = new bootstrap.Modal(document.getElementById('courseDetailsModal'));
    modal.show();
    const body = document.getElementById('courseDetailsBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>Đang tải dữ liệu thực tế từ database...</div>';
    
    fetch(`${BASE_URL}/api/courses/${courseId}`)
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const c = res.data;
                body.innerHTML = `
                    <div class="mb-3">
                        <label class="text-secondary small fw-semibold d-block mb-1">Tên lớp học phần</label>
                        <div class="fw-bold fs-5 text-primary" style="line-height:1.35;">${c.name}</div>
                    </div>
                    <div class="row mb-3 bg-light p-2.5 rounded-3 g-0 border">
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
                    <div class="mb-4">
                        <label class="text-secondary small d-block mb-1">Mô tả tóm tắt</label>
                        <div class="text-muted small bg-light p-2 rounded-2" style="max-height:100px; overflow-y:auto; line-height:1.45;">
                            ${c.description || 'Chưa có mô tả cho học phần này.'}
                        </div>
                    </div>
                    
                    <!-- Dữ liệu thật từ DB -->
                    <div class="alert alert-info mb-0 d-flex align-items-center border-0 p-3 rounded-3" style="background-color: #e0f2fe; color: #0369a1;">
                        <i class="bi bi-people-fill fs-3 me-3 opacity-80"></i>
                        <div>
                            <div class="small fw-semibold opacity-90">Tổng số sinh viên đang theo học</div>
                            <strong class="fs-4 fw-bold" style="letter-spacing: -0.5px;">${c.student_count || 0}</strong> sinh viên thực tế
                        </div>
                    </div>
                `;
            } else {
                body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>${res.message}</div>`;
            }
        })
        .catch(err => {
            body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-wifi-off me-2"></i>Lỗi kết nối máy chủ không thể lấy chi tiết.</div>`;
        });
}
