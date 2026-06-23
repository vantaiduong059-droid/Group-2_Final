<?php ob_start(); ?>
<style>
.method-card { border-radius: 14px; border: 2px solid var(--border-color); padding: 20px; cursor: pointer; transition: all 0.2s; background: #fff; }
.method-card:hover, .method-card.selected { border-color: var(--primary); background: rgba(37,99,235,0.04); }
.method-card .method-icon { font-size: 2rem; margin-bottom: 10px; }
.big-code-display { font-size: 3.5rem; font-weight: 800; letter-spacing: 12px; color: var(--primary); font-family: 'Inter', monospace; }
.qr-display { max-width: 240px; margin: 0 auto; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid var(--border-color); }
.session-badge-active { background: rgba(16,185,129,0.12); color: #10b981; border-radius: 20px; padding: 4px 12px; font-size: 0.8rem; font-weight: 600; }
.complaint-box { background: var(--bg-light, #f8fafc); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); }
</style>

<div class="d-flex flex-column gap-4">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item active">Điểm danh</li>
        </ol></nav>
        <h3 class="fw-bold mb-0">Điểm danh</h3>
    </div>

    <!-- Active Sessions Banner -->
    <div id="activeSessionBanner" class="card-modern p-4" style="display:none; border-left: 4px solid #10b981;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="session-badge-active"><i class="bi bi-record-circle-fill me-1"></i>Đang mở điểm danh</div>
            <div>
                <div class="fw-bold" id="activeSessName">--</div>
                <div class="text-muted small" id="activeSessTime">--</div>
            </div>
            <div class="ms-auto">
                <button class="btn btn-success fw-semibold" onclick="openAttendanceModal()">
                    <i class="bi bi-qr-code-scan me-2"></i>Điểm danh ngay
                </button>
            </div>
        </div>
    </div>

    <!-- No active session message -->
    <div id="noActiveSess" class="card-modern p-4 text-center" style="display:none;">
        <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-bold text-muted">Hiện không có buổi học nào đang mở điểm danh</h5>
        <p class="text-muted">Bạn chỉ có thể điểm danh khi giảng viên bắt đầu buổi học. Kiểm tra lại sau!</p>
    </div>

    <!-- Attendance Form -->
    <div class="card-modern p-4" id="attendanceForm" style="display:none;">
        <h5 class="fw-bold mb-4">Thực hiện điểm danh</h5>
        
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="method-card selected" id="methodCodeCard" onclick="selectMethod('code')">
                    <div class="method-icon">🔢</div>
                    <div class="fw-bold">Nhập mã điểm danh</div>
                    <div class="text-muted small">Nhập mã 6 số do GV cung cấp</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="method-card" id="methodQrCard" onclick="selectMethod('qr')">
                    <div class="method-icon">📱</div>
                    <div class="fw-bold">Nhập Token QR</div>
                    <div class="text-muted small">Nhập token từ mã QR của GV</div>
                </div>
            </div>
        </div>

        <!-- Code input -->
        <div id="codeInputSection">
            <label class="fw-semibold mb-2">Mã điểm danh (6 số)</label>
            <div class="d-flex gap-2 mb-3">
                <input type="text" id="attendanceCodeInput" class="form-control" maxlength="6" placeholder="Nhập mã 6 số..."
                    style="font-size: 1.4rem; letter-spacing: 8px; font-weight: 700; text-align: center; max-width: 220px;"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <button class="btn btn-primary-modern fw-bold px-4" onclick="submitAttendance()">
                    <i class="bi bi-check2-circle me-2"></i>Điểm danh
                </button>
            </div>
        </div>

        <!-- QR Token input -->
        <div id="qrInputSection" style="display:none;">
            <label class="fw-semibold mb-2">Token QR (từ màn hình giảng viên)</label>
            <div class="d-flex gap-2 mb-3">
                <input type="text" id="qrTokenInput" class="form-control" placeholder="Dán token QR vào đây..." style="font-family: monospace;">
                <button class="btn btn-primary-modern fw-bold px-4" onclick="submitAttendanceQr()">
                    <i class="bi bi-qr-code-scan me-2"></i>Xác nhận
                </button>
            </div>
        </div>

        <div id="attendanceResult" class="mt-3"></div>
    </div>

    <!-- Complaint Section -->
    <div class="card-modern p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-chat-right-text me-2"></i>Gửi khiếu nại điểm danh</h6>
        <div class="text-muted small mb-3">Nếu bạn có mặt nhưng bị ghi là vắng, hãy gửi khiếu nại để giảng viên xem xét.</div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Chọn buổi học</label>
                <select class="form-select" id="complaintSessionSelect">
                    <option value="">-- Chọn buổi học có vấn đề --</option>
                    <?php foreach ($myCourses ?? [] as $c): ?>
                    <!-- Sessions will be loaded via JS -->
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Mô tả vấn đề</label>
                <input type="text" class="form-control" id="complaintDesc" placeholder="Ví dụ: Tôi có mặt nhưng mã hết hạn...">
            </div>
            <div class="col-12">
                <button class="btn btn-outline-warning fw-semibold" onclick="submitComplaint()">
                    <i class="bi bi-send me-2"></i>Gửi khiếu nại
                </button>
            </div>
        </div>
        <div id="complaintResult" class="mt-2"></div>
    </div>

    <!-- Own attendance history mini -->
    <div class="card-modern p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Lịch sử điểm danh gần đây</h6>
            <a href="<?= BASE_URL ?>/student/history" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        </div>
        <div id="recentAttList">
            <div class="text-center py-3 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
        </div>
    </div>
</div>

<?php
$myCourseIds = array_column($myCourses ?? [], 'id');
$myCoursesJson = json_encode($myCourses ?? []);
?>
<script>
let currentSessionId = null;
let selectedMethod = 'code';
const myCourses = <?= $myCoursesJson ?>;

document.addEventListener('DOMContentLoaded', () => {
    loadActiveSessions();
    loadRecentAttendance();
    loadComplaintSessions();
});

function selectMethod(m) {
    selectedMethod = m;
    document.getElementById('methodCodeCard').classList.toggle('selected', m === 'code');
    document.getElementById('methodQrCard').classList.toggle('selected', m === 'qr');
    document.getElementById('codeInputSection').style.display = m === 'code' ? 'block' : 'none';
    document.getElementById('qrInputSection').style.display = m === 'qr' ? 'block' : 'none';
}

function loadActiveSessions() {
    fetch(`${BASE_URL}/api/student/active-session`)
        .then(r => r.json()).then(res => {
            if (res.status === 'success' && res.data.length > 0) {
                const s = res.data[0];
                currentSessionId = s.id;
                document.getElementById('activeSessName').textContent = `${s.course_name} - ${s.course_code}`;
                document.getElementById('activeSessTime').textContent = `${s.session_date} | ${(s.start_time||'').slice(0,5)} - ${(s.end_time||'').slice(0,5)} | ${s.room || ''}`;
                
                if (s.my_attendance) {
                    document.getElementById('activeSessionBanner').style.display = 'none';
                    document.getElementById('noActiveSess').style.display = 'block';
                    document.getElementById('noActiveSess').innerHTML = `<i class="bi bi-check-circle-fill fs-1 text-success d-block mb-3"></i><h5 class="fw-bold text-success">Bạn đã điểm danh thành công!</h5><p class="text-muted">Buổi: ${s.course_name} | Trạng thái: <strong>${s.my_attendance === 'present' ? 'Có mặt' : 'Đi muộn'}</strong></p>`;
                } else if (s.attendance_status === 'da_dong') {
                    document.getElementById('activeSessionBanner').style.display = 'none';
                    document.getElementById('noActiveSess').style.display = 'block';
                    document.getElementById('noActiveSess').innerHTML = `<i class="bi bi-clock-fill fs-1 text-danger d-block mb-3"></i><h5 class="fw-bold text-danger">Phiên điểm danh tự động đã hết hạn</h5><p class="text-muted">Buổi: ${s.course_name} | Vui lòng báo giảng viên điểm danh thủ công.</p>`;
                } else {
                    document.getElementById('activeSessionBanner').style.display = 'block';
                    document.getElementById('attendanceForm').style.display = 'block';
                }
            } else {
                document.getElementById('noActiveSess').style.display = 'block';
            }
        }).catch(() => { document.getElementById('noActiveSess').style.display = 'block'; });
}

function openAttendanceModal() {
    document.getElementById('attendanceForm').scrollIntoView({ behavior: 'smooth' });
}

function submitAttendance() {
    const code = document.getElementById('attendanceCodeInput').value.trim();
    if (!code || code.length !== 6) { showToast('Vui lòng nhập đúng mã 6 số.', 'warning'); return; }
    if (!currentSessionId) { showToast('Không tìm thấy buổi học đang mở.', 'danger'); return; }

    fetch(`${BASE_URL}/api/attendance/submit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: currentSessionId, code: code })
    }).then(r => r.json()).then(res => {
        const div = document.getElementById('attendanceResult');
        if (res.status === 'success') {
            div.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>' + res.message + '</div>';
            showToast(res.message, 'success');
            setTimeout(() => loadActiveSessions(), 1500);
        } else {
            div.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>' + res.message + '</div>';
        }
    });
}

function submitAttendanceQr() {
    const token = document.getElementById('qrTokenInput').value.trim();
    if (!token) { showToast('Vui lòng nhập token QR.', 'warning'); return; }
    if (!currentSessionId) { showToast('Không tìm thấy buổi học đang mở.', 'danger'); return; }

    fetch(`${BASE_URL}/api/attendance/submit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: currentSessionId, qr_token: token })
    }).then(r => r.json()).then(res => {
        const div = document.getElementById('attendanceResult');
        if (res.status === 'success') {
            div.innerHTML = '<div class="alert alert-success">' + res.message + '</div>';
            showToast(res.message, 'success');
        } else {
            div.innerHTML = '<div class="alert alert-danger">' + res.message + '</div>';
        }
    });
}

function loadRecentAttendance() {
    fetch(`${BASE_URL}/api/student/history`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            const list = res.data.attendance?.slice(0, 8) || [];
            const el = document.getElementById('recentAttList');
            if (list.length === 0) { el.innerHTML = '<div class="text-muted small text-center py-3">Chưa có lịch sử điểm danh</div>'; return; }
            const statusMap = { present: ['bg-success','Có mặt'], late: ['bg-warning text-dark','Đi muộn'], absent: ['bg-danger','Vắng'], null: ['bg-danger','Vắng'] };
            el.innerHTML = `<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Ngày</th><th>Lớp học</th><th>Giờ</th><th>Trạng thái</th><th>Hình thức</th></tr></thead><tbody>
                ${list.map(r => { 
                    const [cls,txt] = statusMap[r.status] || statusMap[null]; 
                    const isVang = (!r.status || r.status === 'absent');
                    const hinhThuc = isVang ? '--' : (r.method_name || '--');
                    return `<tr><td class="small">${r.session_date}</td><td class="small fw-semibold">${r.course_code}</td><td class="small">${(r.start_time||'').slice(0,5)}</td><td><span class="badge ${cls} small">${txt}</span></td><td class="small text-muted">${hinhThuc}</td></tr>`; 
                }).join('')}
            </tbody></table></div>`;
        }).catch(() => {});
}

function loadComplaintSessions() {
    fetch(`${BASE_URL}/api/student/sessions`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            const sel = document.getElementById('complaintSessionSelect');
            res.data.filter(s => s.status === 'completed').forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = `${s.course_code} - ${s.session_date} (${(s.start_time||'').slice(0,5)})`;
                sel.appendChild(o);
            });
        }).catch(() => {});
}

function submitComplaint() {
    const sessionId = document.getElementById('complaintSessionSelect').value;
    const desc = document.getElementById('complaintDesc').value.trim();
    if (!sessionId) { showToast('Vui lòng chọn buổi học.', 'warning'); return; }
    if (!desc) { showToast('Vui lòng mô tả vấn đề.', 'warning'); return; }

    fetch(`${BASE_URL}/api/attendance/complaint`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId, description: desc })
    }).then(r => r.json()).then(res => {
        document.getElementById('complaintResult').innerHTML = `<div class="alert ${res.status === 'success' ? 'alert-success' : 'alert-danger'} py-2 small">${res.message}</div>`;
        if (res.status === 'success') { document.getElementById('complaintDesc').value = ''; document.getElementById('complaintSessionSelect').value = ''; }
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
