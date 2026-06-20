<?php ob_start(); ?>
<style>
.att-btn-group .btn { border-radius: 20px; font-size: 0.8rem; font-weight: 600; padding: 4px 12px; }
.qr-display { display: inline-block; padding: 12px; background: #fff; border: 1px solid var(--border-color); border-radius: 12px; }
.code-big { font-size: 3rem; font-weight: 900; letter-spacing: 14px; color: var(--primary, #2563eb); text-align: center; }
.tab-content-sect { display: none; }
.tab-content-sect.active { display: block; }
.log-item { padding: 10px 14px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--border-color); margin-bottom: 8px; }
</style>

<?php
$s = $session;
$sessionId = $s['id'];
?>

<div class="d-flex flex-column gap-4">
    <!-- Breadcrumb & Header -->
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/sessions" class="text-decoration-none text-muted">Lịch học</a></li>
            <li class="breadcrumb-item active">Chi tiết buổi học</li>
        </ol></nav>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h3 class="fw-bold mb-0"><?= htmlspecialchars($s['course_name'] ?? 'Buổi học') ?></h3>
            <?php
            $statusBadge = ['scheduled' => 'bg-secondary', 'active' => 'bg-success', 'completed' => 'bg-dark'];
            $statusTxt = ['scheduled' => 'Dự kiến', 'active' => 'Đang diễn ra', 'completed' => 'Đã kết thúc'];
            ?>
            <span class="badge <?= $statusBadge[$s['status']] ?? 'bg-secondary' ?>"><?= $statusTxt[$s['status']] ?? $s['status'] ?></span>
        </div>
        <div class="text-muted small mt-1">
            <i class="bi bi-calendar me-1"></i><?= $s['session_date'] ?>
            &nbsp;|&nbsp;<i class="bi bi-clock me-1"></i><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?>
            &nbsp;|&nbsp;<i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['room'] ?? 'TBD') ?>
        </div>
    </div>

    <!-- Tabs -->
    <div>
        <ul class="nav nav-tabs" id="sessionTabs">
            <li class="nav-item"><button class="nav-link active" onclick="switchTab('attendance')"><i class="bi bi-person-check me-1"></i>Điểm danh</button></li>
            <li class="nav-item"><button class="nav-link" onclick="switchTab('quiz')"><i class="bi bi-patch-question me-1"></i>Quiz & Thảo luận</button></li>
            <li class="nav-item"><button class="nav-link" onclick="switchTab('log')"><i class="bi bi-clock-history me-1"></i>Log</button></li>
            <li class="nav-item"><button class="nav-link" onclick="switchTab('complaints')"><i class="bi bi-chat-right-text me-1"></i>Khiếu nại</button></li>
        </ul>
    </div>

    <!-- ATTENDANCE TAB -->
    <div id="tabAttendance" class="tab-content-sect active">
        <!-- Attendance Controls (Teacher) -->
        <?php if ($s['status'] !== 'completed'): ?>
        <div class="card-modern p-4 mb-4">
            <h6 class="fw-bold mb-3">Mở điểm danh</h6>
            <div class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="fw-semibold small mb-1">Hình thức</label>
                    <select class="form-select" id="attendMethodSelect">
                        <option value="Code">Mã số (6 chữ số)</option>
                        <option value="QR">Mã QR</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="fw-semibold small mb-1">Thời hạn (phút)</label>
                    <input type="number" class="form-control" id="attendMinutes" value="5" min="1" max="60" style="width: 90px;">
                </div>
                <div class="col-auto">
                    <?php if ($s['status'] === 'active'): ?>
                    <button class="btn btn-danger fw-semibold" onclick="stopAttendance()">
                        <i class="bi bi-stop-circle me-2"></i>Đóng điểm danh
                    </button>
                    <?php else: ?>
                    <button class="btn btn-success fw-semibold" onclick="startAttendance()">
                        <i class="bi bi-play-circle me-2"></i>Bắt đầu điểm danh
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Code/QR Display -->
            <?php 
            $statusInfo = AttendanceSessionHelper::getStatus($s);
            if ($statusInfo['status'] === 'dang_mo'): 
            ?>
            <div class="mt-4" id="activeCodeDisplay">
                <?php if ($s['attendance_code']): ?>
                <div class="code-big"><?= $s['attendance_code'] ?></div>
                <div class="text-center text-muted small mt-1">Hết hạn: <?= $s['attendance_expires_at'] ?> (Còn <?= $statusInfo['remaining_minutes'] ?> phút)</div>
                <?php elseif ($s['qr_token']): ?>
                <div class="text-center">
                    <div class="qr-display">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($s['qr_token']) ?>" alt="QR Code" width="200">
                    </div>
                    <div class="text-muted small mt-2">Token: <code><?= $s['qr_token'] ?></code></div>
                    <div class="text-muted small">Hết hạn: <?= $s['attendance_expires_at'] ?> (Còn <?= $statusInfo['remaining_minutes'] ?> phút)</div>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($statusInfo['status'] === 'da_dong' && ($s['attendance_code'] || $s['qr_token'])): ?>
            <div class="mt-4 text-center">
                <div class="alert alert-danger d-inline-block px-4 py-2 small" style="border-radius:10px;">
                    <i class="bi bi-clock-fill me-1"></i>Phiên điểm danh tự động đã hết hạn (<?= $s['attendance_expires_at'] ?>).
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Student List + Attendance -->
        <div class="card-modern p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Danh sách sinh viên</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadAttendanceList()"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
                    <?php if ($s['status'] !== 'completed'): ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="markAllPresent()">Đánh dấu tất cả có mặt</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Sinh viên</th>
                            <th>Trạng thái</th>
                            <th>Hình thức</th>
                            <th>Thời điểm</th>
                            <th>Sửa</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- QUIZ & DISCUSSION TAB -->
    <div id="tabQuiz" class="tab-content-sect">
        <div class="card-modern p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Quiz & Thảo luận</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadQuizDiscuss()"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
                    <button class="btn btn-sm btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createQuizModal">
                        <i class="bi bi-plus me-1"></i>Tạo Quiz
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createDiscussModal">
                        <i class="bi bi-plus me-1"></i>Tạo Thảo luận
                    </button>
                </div>
            </div>
            <div id="quizDiscussContent">
                <div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
            </div>
        </div>
    </div>

    <!-- LOG TAB -->
    <div id="tabLog" class="tab-content-sect">
        <div class="card-modern p-4">
            <h6 class="fw-bold mb-3">Log thay đổi điểm danh</h6>
            <div id="changeLogList">
                <div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
            </div>
        </div>
    </div>

    <!-- COMPLAINTS TAB -->
    <div id="tabComplaints" class="tab-content-sect">
        <div class="card-modern p-4">
            <h6 class="fw-bold mb-3">Khiếu nại của sinh viên</h6>
            <div id="complaintsList">
                <div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Sửa điểm danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><strong id="editAttStudentName"></strong></div>
                <div class="mb-3">
                    <label class="fw-semibold small mb-1">Trạng thái mới</label>
                    <select class="form-select" id="editAttStatus">
                        <option value="present">Có mặt</option>
                        <option value="late">Đi muộn</option>
                        <option value="absent">Vắng</option>
                        <option value="excused">Có phép</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-semibold small mb-1">Lý do thay đổi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editAttReason" placeholder="Ví dụ: Sinh viên có giấy phép...">
                </div>
                <input type="hidden" id="editAttStudentId">
                <input type="hidden" id="editAttOldStatus">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-primary-modern" onclick="saveAttendanceEdit()">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Quiz Modal -->
<div class="modal fade" id="createQuizModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tạo Quiz mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="fw-semibold small mb-1">Tiêu đề Quiz</label><input type="text" class="form-control" id="quizTitle" placeholder="Ví dụ: Quiz buổi 5..."></div>
                <div class="mb-3"><label class="fw-semibold small mb-1">Thời gian làm (phút)</label><input type="number" class="form-control" id="quizDuration" value="10" min="1" max="120"></div>
                <div id="questionsContainer">
                    <div class="fw-semibold mb-2">Câu hỏi</div>
                    <div id="questionList"></div>
                    <button class="btn btn-outline-secondary btn-sm mt-2" onclick="addQuestion()"><i class="bi bi-plus me-1"></i>Thêm câu hỏi</button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-primary-modern" onclick="createQuiz()">Tạo Quiz</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Discussion Modal -->
<div class="modal fade" id="createDiscussModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tạo chủ đề thảo luận</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="fw-semibold small mb-1">Tiêu đề</label><input type="text" class="form-control" id="discussTitle" placeholder="Nhập chủ đề thảo luận..."></div>
                <div class="mb-3"><label class="fw-semibold small mb-1">Mô tả / Câu hỏi gợi mở</label><textarea class="form-control" id="discussDesc" rows="3" placeholder="Mô tả chủ đề..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-primary-modern" onclick="createDiscussion()">Tạo thảo luận</button>
            </div>
        </div>
    </div>
</div>

<!-- Quiz Detail Modal -->
<div class="modal fade" id="quizDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary" id="quizDetailTitle">Chi tiết Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" id="quizDetailTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="quiz-questions-tab" data-bs-toggle="pill" data-bs-target="#quiz-questions-pane" type="button">Danh sách câu hỏi</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="quiz-submissions-tab" data-bs-toggle="pill" data-bs-target="#quiz-submissions-pane" type="button">Kết quả làm bài</button>
                    </li>
                </ul>
                <div class="tab-content" id="quizDetailTabContent">
                    <!-- Tab questions -->
                    <div class="tab-pane fade show active" id="quiz-questions-pane">
                        <div id="quizQuestionsList" class="d-flex flex-column gap-3">
                            <div class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải câu hỏi...</div>
                        </div>
                    </div>
                    <!-- Tab submissions -->
                    <div class="tab-pane fade" id="quiz-submissions-pane">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sinh viên</th>
                                        <th>Email</th>
                                        <th>Điểm số</th>
                                        <th>Thời gian nộp</th>
                                    </tr>
                                </thead>
                                <tbody id="quizSubmissionsList">
                                    <tr><td colspan="4" class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải bài làm...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Discussion Detail Modal -->
<div class="modal fade" id="discussionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary" id="discussDetailTitle">Chủ đề thảo luận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-3">
                <!-- Mô tả chủ đề -->
                <div class="p-3 bg-light rounded" id="discussDetailContent" style="white-space: pre-wrap; font-size: 0.9rem; border-left: 4px solid #2563eb;">
                    Đang tải...
                </div>
                
                <!-- Danh sách bình luận -->
                <div>
                    <h6 class="fw-bold mb-2">Bình luận lớp học</h6>
                    <div id="discussRepliesList" class="d-flex flex-column gap-2 overflow-auto" style="max-height: 300px; padding-right: 5px;">
                        <div class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
                    </div>
                </div>

                <!-- Nhập bình luận mới -->
                <div class="mt-2">
                    <label class="fw-semibold small mb-1">Gửi bình luận mới</label>
                    <div class="input-group">
                        <textarea class="form-control" id="newReplyContent" rows="2" placeholder="Nhập nội dung thảo luận với cả lớp..."></textarea>
                        <button class="btn btn-primary-modern px-3" onclick="submitReply()"><i class="bi bi-send-fill fs-5"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
const SESSION_ID = <?= $sessionId ?>;
const SESSION_STATUS = '<?= $s['status'] ?>';
let questionCount = 0;
let currentTab = 'attendance';
let sessionDetailInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    loadAttendanceList();
    
    if (SESSION_STATUS === 'active') {
        sessionDetailInterval = setInterval(() => {
            if (currentTab === 'attendance') loadAttendanceList();
            else if (currentTab === 'quiz') loadQuizDiscuss();
            else if (currentTab === 'log') loadChangeLogs();
            else if (currentTab === 'complaints') loadComplaints();
        }, 10000);
    }

    // Lắng nghe sự kiện reset modal
    const quizModal = document.getElementById('createQuizModal');
    if (quizModal) {
        quizModal.addEventListener('show.bs.modal', () => {
            document.getElementById('quizTitle').value = '';
            document.getElementById('quizDuration').value = '10';
            document.getElementById('questionList').innerHTML = '';
            questionCount = 0;
            addQuestion(); // Thêm mặc định 1 câu hỏi ban đầu
        });
    }

    const discussModal = document.getElementById('createDiscussModal');
    if (discussModal) {
        discussModal.addEventListener('show.bs.modal', () => {
            document.getElementById('discussTitle').value = '';
            document.getElementById('discussDesc').value = '';
        });
    }
});

