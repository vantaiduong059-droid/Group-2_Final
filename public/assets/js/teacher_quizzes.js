// public/assets/js/teacher_quizzes.js

let currentCourseId = null;
let currentQuizId = null;
let questionsList = [];
let activeDiscussionId = null;
let currentCourseSessions = [];
let quizzesPollingInterval = null;

// Lắng nghe tải trang
document.addEventListener('DOMContentLoaded', () => {
    // Nếu có chọn sẵn lớp
    onCourseChange();
});

window.addEventListener('beforeunload', () => {
    if (quizzesPollingInterval) clearInterval(quizzesPollingInterval);
});

function setupQuizzesPolling() {
    if (quizzesPollingInterval) {
        clearInterval(quizzesPollingInterval);
        quizzesPollingInterval = null;
    }
    
    const hasActive = currentCourseSessions.some(s => s.status === 'active');
    
    if (hasActive) {
        quizzesPollingInterval = setInterval(() => {
            loadDiscussionsAndLogs();
            loadQuizzes();
        }, 10000);
    }
}

// Khi giảng viên đổi lớp học phần
function onCourseChange() {
    const selector = document.getElementById('courseSelector');
    currentCourseId = selector.value;

    const noWarning = document.getElementById('noCourseWarning');
    const mainArea = document.getElementById('mainContentArea');

    if (quizzesPollingInterval) {
        clearInterval(quizzesPollingInterval);
        quizzesPollingInterval = null;
    }

    if (!currentCourseId) {
        noWarning.style.display = 'block';
        mainArea.style.display = 'none';
        currentCourseSessions = [];
        return;
    }

    noWarning.style.display = 'none';
    mainArea.style.display = 'flex';

    // Tải danh sách buổi học cho dropdown select của Quiz
    loadCourseSessions();
    
    // Tải logs tương tác và thảo luận
    loadDiscussionsAndLogs();

    // Tải danh sách Quiz
    loadQuizzes();
}

// Tải danh sách các buổi học của khóa học
function loadCourseSessions() {
    fetch(`${BASE_URL}/api/sessions`)
        .then(r => r.json())
        .then(res => {
            const select = document.getElementById('quizSessionId');
            select.innerHTML = '<option value="">-- Chọn buổi học --</option>';
            if (res.status === 'success') {
                // Lọc ra các buổi học thuộc khóa học đang chọn
                const filtered = res.data.filter(s => s.course_id == currentCourseId);
                currentCourseSessions = filtered;
                filtered.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = `Buổi ngày ${formatDateToDDMMYYYY(new Date(s.session_date.replace(' ', 'T')))} (Tiết: ${s.period})`;
                    select.appendChild(opt);
                });
                setupQuizzesPolling();
            }
        })
        .catch(err => console.error('Lỗi tải buổi học', err));
}

