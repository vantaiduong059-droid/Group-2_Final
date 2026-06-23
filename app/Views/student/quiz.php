<?php ob_start(); ?>
<div class="d-flex flex-column gap-4">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active">Quiz & Thảo luận</li>
        </ol></nav>
        <h3 class="fw-bold mb-0">Quiz & Thảo luận</h3>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="fw-semibold small mb-1">Chọn môn học</label>
            <select class="form-select" id="subjectSelect" onchange="onSubjectChange()">
                <option value="">-- Chọn môn học --</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="fw-semibold small mb-1">Chọn buổi học</label>
            <select class="form-select" id="sessionSelect" disabled>
                <option value="">-- Chọn buổi học để xem quiz/thảo luận --</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary-modern" onclick="loadQuizzes()">
                <i class="bi bi-arrow-right-circle me-2"></i>Xem nội dung
            </button>
        </div>
    </div>

    <div id="noSessionsAlert" class="alert alert-warning mt-3 border-0 rounded-3 shadow-sm" style="display:none;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Môn học này hiện chưa có Quiz/Thảo luận nào.
    </div>

    <div id="quizContent" style="display:none;">
        <!-- Quiz Tab -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <ul class="nav nav-tabs border-0 mb-0" id="quizTabs" style="gap: 8px;">
                <li class="nav-item"><button class="nav-link active" data-tab="quizzes" onclick="switchTab('quizzes')"><i class="bi bi-patch-question me-2"></i>Quiz</button></li>
                <li class="nav-item"><button class="nav-link" data-tab="discussions" onclick="switchTab('discussions')"><i class="bi bi-chat-left-dots me-2"></i>Thảo luận</button></li>
            </ul>
            <button class="btn btn-sm btn-outline-secondary px-3 py-1.5 fw-semibold" onclick="loadQuizzes()"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
        </div>

        <!-- Quizzes -->
        <div id="tabQuizzes">
            <div id="quizzesList">
                <div class="text-center py-5 text-muted"><i class="bi bi-arrow-repeat spin fs-3"></i></div>
            </div>
        </div>

        <!-- Discussions -->
        <div id="tabDiscussions" style="display:none;">
            <div id="discussionsList">
                <div class="text-center py-5 text-muted"><i class="bi bi-arrow-repeat spin fs-3"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Quiz Modal -->
<div class="modal fade" id="quizModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="quizModalTitle">Làm Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quizModalBody">Đang tải câu hỏi...</div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                <button class="btn btn-primary-modern" onclick="submitQuiz()" id="submitQuizBtn">Nộp bài</button>
            </div>
        </div>
    </div>
</div>

<!-- Discussion Reply Modal -->
<div class="modal fade" id="discussModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="discussModalTitle">Tham gia thảo luận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="discussReplies" class="mb-3 border rounded p-3" style="max-height:280px;overflow-y:auto;">Đang tải...</div>
                <label class="fw-semibold small mb-1">Bài đăng của bạn</label>
                <textarea class="form-control" id="replyContent" rows="3" placeholder="Viết bình luận / câu trả lời..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                <button class="btn btn-primary-modern" onclick="submitReply()"><i class="bi bi-send me-2"></i>Đăng</button>
            </div>
        </div>
    </div>
</div>

<?php $myCoursesJson = json_encode($myCourses ?? []); ?>
<script>
let currentQuizId = null, currentDiscussionId = null, currentSessionQuestions = [];
let activeSessionsList = [];
let quizPollingInterval = null;
let quizModalInstance = null;
let discussModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    loadSubjectOptions();
});

window.addEventListener('beforeunload', () => {
    if (quizPollingInterval) clearInterval(quizPollingInterval);
});

function loadSubjectOptions() {
    fetch(`${BASE_URL}/api/student/subjects`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            const sel = document.getElementById('subjectSelect');
            sel.innerHTML = '<option value="">-- Chọn môn học --</option>';
            res.data.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id;
                o.textContent = `${c.code} - ${c.name}`;
                sel.appendChild(o);
            });
        }).catch(err => console.error(err));
}