window.addEventListener('beforeunload', () => {
    if (sessionDetailInterval) clearInterval(sessionDetailInterval);
});

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('#sessionTabs .nav-link').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content-sect').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
    if (tab === 'quiz') loadQuizDiscuss();
    if (tab === 'log') loadChangeLogs();
    if (tab === 'complaints') loadComplaints();
}

function loadAttendanceList() {
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}`)
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('attendanceTableBody');
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Lớp chưa có sinh viên</td></tr>';
                return;
            }
            const statusMap = { present: ['bg-success','Có mặt'], late: ['bg-warning text-dark','Đi muộn'], absent: ['bg-danger','Vắng'], excused: ['bg-info text-dark','Có phép'] };
            tbody.innerHTML = res.data.map((r, i) => {
                const [cls, txt] = r.status ? (statusMap[r.status] || ['bg-secondary','?']) : ['bg-light text-muted border','Chưa ĐD'];
                const editBtn = SESSION_STATUS !== 'completed' ? '' : `<button class="btn btn-sm btn-outline-primary" onclick="openEditAtt(${r.student_id},'${r.full_name}','${r.status||''}')"><i class="bi bi-pencil"></i></button>`;
                const manualBtn = SESSION_STATUS !== 'completed' ? `<div class="att-btn-group d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm ${r.status==='present'?'btn-success':'btn-outline-success'}" onclick="quickUpdateAtt(${r.student_id},'present')"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm ${r.status==='late'?'btn-warning':'btn-outline-warning'}" onclick="quickUpdateAtt(${r.student_id},'late')"><i class="bi bi-clock"></i></button>
                    <button class="btn btn-sm ${r.status==='absent'?'btn-danger':'btn-outline-danger'}" onclick="quickUpdateAtt(${r.student_id},'absent')"><i class="bi bi-x"></i></button>
                </div>` : editBtn;
                return `<tr>
                    <td class="small text-muted">${i+1}</td>
                    <td><div class="fw-semibold small">${r.full_name}</div><div class="text-muted" style="font-size:0.72rem;">${r.email}</div></td>
                    <td><span class="badge ${cls} small">${txt}</span></td>
                    <td class="small text-muted">${r.method_name || '--'}</td>
                    <td class="small text-muted">${r.recorded_at ? r.recorded_at.slice(11,16) : '--'}</td>
                    <td>${manualBtn}</td>
                </tr>`;
            }).join('');
        });
}

function startAttendance() {
    const method = document.getElementById('attendMethodSelect').value;
    const minutes = parseInt(document.getElementById('attendMinutes').value);
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/start`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ method, minutes })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') setTimeout(() => location.reload(), 1000);
    });
}