// Tải thảo luận và logs tương tác
function loadDiscussionsAndLogs() {
    const logsContainer = document.getElementById('classLogsContainer');
    const discContainer = document.getElementById('discussionsContainer');

    logsContainer.innerHTML = '<div class="text-muted small text-center py-4"><i class="bi bi-arrow-repeat spin"></i> Đang tải logs...</div>';
    discContainer.innerHTML = '<div class="text-muted small text-center py-4"><i class="bi bi-arrow-repeat spin"></i> Đang tải thảo luận...</div>';

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/discussions`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                // 1. Render logs tương tác
                renderLogs(res.data.logs);

                // 2. Render thảo luận
                renderDiscussions(res.data.discussions);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi tải thảo luận/logs.', 'danger');
        });
}

function renderLogs(logs) {
    const container = document.getElementById('classLogsContainer');
    if (!logs || logs.length === 0) {
        container.innerHTML = '<div class="text-muted small text-center py-4">Chưa có tương tác nào trong lớp này.</div>';
        return;
    }

    const typeTexts = {
        'question': 'đặt câu hỏi',
        'answer': 'trả lời phát biểu',
        'discussion': 'tham gia thảo luận'
    };

    container.innerHTML = '';
    logs.forEach(l => {
        const item = document.createElement('div');
        item.className = 'd-flex align-items-start gap-2 border-bottom pb-2';
        item.style.fontSize = '0.82rem';
        item.innerHTML = `
            <div class="bg-primary-subtle text-primary rounded-circle p-1.5" style="line-height:1;"><i class="bi bi-person-fill"></i></div>
            <div style="flex:1;">
                <strong>${l.student_name}</strong> (${l.student_code}) ${typeTexts[l.type] || l.type} tại buổi học ngày ${formatDateToDDMMYYYY(new Date(l.session_date.replace(' ', 'T')))}
                <div class="text-muted small mt-0.5"><i class="bi bi-clock me-1"></i>${formatTimeAgo(new Date(l.created_at.replace(' ', 'T')))}</div>
            </div>
            <span class="badge bg-success-subtle text-success">+${l.points_awarded}đ</span>
        `;
        container.appendChild(item);
    });
}

function renderDiscussions(discussions) {
    const container = document.getElementById('discussionsContainer');
    if (!discussions || discussions.length === 0) {
        container.innerHTML = '<div class="text-muted small text-center py-4">Chưa có chủ đề thảo luận nào được tạo.</div>';
        return;
    }

    container.innerHTML = '';
    discussions.forEach(d => {
        const card = document.createElement('div');
        card.className = 'discussion-card';
        card.id = `discussion-card-${d.id}`;
        
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-bold mb-0 text-dark">${d.title}</h6>
                <button class="btn btn-sm text-danger border-0 p-0" onclick="deleteDiscussion(${d.id})" title="Xóa chủ đề"><i class="bi bi-trash"></i></button>
            </div>
            <p class="text-muted small mb-2">${d.content}</p>
            <div class="d-flex justify-content-between align-items-center small text-muted">
                <div>Đăng bởi: <strong>${d.creator_name}</strong> (${d.creator_role === 'teacher' ? 'Giảng viên' : 'Sinh viên'})</div>
                <div>${formatRelativeTime(new Date(d.created_at.replace(' ', 'T')))}</div>
            </div>
            
            <div class="mt-3 pt-3 border-top">
                <div class="fw-bold small mb-2">Ý kiến thảo luận:</div>
                <div id="replies-list-${d.id}" class="d-flex flex-column gap-2 mb-3">
                    <!-- JS load replies -->
                </div>
                <!-- Form gửi phản hồi -->
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="reply-input-${d.id}" placeholder="Nhập ý kiến phản hồi...">
                    <button class="btn btn-primary-modern btn-sm" onclick="sendReply(${d.id})">Gửi</button>
                </div>
            </div>
        `;
        container.appendChild(card);
        
        // Tải các replies cho discussion này
        loadReplies(d.id);
    });
}

function loadReplies(discussionId) {
    const list = document.getElementById(`replies-list-${discussionId}`);
    list.innerHTML = '<div class="text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tải phản hồi...</div>';

    fetch(`${BASE_URL}/api/teacher/discussions/${discussionId}/replies`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                list.innerHTML = '';
                const replies = res.data;
                if (replies.length === 0) {
                    list.innerHTML = '<div class="text-muted small italic">Chưa có ý kiến phản hồi nào.</div>';
                    return;
                }
                replies.forEach(r => {
                    const item = document.createElement('div');
                    item.className = 'reply-item';
                    item.innerHTML = `
                        <div class="fw-semibold text-dark" style="font-size:0.82rem;">${r.user_name} <span class="badge ${r.user_role === 'teacher' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'} py-0.5 px-1.5" style="font-size:0.65rem;">${r.user_role === 'teacher' ? 'GV' : 'SV'}</span></div>
                        <div class="text-muted small mt-0.5">${r.content}</div>
                        <div class="text-muted small" style="font-size: 0.7rem; margin-top:2px;">${formatRelativeTime(new Date(r.created_at.replace(' ', 'T')))}</div>
                    `;
                    list.appendChild(item);
                });
            }
        })
        .catch(err => console.error(err));
}

function sendReply(discussionId) {
    const input = document.getElementById(`reply-input-${discussionId}`);
    const content = input.value.trim();
    if (!content) return;

    fetch(`${BASE_URL}/api/teacher/discussions/${discussionId}/replies`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content: content })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            input.value = '';
            showToast(res.message, 'success');
            loadReplies(discussionId);
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => console.error(err));
}

