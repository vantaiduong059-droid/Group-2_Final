// public/assets/js/students.js

let currentStudentId = null;
let studentsList = [];
let currentView = localStorage.getItem('studentView') || 'grid'; // Lưu trạng thái view

document.addEventListener('DOMContentLoaded', () => {
    loadStudents();
    
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
function setStudentView(viewType) {
    currentView = viewType;
    localStorage.setItem('studentView', viewType);
    syncViewButtons();
    renderStudents();
}

// R - Lấy danh sách Học sinh
function loadStudents() {
    fetch(`${BASE_URL}/api/students`)
        .then(response => response.json())
        .then(res => {
            if(res.status === 'success') {
                studentsList = res.data;
                renderStudents();
            } else {
                showToast('Không thể tải dữ liệu học sinh', 'danger');
            }
        })
        .catch(err => {
            showToast('Lỗi khi tải dữ liệu học sinh.', 'danger');
            console.error(err);
        });
}

// Mảng màu pastel gradient cho avatar học sinh
const studentGradients = [
    'linear-gradient(135deg, #10b981, #059669)', // Xanh ngọc
    'linear-gradient(135deg, #3b82f6, #2563eb)', // Xanh da trời
    'linear-gradient(135deg, #8b5cf6, #7c3aed)', // Tím violet
    'linear-gradient(135deg, #f59e0b, #d97706)', // Cam ấm
    'linear-gradient(135deg, #ec4899, #db2777)', // Hồng đậm
    'linear-gradient(135deg, #06b6d4, #0891b2)', // Cyan
    'linear-gradient(135deg, #f43f5e, #e11d48)'  // Đỏ mâm xôi
];

function getStudentGradientById(id) {
    const index = id % studentGradients.length;
    return studentGradients[index];
}

// Render học sinh lên màn hình
function renderStudents() {
    const container = document.getElementById('studentContainer');
    if(!container) return;
    container.innerHTML = '';

    if(studentsList.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-person-x fs-1 d-block mb-2"></i>Không có học sinh nào.</div>';
        return;
    }

    if (currentView === 'grid') {
        // 1. RENDER DẠNG LƯỚI (GRID VIEW) - Cực hiện đại
        const row = document.createElement('div');
        row.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4';
        
        studentsList.forEach(s => {
            const col = document.createElement('div');
            col.className = 'col';
            
            const gradient = getStudentGradientById(s.id);
            const initialLetter = s.full_name ? s.full_name.charAt(0) : '?';

            col.innerHTML = `
                <div class="student-card">
                    <!-- Thao tác nhanh góc trên phải card -->
                    <div class="student-card-actions">
                        <button class="btn-card-action edit" onclick='openStudentEditModal(${JSON.stringify(s).replace(/"/g, '&quot;')})' title="Chỉnh sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-card-action delete" onclick="deleteStudent(${s.id})" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <!-- Avatar lớn -->
                    <div class="student-avatar-large" style="background: ${gradient};">
                        ${initialLetter}
                    </div>

                    <!-- Thông tin chi tiết -->
                    <h4 class="student-name" title="${s.full_name}">${s.full_name}</h4>
                    <span class="student-username-badge">@${s.username}</span>
                    
                    <div class="student-email">
                        <i class="bi bi-envelope-fill text-muted"></i>
                        <span class="text-truncate" style="max-width:180px;" title="${s.email}">${s.email}</span>
                    </div>
                </div>
            `;
            row.appendChild(col);
        });
        container.appendChild(row);
    } else {
        // 2. RENDER DẠNG BẢNG (LIST VIEW) - Rất sạch sẽ, thoáng đãng
        const cardModern = document.createElement('div');
        cardModern.className = 'card-modern';
        
        const tableResponsive = document.createElement('div');
        tableResponsive.className = 'table-responsive';
        
        tableResponsive.innerHTML = `
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="ps-4 py-3" style="width: 60px;">STT</th>
                        <th class="py-3">Họ và Tên học sinh</th>
                        <th class="py-3" style="width: 160px;">Tài khoản đăng nhập</th>
                        <th class="py-3">Địa chỉ Email học sinh</th>
                        <th class="py-3 text-end pe-4" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    ${studentsList.map((s, index) => `
                        <tr>
                            <td class="ps-4 fw-medium text-muted">${index + 1}</td>
                            <td><span class="fw-bold text-dark">${s.full_name}</span></td>
                            <td><span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold">@${s.username}</span></td>
                            <td class="text-secondary fw-semibold">${s.email}</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary me-1" onclick='openStudentEditModal(${JSON.stringify(s).replace(/"/g, '&quot;')})' title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="deleteStudent(${s.id})" title="Xóa">
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

// Mở modal tạo mới
function openStudentModal() {
    currentStudentId = null;
    document.getElementById('studentModalTitle').innerText = 'Thêm học sinh mới';
    document.getElementById('studentForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}

// Mở modal chỉnh sửa
function openStudentEditModal(student) {
    currentStudentId = student.id;
    document.getElementById('studentModalTitle').innerText = 'Chỉnh sửa học sinh';
    document.getElementById('studentName').value = student.full_name;
    document.getElementById('studentUsername').value = student.username;
    document.getElementById('studentEmail').value = student.email;
    
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}

// C/U - Lưu học sinh
function saveStudent() {
    const data = {
        full_name: document.getElementById('studentName').value.trim(),
        username: document.getElementById('studentUsername').value.trim(),
        email: document.getElementById('studentEmail').value.trim()
    };

    if(!data.full_name || !data.username || !data.email) {
        showToast('Vui lòng điền đủ thông tin', 'warning');
        return;
    }

    const url = currentStudentId ? `${BASE_URL}/api/students/${currentStudentId}` : `${BASE_URL}/api/students`;
    const method = currentStudentId ? 'PUT' : 'POST';

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
            bootstrap.Modal.getInstance(document.getElementById('studentModal')).hide();
            loadStudents();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        showToast('Có lỗi xảy ra', 'danger');
        console.error(err);
    });
}

// D - Xóa học sinh
function deleteStudent(id) {
    if(confirm('Bạn có chắc chắn muốn xóa học sinh này? Mọi thông tin chuyên cần, điểm danh và tương tác sẽ bị xóa vĩnh viễn!')) {
        fetch(`${BASE_URL}/api/students/${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                loadStudents();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => {
            showToast('Có lỗi xảy ra', 'danger');
        });
    }
}