function stopAttendance() {
    if (!confirm('Đóng điểm danh? Sinh viên chưa điểm danh sẽ bị đánh dấu Vắng.')) return;
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/stop`, { method: 'POST' })
        .then(r => r.json()).then(res => {
            showToast(res.message, res.status === 'success' ? 'success' : 'danger');
            if (res.status === 'success') setTimeout(() => location.reload(), 1500);
        });
}

function quickUpdateAtt(studentId, status) {
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId, status })
    }).then(r => r.json()).then(res => {
        if (res.status === 'success') loadAttendanceList();
        else showToast(res.message, 'danger');
    });
}

function markAllPresent() {
    const tbody = document.getElementById('attendanceTableBody');
    const studentIds = [...tbody.querySelectorAll('[onclick*="quickUpdateAtt"]')].map(b => parseInt(b.getAttribute('onclick').match(/quickUpdateAtt\((\d+)/)?.[1])).filter(Boolean);
    const unique = [...new Set(studentIds)];
    if (!unique.length) return;
    Promise.all(unique.map(sid => fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/update`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: sid, status: 'present' })
    })).then(r => r.json())).then(() => { loadAttendanceList(); showToast('Đã đánh dấu tất cả có mặt', 'success'); }).catch(() => loadAttendanceList());
}