function openDiscussionModal() {
    document.getElementById('discussionForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('discussionModal'));
    modal.show();
}

function saveDiscussion() {
    const form = document.getElementById('discussionForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        title: document.getElementById('discTitle').value,
        content: document.getElementById('discContent').value
    };

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/discussions`, {
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
            const modalEl = document.getElementById('discussionModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            
            loadDiscussionsAndLogs();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => console.error(err));
}

function deleteDiscussion(id) {
    if (confirm('Bạn có chắc chắn muốn xóa chủ đề thảo luận này? Các phản hồi liên quan sẽ bị xóa!')) {
        fetch(`${BASE_URL}/api/teacher/discussions/${id}`, {
            method: 'DELETE'
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadDiscussionsAndLogs();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => console.error(err));
    }
}

// ==========================================
// TẢI & QUẢN LÝ QUIZ
// ==========================================

function loadQuizzes() {
    const container = document.getElementById('quizzesContainer');
    container.innerHTML = '<div class="text-muted small text-center py-4"><i class="bi bi-arrow-repeat spin"></i> Đang tải danh sách Quiz...</div>';

    fetch(`${BASE_URL}/api/teacher/courses/${currentCourseId}/quizzes`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                container.innerHTML = '';
                const list = res.data;
                if (!list || list.length === 0) {
                    container.innerHTML = '<div class="text-muted small text-center py-4">Chưa có Mini-Quiz nào được tạo cho lớp học này.</div>';
                    return;
                }

                list.forEach(q => {
                    const card = document.createElement('div');
                    card.className = 'card border rounded-3 p-3';
                    
                    const now = new Date();
                    const start = new Date(q.start_time.replace(' ', 'T'));
                    const end = new Date(q.end_time.replace(' ', 'T'));
                    let statusHtml = '';
                    if (now < start) {
                        statusHtml = '<span class="badge bg-warning text-dark">Lên lịch</span>';
                    } else if (now > end) {
                        statusHtml = '<span class="badge bg-secondary">Đã đóng</span>';
                    } else {
                        statusHtml = '<span class="badge bg-success animate-pulse">Đang hoạt động</span>';
                    }

                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">${q.title}</h6>
                                <div class="text-muted small"><i class="bi bi-calendar-event me-1"></i>Buổi học ngày: ${formatDateToDDMMYYYY(new Date(q.session_date.replace(' ', 'T')))}</div>
                            </div>
                            <div>
                                ${statusHtml}
                            </div>
                        </div>
                        <div class="row g-2 small text-muted mb-3 border-top pt-2">
                            <div class="col-6"><strong>Bắt đầu:</strong> ${formatDateTime(start)}</div>
                            <div class="col-6"><strong>Hết hạn:</strong> ${formatDateTime(end)}</div>
                            <div class="col-6"><strong>Thang điểm:</strong> ${q.total_marks}đ</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-xs btn-outline-primary fw-bold" onclick="openQuestionsModal(${q.id}, '${q.title}')">
                                <i class="bi bi-list-task me-1"></i> Quản lý câu hỏi
                            </button>
                            <div class="d-flex gap-2">
                                <button class="btn btn-xs btn-light text-primary" onclick='editQuiz(${JSON.stringify(q)})'><i class="bi bi-pencil"></i> Sửa</button>
                                <button class="btn btn-xs btn-light text-danger" onclick="deleteQuiz(${q.id})"><i class="bi bi-trash"></i> Xóa</button>
                            </div>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }
        })
        .catch(err => console.error(err));
}

function openQuizModal() {
    currentQuizId = null;
    document.getElementById('quizModalTitle').textContent = 'Tạo Mini-Quiz mới';
    document.getElementById('quizForm').reset();
    
    // Đặt mặc định thời gian bắt đầu là lúc này, kết thúc là +30 phút
    const now = new Date();
    const future = new Date(now.getTime() + 30 * 60000);
    document.getElementById('quizStartTime').value = formatToDateTimeLocal(now);
    document.getElementById('quizEndTime').value = formatToDateTimeLocal(future);

    const modal = new bootstrap.Modal(document.getElementById('quizModal'));
    modal.show();
}

function editQuiz(q) {
    currentQuizId = q.id;
    document.getElementById('quizModalTitle').textContent = 'Chỉnh sửa Mini-Quiz';
    
    document.getElementById('quizTitle').value = q.title;
    document.getElementById('quizSessionId').value = q.session_id;
    document.getElementById('quizStartTime').value = formatToDateTimeLocal(new Date(q.start_time.replace(' ', 'T')));
    document.getElementById('quizEndTime').value = formatToDateTimeLocal(new Date(q.end_time.replace(' ', 'T')));
    document.getElementById('quizTotalMarks').value = q.total_marks;

    const modal = new bootstrap.Modal(document.getElementById('quizModal'));
    modal.show();
}

function saveQuiz() {
    const form = document.getElementById('quizForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        title: document.getElementById('quizTitle').value,
        session_id: document.getElementById('quizSessionId').value,
        start_time: document.getElementById('quizStartTime').value,
        end_time: document.getElementById('quizEndTime').value,
        total_marks: document.getElementById('quizTotalMarks').value
    };

    const url = currentQuizId ? `${BASE_URL}/api/teacher/quizzes/${currentQuizId}` : `${BASE_URL}/api/teacher/courses/${currentCourseId}/quizzes`;
    const method = currentQuizId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
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
            const modalEl = document.getElementById('quizModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            
            loadQuizzes();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => console.error(err));
}

function deleteQuiz(id) {
    if (confirm('Bạn có chắc chắn muốn xóa Mini-Quiz này? Tất cả câu hỏi và điểm số sinh viên nộp bài liên quan sẽ bị xóa!')) {
        fetch(`${BASE_URL}/api/teacher/quizzes/${id}`, {
            method: 'DELETE'
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadQuizzes();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => console.error(err));
    }
}

// ==========================================
// QUẢN LÝ CÂU HỎI TRẮC NGHIỆM
// ==========================================

function openQuestionsModal(quizId, quizTitle) {
    currentQuizId = quizId;
    document.getElementById('questionsModalSubtitle').textContent = `Quiz: ${quizTitle}`;
    resetQuestionForm();
    loadQuestions();

    const modal = new bootstrap.Modal(document.getElementById('questionsModal'));
    modal.show();
}

function loadQuestions() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '<div class="text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tải câu hỏi...</div>';

    fetch(`${BASE_URL}/api/teacher/quizzes/${currentQuizId}/questions`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                questionsList = res.data;
                renderQuestionsList(questionsList);
            }
        })
        .catch(err => console.error(err));
}

function renderQuestionsList(list) {
    const container = document.getElementById('questionsContainer');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="text-muted small text-center py-4">Chưa có câu hỏi trắc nghiệm nào trong Quiz này.</div>';
        return;
    }

    container.innerHTML = '';
    list.forEach((q, idx) => {
        const item = document.createElement('div');
        item.className = 'quiz-question-item';
        item.innerHTML = `
            <div class="question-actions">
                <button class="btn btn-sm btn-light text-primary py-0 px-1.5" onclick='editQuestion(${JSON.stringify(q)})' title="Sửa"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-light text-danger py-0 px-1.5" onclick="deleteQuestion(${q.id})" title="Xóa"><i class="bi bi-trash"></i></button>
            </div>
            <div class="fw-bold mb-1" style="font-size:0.88rem;">Câu ${idx + 1}: ${q.question_text}</div>
            <div class="row g-2 small text-muted">
                <div class="col-6 ${q.correct_option === 'A' ? 'text-success fw-bold' : ''}">A. ${q.option_a}</div>
                <div class="col-6 ${q.correct_option === 'B' ? 'text-success fw-bold' : ''}">B. ${q.option_b}</div>
                <div class="col-6 ${q.correct_option === 'C' ? 'text-success fw-bold' : ''}">C. ${q.option_c}</div>
                <div class="col-6 ${q.correct_option === 'D' ? 'text-success fw-bold' : ''}">D. ${q.option_d}</div>
            </div>
            <div class="mt-2 text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Đáp án đúng: ${q.correct_option}</div>
        `;
        container.appendChild(item);
    });
}

function saveQuestion() {
    const form = document.getElementById('questionForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const qId = document.getElementById('questionId').value;
    const data = {
        question_text: document.getElementById('questionText').value,
        option_a: document.getElementById('optionA').value,
        option_b: document.getElementById('optionB').value,
        option_c: document.getElementById('optionC').value,
        option_d: document.getElementById('optionD').value,
        correct_option: document.getElementById('correctOption').value
    };

    const url = qId ? `${BASE_URL}/api/teacher/questions/${qId}` : `${BASE_URL}/api/teacher/quizzes/${currentQuizId}/questions`;
    const method = qId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            resetQuestionForm();
            loadQuestions();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(err => console.error(err));
}

function editQuestion(q) {
    document.getElementById('questionFormTitle').textContent = 'Chỉnh sửa câu hỏi';
    document.getElementById('questionId').value = q.id;
    document.getElementById('questionText').value = q.question_text;
    document.getElementById('optionA').value = q.option_a;
    document.getElementById('optionB').value = q.option_b;
    document.getElementById('optionC').value = q.option_c;
    document.getElementById('optionD').value = q.option_d;
    document.getElementById('correctOption').value = q.correct_option;
}

function deleteQuestion(id) {
    if (confirm('Bạn có chắc muốn xóa câu hỏi này?')) {
        fetch(`${BASE_URL}/api/teacher/questions/${id}`, {
            method: 'DELETE'
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadQuestions();
            } else {
                showToast(res.message, 'danger');
            }
        })
        .catch(err => console.error(err));
    }
}

function resetQuestionForm() {
    document.getElementById('questionFormTitle').textContent = 'Thêm câu hỏi mới';
    document.getElementById('questionForm').reset();
    document.getElementById('questionId').value = '';
}

// ==========================================
// HELPERS
// ==========================================

function formatDateToDDMMYYYY(date) {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

function formatDateTime(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

function formatToDateTimeLocal(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

function formatRelativeTime(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHrs / 24);

    if (diffMins < 1) return 'Vừa xong';
    if (diffMins < 60) return `${diffMins} phút trước`;
    if (diffHrs < 24) return `${diffHrs} giờ trước`;
    return `${diffDays} ngày trước`;
}

function formatTimeAgo(date) {
    return formatRelativeTime(date);
}