function onSubjectChange() {
    const courseId = document.getElementById('subjectSelect').value;
    const sessSel = document.getElementById('sessionSelect');
    const alertDiv = document.getElementById('noSessionsAlert');
    
    // Reset và ẩn phần nội dung trước đó
    sessSel.innerHTML = '<option value="">-- Chọn buổi học để xem quiz/thảo luận --</option>';
    sessSel.disabled = true;
    alertDiv.style.display = 'none';
    document.getElementById('quizContent').style.display = 'none';

    if (!courseId) return;

    if (quizPollingInterval) {
        clearInterval(quizPollingInterval);
        quizPollingInterval = null;
    }

    // Luôn cho phép chọn thảo luận chung và kích hoạt dropdown luôn
    sessSel.disabled = false;
    const optCommon = document.createElement('option');
    optCommon.value = 'common';
    optCommon.textContent = '-- Thảo luận chung môn học --';
    sessSel.appendChild(optCommon);
    sessSel.value = 'common'; // đặt mặc định là thảo luận chung

    fetch(`${BASE_URL}/api/student/sessions?course_id=${courseId}&has_activities=1`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            activeSessionsList = res.data;
            res.data.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = `Buổi ngày ${s.session_date.split('-').reverse().join('/')} (${(s.start_time||'').slice(0,5)})`;
                sessSel.appendChild(o);
            });
        }).catch(err => console.error(err));
}

function switchTab(tab) {
    document.querySelectorAll('#quizTabs .nav-link').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.getElementById('tabQuizzes').style.display = tab === 'quizzes' ? 'block' : 'none';
    document.getElementById('tabDiscussions').style.display = tab === 'discussions' ? 'block' : 'none';
}

function loadQuizzes() {
    const courseId = document.getElementById('subjectSelect').value;
    const sessionId = document.getElementById('sessionSelect').value;
    if (!sessionId) { showToast('Vui lòng chọn buổi học hoặc thảo luận chung.', 'warning'); return; }
    document.getElementById('quizContent').style.display = 'block';

    if (quizPollingInterval) {
        clearInterval(quizPollingInterval);
        quizPollingInterval = null;
    }

    const fetchQuizzesData = () => {
        fetch(`${BASE_URL}/api/student/quizzes?session_id=${sessionId}&course_id=${courseId}`)
            .then(r => r.json()).then(res => {
                if (res.status !== 'success') return;
                renderQuizzes(res.data.quizzes || []);
                renderDiscussions(res.data.discussions || []);
            }).catch(err => console.error(err));
    };

    fetchQuizzesData();

    if (sessionId !== 'common') {
        const currentSession = activeSessionsList.find(s => s.id == sessionId);
        if (currentSession && currentSession.status === 'active') {
            quizPollingInterval = setInterval(fetchQuizzesData, 10000);
        }
    }
}

function renderQuizzes(quizzes) {
    const el = document.getElementById('quizzesList');
    if (quizzes.length === 0) {
        el.innerHTML = '<div class="text-center py-5"><i class="bi bi-patch-question fs-1 text-muted d-block mb-3"></i><div class="text-muted">Chưa có quiz nào trong buổi này</div></div>';
        return;
    }
    el.innerHTML = quizzes.map(q => {
        const done = q.my_submissions > 0;
        return `<div class="card-modern p-4 mb-3">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div style="flex:1;">
                    <h6 class="fw-bold mb-1">${q.title || 'Mini Quiz'}</h6>
                    <div class="text-muted small mb-2"><i class="bi bi-question-circle me-1"></i>${q.question_count} câu hỏi &nbsp;|&nbsp; <i class="bi bi-clock me-1"></i>${q.duration_minutes || '--'} phút</div>
                    ${done ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã nộp</span>' : '<span class="badge bg-warning text-dark">Chưa làm</span>'}
                </div>
                <button class="btn btn-primary-modern btn-sm" onclick="openQuiz(${q.id})" ${done ? 'disabled' : ''}>
                    ${done ? '<i class="bi bi-eye me-1"></i>Xem kết quả' : '<i class="bi bi-play-circle me-1"></i>Làm ngay'}
                </button>
            </div>
        </div>`;
    }).join('');
}

function renderDiscussions(discussions) {
    const el = document.getElementById('discussionsList');
    if (discussions.length === 0) {
        el.innerHTML = '<div class="text-center py-5"><i class="bi bi-chat-left-dots fs-1 text-muted d-block mb-3"></i><div class="text-muted">Chưa có chủ đề thảo luận nào</div></div>';
        return;
    }
    el.innerHTML = discussions.map(d => `
        <div class="card-modern p-4 mb-3">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div style="flex:1;">
                    <h6 class="fw-bold mb-1">${d.title}</h6>
                    <div class="text-muted small mb-2">${d.description || ''}</div>
                    <div class="d-flex gap-3 text-muted small">
                        <span><i class="bi bi-chat-dots me-1"></i>${d.reply_count} bình luận</span>
                        ${d.my_replies > 0 ? '<span class="text-success"><i class="bi bi-check me-1"></i>Đã tham gia</span>' : ''}
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm" onclick="openDiscussion(${d.id}, '${d.title.replace(/'/g,"\\'")}')">
                    <i class="bi bi-chat-right-text me-1"></i>Tham gia
                </button>
            </div>
        </div>`).join('');
}