function openEditAtt(studentId, name, currentStatus) {
    document.getElementById('editAttStudentId').value = studentId;
    document.getElementById('editAttStudentName').textContent = name;
    document.getElementById('editAttStatus').value = currentStatus;
    document.getElementById('editAttOldStatus').value = currentStatus;
    document.getElementById('editAttReason').value = '';
    new bootstrap.Modal(document.getElementById('editAttModal')).show();
}

function saveAttendanceEdit() {
    const studentId = document.getElementById('editAttStudentId').value;
    const newStatus = document.getElementById('editAttStatus').value;
    const oldStatus = document.getElementById('editAttOldStatus').value;
    const reason = document.getElementById('editAttReason').value.trim();
    if (!reason) { showToast('Vui lòng nhập lý do thay đổi.', 'warning'); return; }
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/edit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId, status: newStatus, old_status: oldStatus, reason })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') { bootstrap.Modal.getInstance(document.getElementById('editAttModal'))?.hide(); loadAttendanceList(); }
    });
}

function loadQuizDiscuss() {
    fetch(`${BASE_URL}/api/teacher/session/${SESSION_ID}/quizzes-discussions`)
        .then(r => r.json()).then(res => {
            const el = document.getElementById('quizDiscussContent');
            if (res.status !== 'success') { el.innerHTML = '<div class="text-muted text-center py-4">Lỗi tải dữ liệu</div>'; return; }
            const quizzes = res.data?.quizzes || [], discs = res.data?.discussions || [];
            let html = '';
            
            if (quizzes.length > 0) {
                html += '<div class="fw-semibold mb-2">Quiz</div>';
                html += quizzes.map(q => {
                    const now = new Date();
                    const startTime = new Date(q.start_time.replace(' ', 'T'));
                    const endTime = new Date(q.end_time.replace(' ', 'T'));
                    let statusText = '';
                    let statusCls = '';
                    if (now < startTime) {
                        statusText = 'Chưa mở';
                        statusCls = 'bg-secondary';
                    } else if (now > endTime) {
                        statusText = 'Đã đóng';
                        statusCls = 'bg-danger';
                    } else {
                        statusText = 'Đang hoạt động';
                        statusCls = 'bg-success';
                    }
                    
                    const totalStuds = res.data.total_students || 0;

                    return `<div class="d-flex justify-content-between align-items-center p-3 mb-2 session-item-card" style="background:#f8fafc;border-radius:10px;border:1px solid var(--border-color); cursor:pointer;" onclick="viewQuizDetail(${q.id}, '${encodeURIComponent(q.title)}')">
                        <div>
                            <div class="fw-semibold small text-primary">${q.title || 'Quiz'}</div>
                            <div class="text-muted small mt-1">
                                ${q.question_count || 0} câu | ${q.duration_minutes} phút 
                                &nbsp;|&nbsp; Đã nộp: <span class="fw-bold">${q.submission_count || 0}</span>/${totalStuds} SV
                                &nbsp;|&nbsp; <span class="badge ${statusCls} text-white">${statusText}</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteQuiz(${q.id})"><i class="bi bi-trash"></i></button>
                    </div>`;
                }).join('');
            }
            
            if (discs.length > 0) {
                html += '<div class="fw-semibold mb-2 mt-3">Thảo luận</div>';
                html += discs.map(d => `<div class="d-flex justify-content-between align-items-center p-3 mb-2 session-item-card" style="background:#f8fafc;border-radius:10px;border:1px solid var(--border-color); cursor:pointer;" onclick="viewDiscussionDetail(${d.id}, '${encodeURIComponent(d.title)}', '${encodeURIComponent(d.content || d.description || '')}')">
                    <div>
                        <div class="fw-semibold small text-primary">${d.title}</div>
                        <div class="text-muted small mt-1">${d.reply_count || 0} bình luận &nbsp;|&nbsp; Người tạo: ${d.creator_name || 'GV'}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteDiscussion(${d.id})"><i class="bi bi-trash"></i></button>
                </div>`).join('');
            }
            
            if (!html) html = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-3"></i>Chưa có quiz hay thảo luận nào</div>';
            el.innerHTML = html;
        });
}

