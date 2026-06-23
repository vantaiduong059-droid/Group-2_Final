// public/assets/js/teacher_engagement.js

let currentCourseId = null;

document.addEventListener('DOMContentLoaded', () => {
    // Tự động chọn lớp từ query parameter "course_id" nếu có
    const urlParams = new URLSearchParams(window.location.search);
    const courseIdParam = urlParams.get('course_id');
    if (courseIdParam) {
        const selector = document.getElementById('courseSelector');
        if (selector) {
            selector.value = courseIdParam;
        }
    }

    onCourseChange();

    // Tự động tính toán trọng số bổ trợ nếu điền
    const attInput = document.getElementById('classAttWeight');
    const quizInput = document.getElementById('classQuizWeight');

    attInput.addEventListener('input', () => {
        const val = parseInt(attInput.value);
        if (val >= 0 && val <= 100) {
            quizInput.value = 100 - val;
        } else if (attInput.value === '') {
            quizInput.value = '';
        }
    });

    quizInput.addEventListener('input', () => {
        const val = parseInt(quizInput.value);
        if (val >= 0 && val <= 100) {
            attInput.value = 100 - val;
        } else if (quizInput.value === '') {
            attInput.value = '';
        }
    });
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
    mainArea.style.display = 'flex';

    loadClassCPI();
}

// Tải điểm CPI và quy tắc của lớp
function loadClassCPI() {
    const tbody = document.getElementById('studentCpiTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải bảng điểm CPI...</td></tr>';

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/engagement`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                // 1. Render bảng điểm
                renderCpiTable(res.data.scores);

                // 2. Điền thông tin quy tắc
                fillRulesForm(res.data.rules);
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Không thể tải dữ liệu điểm CPI.</td></tr>';
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Lỗi kết nối máy chủ.</td></tr>';
        });
}

function renderCpiTable(list) {
    const tbody = document.getElementById('studentCpiTableBody');
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Lớp học này chưa có sinh viên nào đăng ký học phần.</td></tr>';
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
            <td>
                <div class="fw-bold text-dark">${s.student_name}</div>
                <div class="text-muted small">${s.student_email}</div>
            </td>
            <td><span class="fw-semibold text-muted small">${s.student_email.split('@')[0]}</span></td>
            <td class="text-center fw-medium">${s.attendance_points}đ</td>
            <td class="text-center fw-medium">${s.interaction_points}đ</td>
            <td class="text-end">
                <span class="cpi-badge ${cpiClass}">${s.total_score}đ</span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function fillRulesForm(rules) {
    const form = document.getElementById('courseConfigForm');
    
    for (const key in rules) {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = rules[key] !== null ? rules[key] : '';
        }
    }
}

// Lưu quy tắc lớp học
function saveCourseConfigs() {
    const form = document.getElementById('courseConfigForm');
    const formData = new FormData(form);
    const data = {};

    formData.forEach((value, key) => {
        data[key] = value;
    });

    // Check tổng trọng số bằng 100% nếu có điền
    if (data['rule_attendance_weight'] || data['rule_quiz_weight']) {
        const attW = parseInt(data['rule_attendance_weight'] || 0);
        const quizW = parseInt(data['rule_quiz_weight'] || 0);
        if (attW + quizW !== 100) {
            showToast('Tổng trọng số Chuyên cần và Quiz phải bằng 100% (nếu có điền)', 'warning');
            return;
        }
    }

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/engagement-rules`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadClassCPI(); // Load lại để tính toán lại điểm CPI mới của sinh viên
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Có lỗi xảy ra khi cập nhật quy tắc.', 'danger');
    });
}