function openQuiz(quizId) {
    currentQuizId = quizId;
    document.getElementById('quizModalBody').innerHTML = '<div class="text-center py-4"><i class="bi bi-arrow-repeat spin fs-3"></i></div>';
    if (!quizModalInstance) {
        quizModalInstance = new bootstrap.Modal(document.getElementById('quizModal'));
    }
    quizModalInstance.show();
    
    fetch(`${BASE_URL}/api/teacher/quizzes/${quizId}/questions`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') { document.getElementById('quizModalBody').innerHTML = '<div class="text-center text-muted">Lỗi tải câu hỏi.</div>'; return; }
            currentSessionQuestions = res.data || [];
            document.getElementById('quizModalBody').innerHTML = currentSessionQuestions.map((q, i) => `
                <div class="mb-4">
                    <div class="fw-semibold mb-2">Câu ${i+1}: ${q.question_text}</div>
                    ${['A','B','C','D'].filter(opt => q['option_'+opt.toLowerCase()]).map(opt => `
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="q${q.id}" value="${opt}" id="q${q.id}${opt}">
                            <label class="form-check-label" for="q${q.id}${opt}">${opt}. ${q['option_'+opt.toLowerCase()]}</label>
                        </div>`).join('')}
                </div>`).join('');
        });
}

function submitQuiz() {
    const answers = {};
    currentSessionQuestions.forEach(q => {
        const sel = document.querySelector(`input[name="q${q.id}"]:checked`);
        if (sel) answers[q.id] = sel.value;
    });
    fetch(`${BASE_URL}/api/quiz/${currentQuizId}/submit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ quiz_id: currentQuizId, answers })
    }).then(r => r.json()).then(res => {
        showToast(res.message || 'Đã nộp bài!', res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') {
            if (quizModalInstance) {
                quizModalInstance.hide();
            } else {
                bootstrap.Modal.getInstance(document.getElementById('quizModal'))?.hide();
            }
            loadQuizzes();
        }
    });
}

function openDiscussion(discussId, title) {
    currentDiscussionId = discussId;
    document.getElementById('discussModalTitle').textContent = title;
    document.getElementById('discussReplies').innerHTML = '<div class="text-center py-3"><i class="bi bi-arrow-repeat spin"></i></div>';
    if (!discussModalInstance) {
        discussModalInstance = new bootstrap.Modal(document.getElementById('discussModal'));
    }
    discussModalInstance.show();

    fetch(`${BASE_URL}/api/discussions/${discussId}/replies`)
        .then(r => r.json()).then(res => {
            if (!res.data || res.data.length === 0) {
                document.getElementById('discussReplies').innerHTML = '<div class="text-muted small text-center py-3">Chưa có bình luận nào. Hãy là người đầu tiên!</div>';
                return;
            }
            document.getElementById('discussReplies').innerHTML = res.data.map(r => {
                const isTeacher = r.user_role === 'teacher';
                const roleBadge = isTeacher ? '<span class="badge bg-danger ms-1" style="font-size:0.6rem;">GV</span>' : '<span class="badge bg-secondary ms-1" style="font-size:0.6rem;">SV</span>';
                const authorName = r.user_name || 'Người dùng';
                return `
                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex gap-2 align-items-center mb-1">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(authorName)}&size=28&background=6366f1&color=fff" style="border-radius:50%;width:28px;height:28px;">
                        <span class="fw-semibold small">${authorName} ${roleBadge}</span>
                        <span class="text-muted" style="font-size:0.72rem;">${r.created_at ? r.created_at.slice(11, 16) : ''}</span>
                    </div>
                    <div class="small">${r.content}</div>
                </div>`;
            }).join('');
        });
}

function submitReply() {
    const content = document.getElementById('replyContent').value.trim();
    if (!content) { showToast('Vui lòng nhập nội dung.', 'warning'); return; }
    fetch(`${BASE_URL}/api/discussions/${currentDiscussionId}/reply`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') { 
            document.getElementById('replyContent').value = ''; 
            openDiscussion(currentDiscussionId, document.getElementById('discussModalTitle').textContent); 
            loadQuizzes();
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