function deleteQuiz(quizId) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài Quiz này không? Toàn bộ bài làm của sinh viên cũng sẽ bị xóa.')) return;
    fetch(`${BASE_URL}/api/teacher/quizzes/${quizId}`, {
        method: 'DELETE'
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') loadQuizDiscuss();
    });
}

function deleteDiscussion(discussId) {
    if (!confirm('Bạn có chắc chắn muốn xóa chủ đề thảo luận này không?')) return;
    fetch(`${BASE_URL}/api/teacher/discussions/${discussId}`, {
        method: 'DELETE'
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') loadQuizDiscuss();
    });
}

let activeQuizId = null;
let activeDiscussId = null;

function viewQuizDetail(quizId, quizTitleEncoded) {
    activeQuizId = quizId;
    const title = decodeURIComponent(quizTitleEncoded);
    document.getElementById('quizDetailTitle').textContent = 'Chi tiết Quiz: ' + title;
    
    // Mở modal
    const modal = new bootstrap.Modal(document.getElementById('quizDetailModal'));
    modal.show();
    
    // Reset các tab
    document.getElementById('quizQuestionsList').innerHTML = '<div class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải câu hỏi...</div>';
    document.getElementById('quizSubmissionsList').innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải bài làm...</td></tr>';
    
    // Switch về tab 1
    const triggerEl = document.querySelector('#quizDetailTabs button[id="quiz-questions-tab"]');
    if (triggerEl) {
        const tabInstance = bootstrap.Tab.getOrCreateInstance(triggerEl);
        tabInstance.show();
    }

    // Tải câu hỏi
    fetch(`${BASE_URL}/api/teacher/quizzes/${quizId}/questions`)
        .then(r => r.json())
        .then(res => {
            const listEl = document.getElementById('quizQuestionsList');
            if (res.status !== 'success' || !res.data || res.data.length === 0) {
                listEl.innerHTML = '<div class="text-muted text-center py-3">Không có câu hỏi nào.</div>';
                return;
            }
            listEl.innerHTML = res.data.map((q, idx) => {
                const getOptionCls = (opt) => q.correct_option === opt ? 'text-success fw-bold' : '';
                const getOptionIcon = (opt) => q.correct_option === opt ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' : '<i class="bi bi-circle text-muted me-1"></i>';
                return `<div class="p-3 border rounded">
                    <div class="fw-bold mb-2">Câu ${idx + 1}: ${q.question_text}</div>
                    <div class="row g-2">
                        <div class="col-md-6 ${getOptionCls('A')}">${getOptionIcon('A')} A. ${q.option_a}</div>
                        <div class="col-md-6 ${getOptionCls('B')}">${getOptionIcon('B')} B. ${q.option_b}</div>
                        <div class="col-md-6 ${getOptionCls('C')}">${getOptionIcon('C')} C. ${q.option_c}</div>
                        <div class="col-md-6 ${getOptionCls('D')}">${getOptionIcon('D')} D. ${q.option_d}</div>
                    </div>
                </div>`;
            }).join('');
        }).catch(err => {
            document.getElementById('quizQuestionsList').innerHTML = '<div class="text-danger text-center py-3">Lỗi tải câu hỏi.</div>';
        });

    // Tải bài nộp
    fetch(`${BASE_URL}/api/quizzes/${quizId}/submissions`)
        .then(r => r.json())
        .then(res => {
            const listEl = document.getElementById('quizSubmissionsList');
            if (res.status !== 'success' || !res.data || res.data.length === 0) {
                listEl.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">Chưa có sinh viên nào nộp bài.</td></tr>';
                return;
            }
            listEl.innerHTML = res.data.map(sub => {
                return `<tr>
                    <td><div class="fw-semibold">${sub.student_name}</div></td>
                    <td class="small text-muted">${sub.student_email}</td>
                    <td><span class="badge bg-primary fs-6">${sub.score} / 10</span></td>
                    <td class="small text-muted">${sub.submitted_at ? sub.submitted_at.slice(0, 16) : '--'}</td>
                </tr>`;
            }).join('');
        }).catch(err => {
            document.getElementById('quizSubmissionsList').innerHTML = '<tr><td colspan="4" class="text-danger text-center py-3">Lỗi tải kết quả làm bài.</td></tr>';
        });
}

function viewDiscussionDetail(discId, titleEncoded, descEncoded) {
    activeDiscussId = discId;
    const title = decodeURIComponent(titleEncoded);
    const desc = decodeURIComponent(descEncoded);
    
    document.getElementById('discussDetailTitle').textContent = 'Thảo luận: ' + title;
    document.getElementById('discussDetailContent').textContent = desc || 'Không có mô tả.';
    document.getElementById('newReplyContent').value = '';
    
    // Mở modal
    const modal = new bootstrap.Modal(document.getElementById('discussionDetailModal'));
    modal.show();
    
    loadDiscussionReplies(discId);
}

function loadDiscussionReplies(discId) {
    document.getElementById('discussRepliesList').innerHTML = '<div class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải bình luận...</div>';
    
    fetch(`${BASE_URL}/api/discussions/${discId}/replies`)
        .then(r => r.json())
        .then(res => {
            const listEl = document.getElementById('discussRepliesList');
            if (res.status !== 'success' || !res.data || res.data.length === 0) {
                listEl.innerHTML = '<div class="text-muted text-center py-4">Chưa có bình luận nào. Hãy bắt đầu thảo luận!</div>';
                return;
            }
            listEl.innerHTML = res.data.map(r => {
                const isTeacher = r.user_role === 'teacher';
                const roleBadge = isTeacher ? '<span class="badge bg-danger ms-1" style="font-size:0.6rem;">GV</span>' : '<span class="badge bg-secondary ms-1" style="font-size:0.6rem;">SV</span>';
                const cardBg = isTeacher ? 'bg-primary-subtle border-primary-subtle' : 'bg-light';
                return `<div class="p-2.5 rounded ${cardBg} border mb-1" style="padding: 10px 14px;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small text-dark">${r.user_name} ${roleBadge}</span>
                        <span class="text-muted small" style="font-size:0.75rem;">${r.created_at ? r.created_at.slice(11, 16) : ''}</span>
                    </div>
                    <div class="small text-dark">${r.content}</div>
                </div>`;
            }).join('');
            
            setTimeout(() => {
                listEl.scrollTop = listEl.scrollHeight;
            }, 50);
        }).catch(err => {
            document.getElementById('discussRepliesList').innerHTML = '<div class="text-danger text-center py-3">Lỗi tải bình luận.</div>';
        });
}

function submitReply() {
    const content = document.getElementById('newReplyContent').value.trim();
    if (!content) { showToast('Vui lòng nhập nội dung bình luận.', 'warning'); return; }
    
    fetch(`${BASE_URL}/api/discussions/${activeDiscussId}/reply`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') {
            document.getElementById('newReplyContent').value = '';
            loadDiscussionReplies(activeDiscussId);
            loadQuizDiscuss();
        }
    });
}


function addQuestion() {
    questionCount++;
    const div = document.createElement('div');
    div.className = 'mb-3 p-3 border rounded';
    div.id = `q${questionCount}`;
    div.innerHTML = `<div class="d-flex justify-content-between mb-2"><strong class="small">Câu ${questionCount}</strong><button class="btn btn-sm btn-outline-danger" onclick="this.closest('[id]').remove()"><i class="bi bi-trash"></i></button></div>
        <input type="text" class="form-control mb-2 q-text" placeholder="Nội dung câu hỏi...">
        ${['A','B','C','D'].map(opt => `<div class="input-group mb-1"><span class="input-group-text fw-bold">${opt}</span><input type="text" class="form-control opt-${opt.toLowerCase()}" placeholder="Đáp án ${opt}..."></div>`).join('')}
        <div class="mt-2"><label class="small fw-semibold">Đáp án đúng: </label>
        <select class="form-select form-select-sm correct-ans" style="display:inline-block;width:80px;margin-left:5px;"><option>A</option><option>B</option><option>C</option><option>D</option></select></div>`;
    document.getElementById('questionList').appendChild(div);
}

function createQuiz() {
    const title = document.getElementById('quizTitle').value.trim();
    const duration = parseInt(document.getElementById('quizDuration').value);
    const questions = [...document.querySelectorAll('#questionList > div')].map(q => ({
        question_text: q.querySelector('.q-text')?.value.trim(),
        option_a: q.querySelector('.opt-a')?.value.trim(),
        option_b: q.querySelector('.opt-b')?.value.trim(),
        option_c: q.querySelector('.opt-c')?.value.trim(),
        option_d: q.querySelector('.opt-d')?.value.trim(),
        correct_answer: q.querySelector('.correct-ans')?.value
    })).filter(q => q.question_text);
    
    if (!title || questions.length === 0) { showToast('Vui lòng nhập tiêu đề và ít nhất 1 câu hỏi.', 'warning'); return; }
    
    fetch(`${BASE_URL}/api/teacher/quizzes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: SESSION_ID, title, duration_minutes: duration, questions })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') { bootstrap.Modal.getInstance(document.getElementById('createQuizModal'))?.hide(); loadQuizDiscuss(); }
    });
}

function createDiscussion() {
    const title = document.getElementById('discussTitle').value.trim();
    const desc = document.getElementById('discussDesc').value.trim();
    if (!title) { showToast('Vui lòng nhập tiêu đề thảo luận.', 'warning'); return; }
    fetch(`${BASE_URL}/api/teacher/discussions`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: SESSION_ID, title, description: desc })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') { bootstrap.Modal.getInstance(document.getElementById('createDiscussModal'))?.hide(); loadQuizDiscuss(); }
    });
}

function loadChangeLogs() {
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/logs`)
        .then(r => r.json()).then(res => {
            const el = document.getElementById('changeLogList');
            if (!res.data || res.data.length === 0) { el.innerHTML = '<div class="text-muted text-center py-4">Chưa có thay đổi nào</div>'; return; }
            el.innerHTML = res.data.map(l => `<div class="log-item"><div class="d-flex justify-content-between"><strong class="small">${l.student_name}</strong><span class="text-muted small">${l.changed_at?.slice(0,16)}</span></div>
                <div class="small text-muted">Thay đổi bởi: <strong>${l.changed_by_name}</strong> | <span class="badge bg-warning text-dark">${l.old_status}</span> → <span class="badge bg-success">${l.new_status}</span></div>
                ${l.reason ? `<div class="small text-muted mt-1">Lý do: ${l.reason}</div>` : ''}</div>`).join('');
        });
}

function loadComplaints() {
    fetch(`${BASE_URL}/api/attendance/${SESSION_ID}/complaints`)
        .then(r => r.json()).then(res => {
            const el = document.getElementById('complaintsList');
            if (!res.data || res.data.length === 0) { el.innerHTML = '<div class="text-muted text-center py-4">Không có khiếu nại nào</div>'; return; }
            el.innerHTML = res.data.map(c => `<div class="log-item mb-3"><div class="d-flex justify-content-between align-items-start">
                <div><strong class="small">${c.student_name}</strong> <span class="text-muted small">${c.student_email}</span></div>
                <span class="badge ${c.status === 'resolved' ? 'bg-success' : 'bg-warning text-dark'}">${c.status === 'resolved' ? 'Đã xử lý' : 'Chưa xử lý'}</span>
            </div>
            <div class="small mt-1">${c.description}</div>
            ${c.status === 'pending' ? `<div class="mt-2 d-flex gap-2"><input type="text" class="form-control form-control-sm" id="note_${c.id}" placeholder="Ghi chú xử lý..."><button class="btn btn-sm btn-success" onclick="resolveComplaint(${c.id})">Xử lý</button></div>` : `<div class="text-muted small mt-1">Ghi chú: ${c.teacher_note || '--'}</div>`}
            <div class="text-muted small mt-1">${c.created_at?.slice(0,16)}</div></div>`).join('');
        });
}

function resolveComplaint(id) {
    const note = document.getElementById('note_'+id)?.value.trim() || '';
    fetch(`${BASE_URL}/api/attendance/complaint/${id}/resolve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ teacher_note: note })
    }).then(r => r.json()).then(res => {
        showToast(res.message, res.status === 'success' ? 'success' : 'danger');
        if (res.status === 'success') loadComplaints();
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php';
?>
