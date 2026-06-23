<?php ob_start(); ?>
<!-- Nạp Chart.js CDN để vẽ các biểu đồ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.student-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
}
.stat-icon.red {
    background: #fee2e2;
    color: #ef4444;
}
.upcoming-session-item {
    border-radius: 12px;
    border: 1px solid var(--border-color);
    padding: 14px 16px;
    background: var(--bg-surface);
    transition: all 0.2s;
}
.upcoming-session-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}
.notification-item {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}
.notification-item:hover {
    background: rgba(59, 130, 246, 0.04);
}
</style>

<div class="d-flex flex-column gap-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--text-main);">Học tập của tôi</h2>
            <div class="text-muted" id="greetingDate">Đang tải...</div>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold rounded-pill">Sinh viên</span>
        </div>
    </div>

    <div class="row g-4">
        <!-- 1. Số buổi đã học -->
        <div class="col-md-4 col-sm-4 col-12">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon blue me-3">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Số buổi học trong kỳ</div>
                    <div class="value fw-bold fs-4" id="statTotalSessions">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Học kỳ hiện tại</div>
                </div>
            </div>
        </div>
        <!-- 2. Số buổi vắng -->
        <div class="col-md-4 col-sm-4 col-12">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon red me-3">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Số buổi vắng</div>
                    <div class="value fw-bold fs-4 text-danger" id="statAbsentSessions">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Không phép</div>
                </div>
            </div>
        </div>
        <!-- 3. CPI hiện tại -->
        <div class="col-md-4 col-sm-4 col-12">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon orange me-3">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">CPI Tích lũy</div>
                    <div class="value fw-bold fs-4" id="statCpiScore">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Điểm tương tác</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI THÔNG TIN CÁ NHÂN & ĐIỂM DANH NHANH -->
    <div class="row g-4">
        <!-- Thông tin cá nhân -->
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <h5 class="card-title-modern mb-4"><i class="bi bi-person-card text-primary me-2"></i>Thông tin sinh viên</h5>
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div id="studentAvatarWrap">
                        <div class="skeleton" style="width:80px; height:80px; border-radius:50%;"></div>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="fw-bold mb-1 text-dark" id="lblStudentName">Đang tải...</h4>
                        <p class="text-muted mb-2 small" id="lblStudentEmail">--</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-light text-dark border px-2 py-1" id="lblStudentCode">MSSV: --</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted small">Chuyên ngành:</span>
                        <strong class="text-dark ms-1" id="lblStudentMajor">--</strong>
                    </div>
                    <div>
                        <span class="text-muted small">Khóa đào tạo:</span>
                        <strong class="text-primary ms-1" id="lblStudentCohort">--</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Khối Điểm danh nhanh (Nếu đang có session active) -->
        <div class="col-lg-6">
            <div class="card-modern h-100" id="quickAttendanceCard">
                <h5 class="card-title-modern mb-3"><i class="bi bi-qr-code-scan text-success me-2"></i>Điểm danh nhanh</h5>
                <div id="quickAttendanceContainer">
                    <div class="text-center py-4 text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tải dữ liệu điểm danh...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- MÔN HỌC & LỊCH HỌC SẮP DIỄN RA -->
    <div class="row g-4">
        <!-- Môn học (Shortcut danh sách rút gọn) -->
        <div class="col-lg-5">
            <div class="card-modern h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title-modern mb-0"><i class="bi bi-book-fill text-primary me-2"></i>Học phần của tôi</h5>
                </div>
                <div id="myCoursesList" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                    <div class="text-center py-4 text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
                </div>
            </div>
        </div>

        <!-- Lịch học sắp tới -->
        <div class="col-lg-7">
            <div class="card-modern h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title-modern mb-0"><i class="bi bi-calendar-event text-success me-2"></i>Lịch học sắp tới (7 ngày)</h5>
                    <a href="<?= BASE_URL ?>/student/schedule" class="btn btn-sm btn-outline-secondary">Xem lịch tuần</a>
                </div>
                <div class="d-flex flex-column gap-2" id="upcomingSessionsContainer">
                    <div class="text-center py-4 text-muted small">Không có lịch học nào sắp tới.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CẢNH BÁO CÁ NHÂN & THÔNG BÁO -->
    <div class="row g-4">
        <!-- Cảnh báo học tập -->
        <div class="col-lg-6">
            <div class="card-modern h-100 border-start border-danger border-4" id="alertsCard" style="display: none;">
                <h5 class="card-title-modern text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Cảnh báo học tập</h5>
                <div class="d-flex flex-column gap-2" id="alertsList">
                    <!-- Loaded dynamically -->
                </div>
            </div>
            <div class="card-modern h-100 border-start border-success border-4" id="normalCard">
                <h5 class="card-title-modern text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Tình trạng học tập</h5>
                <p class="text-muted small">Bạn đang đáp ứng tốt yêu cầu chuyên cần và tương tác. Hãy tiếp tục duy trì kết quả học tập tốt này nhé!</p>
                <div class="text-success fw-bold small"><i class="bi bi-shield-check"></i> Trạng thái: An toàn</div>
            </div>
        </div>

        <!-- Thông báo -->
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <h5 class="card-title-modern mb-3"><i class="bi bi-bell-fill text-warning me-2"></i>Thông báo từ nhà trường & GV</h5>
                <div class="d-flex flex-column gap-2" id="notificationsContainer">
                    <div class="text-center py-4 text-muted small">Không có thông báo mới.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ HỌC TẬP THEO MÔN -->
    <div class="card-modern p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Biểu đồ phân tích học phần</h5>
            <div style="width: 250px;">
                <select class="form-select" id="chartCourseFilter" onchange="loadCourseChartsData()">
                    <!-- Tải động danh sách môn học -->
                </select>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- 1. Tỷ lệ đi học (Hình tròn - Doughnut) -->
            <div class="col-12 col-lg-6">
                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                    <h6 class="fw-bold text-dark mb-3 text-center"><i class="bi bi-pie-chart text-success me-1"></i>Thống kê chuyên cần lớp học</h6>
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartStudentAttendanceDoughnut"></canvas>
                        <div id="chartStudentAttendanceEmpty" class="position-absolute top-50 start-50 translate-middle text-muted small d-none" style="pointer-events: none;">Chưa có dữ liệu</div>
                    </div>
                </div>
            </div>
            
            <!-- 2. CPI theo thời gian (Đường - Line) -->
            <div class="col-12 col-lg-6">
                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                    <h6 class="fw-bold text-dark mb-3 text-center"><i class="bi bi-award text-warning me-1"></i>Chỉ số tham gia CPI qua các buổi</h6>
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartStudentCpiTrendsLine"></canvas>
                        <div id="chartStudentCpiEmpty" class="position-absolute top-50 start-50 translate-middle text-muted small d-none" style="pointer-events: none;">Chưa có dữ liệu</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let chartStudentAttendanceDoughnut = null;
let chartStudentCpiTrendsLine = null;
let dashboardData = null;
let activeInterval = null;
let currentPollingTime = 60000;

document.addEventListener('DOMContentLoaded', () => {
    // Hiển thị ngày tháng
    const now = new Date();
    document.getElementById('greetingDate').textContent = now.toLocaleDateString('vi-VN', {weekday:'long',year:'numeric',month:'long',day:'numeric'});

    loadDashboard();
});

window.addEventListener('beforeunload', () => {
    if (activeInterval) clearInterval(activeInterval);
});

function setupDashboardPolling(hasActiveSession) {
    const nextTime = hasActiveSession ? 10000 : 60000;
    if (currentPollingTime === nextTime && activeInterval) {
        return;
    }
    
    if (activeInterval) {
        clearInterval(activeInterval);
    }
    
    currentPollingTime = nextTime;
    activeInterval = setInterval(() => {
        loadDashboard(true);
    }, nextTime);
}

function loadDashboard(isReload = false) {
    fetch(`${BASE_URL}/api/student/dashboard`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                dashboardData = res.data;
                updateUI(res.data, isReload);
            }
        })
        .catch(err => console.error(err));
}

function updateUI(data, isReload) {
    // 1. Thống kê nhanh
    document.getElementById('statTotalSessions').textContent = data.total_sessions;
    document.getElementById('statAbsentSessions').textContent = data.absent_count;
    if (data.avg_cpi !== null && data.avg_cpi !== undefined) {
        document.getElementById('statCpiScore').textContent = data.avg_cpi;
        document.getElementById('statCpiScore').classList.remove('fs-6');
        document.getElementById('statCpiScore').classList.add('fs-4');
    } else {
        document.getElementById('statCpiScore').textContent = 'Chưa có dữ liệu';
        document.getElementById('statCpiScore').classList.remove('fs-4');
        document.getElementById('statCpiScore').classList.add('fs-6');
    }

    // 2. Thông tin cá nhân
    const info = data.student_info;
    document.getElementById('lblStudentName').textContent = info.full_name;
    document.getElementById('lblStudentEmail').textContent = info.email;
    document.getElementById('lblStudentCode').textContent = `MSSV: ${info.student_code}`;
    document.getElementById('lblStudentMajor').textContent = info.major_name || 'Chưa cập nhật';
    document.getElementById('lblStudentCohort').textContent = info.cohort || 'K65';

    const initial = encodeURIComponent(info.full_name);
    const avatarSrc = info.avatar_url || `https://ui-avatars.com/api/?name=${initial}&background=2563eb&color=fff&size=80`;
    document.getElementById('studentAvatarWrap').innerHTML = `<img src="${avatarSrc}" class="student-avatar" alt="Avatar">`;

    // 3. Cảnh báo học tập cá nhân
    const alertsCard = document.getElementById('alertsCard');
    const normalCard = document.getElementById('normalCard');
    const alertsList = document.getElementById('alertsList');
    
    let personalAlerts = [];
    if (data.courses && data.courses.length > 0) {
        data.courses.forEach(c => {
            const stats = c.stats;
            if (stats) {
                // Kiểm tra vắng quá 3 buổi (hoặc theo rule_absent_limit của môn đó)
                const limit = c.rule_absent_limit ? parseInt(c.rule_absent_limit) : 3;
                if (stats.absent > limit) {
                    personalAlerts.push(`Bạn đã nghỉ quá ${limit} buổi ở môn <strong>${c.code} - ${c.name}</strong> (đã nghỉ ${stats.absent} buổi).`);
                }
                
                // Kiểm tra CPI dưới ngưỡng (hoặc mặc định dưới 50.0)
                const cpiThreshold = c.rule_low_cpi_threshold ? parseFloat(c.rule_low_cpi_threshold) : 50.0;
                if (stats.cpi < cpiThreshold && stats.passed_sessions > 2) {
                    personalAlerts.push(`Chỉ số tham gia (CPI) của môn <strong>${c.code} - ${c.name}</strong> dưới ngưỡng an toàn (${stats.cpi}/${cpiThreshold}).`);
                }
            }
        });
    }

    if (personalAlerts.length > 0 || data.alerts.length > 0) {
        alertsCard.style.display = 'block';
        normalCard.style.display = 'none';
        
        let alertsHtml = '';
        personalAlerts.forEach(msg => {
            alertsHtml += `
                <div class="alert alert-danger mb-2 py-2 small d-flex gap-2 align-items-center border-0" style="background:#fee2e2; color:#b91c1c;">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>${msg}</div>
                </div>
            `;
        });
        
        data.alerts.forEach(a => {
            alertsHtml += `
                <div class="alert alert-warning mb-2 py-2 small d-flex gap-2 align-items-center border-0" style="background:#fef3c7; color:#b45309;">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <div><strong>${a.course_code}:</strong> ${a.message}</div>
                </div>
            `;
        });
        alertsList.innerHTML = alertsHtml;
    } else {
        alertsCard.style.display = 'none';
        normalCard.style.display = 'block';
    }

    // 4. Khối Điểm danh nhanh (Nếu đang học có điểm danh mở)
    const quickAttContainer = document.getElementById('quickAttendanceContainer');
    const active = data.active_sessions || [];
    
    setupDashboardPolling(active.length > 0);
    
    if (active.length === 0) {
        quickAttContainer.innerHTML = `
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-calendar-x fs-2 mb-2 d-block"></i>
                <span>Hiện không có buổi học nào đang mở điểm danh cho bạn.</span>
            </div>
        `;
    } else {
        const s = active[0]; // Lấy buổi học active đầu tiên
        
        // Tính thời gian còn lại từ server
        const diffMin = s.remaining_minutes;
        
        let innerHtml = `
            <div class="card p-3 border-0 bg-light" style="border-radius:12px;">
                <h6 class="fw-bold mb-1 text-dark">${s.course_name}</h6>
                <div class="text-muted small mb-2"><span class="fw-semibold text-secondary">${s.course_code}</span> | Phòng: ${s.room || 'N/A'} | GV: ${s.teacher_name || 'N/A'}</div>
        `;

        if (s.my_attendance) {
            const attStatusText = s.my_attendance === 'present' ? 'Có mặt' : 'Đi muộn';
            const attStatusClass = s.my_attendance === 'present' ? 'bg-success' : 'bg-warning text-dark';
            innerHtml += `
                <div class="alert alert-success mt-3 mb-0 d-flex align-items-center justify-content-between py-2 border-0">
                    <span class="small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Bạn đã điểm danh thành công!</span>
                    <span class="badge ${attStatusClass}">${attStatusText}</span>
                </div>
            `;
        } else if (s.attendance_status === 'da_dong' && (s.qr_token || s.attendance_code)) {
            innerHtml += `
                <div class="alert alert-danger mt-3 mb-0 py-2 border-0 small">
                    <i class="bi bi-clock-fill me-1"></i>Phiên điểm danh tự động đã hết hạn. Vui lòng báo giảng viên điểm danh thủ công.
                </div>
            `;
        } else {
            // Hiển thị form điểm danh dựa trên hình thức
            if (s.qr_token) {
                // Hình thức QR Code
                innerHtml += `
                    <div class="mt-3">
                        <label class="fw-semibold small mb-1 text-dark"><i class="bi bi-qr-code me-1"></i>Điểm danh QR Code (Nhập mã token)</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="inpQrToken" class="form-control form-control-sm" placeholder="Nhập mã token QR..." style="font-family:monospace;">
                            <button class="btn btn-sm btn-success fw-bold px-3" onclick="submitAttendance(${s.id}, 'qr')" style="border-radius:20px;">Xác nhận</button>
                        </div>
                        <div class="text-muted small mt-2" style="font-size:0.75rem;"><span class="badge bg-danger animate-pulse">Cổng mở QR</span> Hết hạn sau ${diffMin} phút.</div>
                    </div>
                `;
            } else if (s.attendance_code) {
                // Hình thức Nhập Code theo phút
                innerHtml += `
                    <div class="mt-3">
                        <label class="fw-semibold small mb-1 text-dark">🔢 Nhập mã điểm danh (6 chữ số)</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="inpAttCode" class="form-control form-control-sm text-center fw-bold" maxlength="6" placeholder="Mã 6 số..." style="font-size:1.1rem; letter-spacing:4px; max-width:140px;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <button class="btn btn-sm btn-primary fw-bold px-3" onclick="submitAttendance(${s.id}, 'code')" style="border-radius:20px;">Điểm danh</button>
                        </div>
                        <div class="text-muted small mt-2" style="font-size:0.75rem;"><span class="badge bg-danger animate-pulse">Cổng mở Code</span> Hết hạn sau ${diffMin} phút.</div>
                    </div>
                `;
            } else {
                // Điểm danh thủ công
                innerHtml += `
                    <div class="alert alert-info mt-3 mb-0 py-2 border-0 small">
                        <i class="bi bi-info-circle-fill me-1"></i>Buổi học này điểm danh Thủ công. Giảng viên sẽ tích trực tiếp cho bạn.
                    </div>
                `;
            }
        }
        innerHtml += `</div>`;
        quickAttContainer.innerHTML = innerHtml;
    }

    // 5. Học phần của tôi (Shortcut rút gọn)
    const coursesContainer = document.getElementById('myCoursesList');
    const courses = data.courses || [];
    if (courses.length === 0) {
        coursesContainer.innerHTML = `<div class="text-center py-4 text-muted small">Bạn chưa đăng ký lớp học nào.</div>`;
    } else {
        coursesContainer.innerHTML = courses.slice(0, 4).map(c => `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-bold text-dark small">${c.code} - ${c.class_code || ''}</div>
                    <div class="text-muted" style="font-size:0.78rem;">${c.name}</div>
                </div>
                <span class="badge bg-light text-secondary border small px-2 py-1 rounded" style="font-size:0.7rem;">${c.teacher_name || 'Giảng viên'}</span>
            </div>
        `).join('');
    }

    // 6. Lịch học sắp tới (7 ngày)
    const upcomingContainer = document.getElementById('upcomingSessionsContainer');
    const upcoming = data.upcoming_sessions || [];
    if (upcoming.length === 0) {
        upcomingContainer.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x fs-2 mb-2 d-block"></i>
                <span class="small">Không có lịch học nào diễn ra trong 7 ngày tới.</span>
            </div>
        `;
    } else {
        const days = ['CN','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
        upcomingContainer.innerHTML = upcoming.slice(0, 3).map(s => {
            const dt = new Date(s.session_date + 'T00:00:00');
            const attBadge = s.my_attendance
                ? `<span class="badge ${s.my_attendance === 'present' ? 'bg-success' : s.my_attendance === 'late' ? 'bg-warning text-dark' : 'bg-danger'} small">${s.my_attendance === 'present' ? 'Có mặt' : s.my_attendance === 'late' ? 'Đi muộn' : 'Vắng'}</span>`
                : `<span class="badge bg-secondary small">Chưa điểm danh</span>`;
            return `
                <div class="upcoming-session-item d-flex align-items-center gap-3">
                    <div class="text-center" style="min-width:48px;">
                        <div class="fw-bold text-primary fs-5" style="line-height:1.1;">${String(dt.getDate()).padStart(2,'0')}</div>
                        <div class="text-muted" style="font-size:0.7rem;">${days[dt.getDay()]}</div>
                    </div>
                    <div style="flex:1;">
                        <div class="fw-bold text-dark small mb-1">${s.course_name} (${s.course_code})</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <i class="bi bi-clock me-1"></i>${(s.start_time||'').slice(0,5)} - ${(s.end_time||'').slice(0,5)} | Phòng ${s.room || 'N/A'}
                        </div>
                    </div>
                    ${attBadge}
                </div>
            `;
        }).join('');
    }

    // 7. Thông báo
    const notiContainer = document.getElementById('notificationsContainer');
    const notis = data.notifications || [];
    if (notis.length === 0) {
        notiContainer.innerHTML = `<div class="text-center py-4 text-muted small">Không có thông báo mới.</div>`;
    } else {
        notiContainer.innerHTML = notis.map(n => {
            const time = n.created_at ? n.created_at.substring(5,16).replace('-', '/') : '';
            return `
                <div class="notification-item d-flex gap-2">
                    <div class="text-warning mt-1"><i class="bi bi-info-circle-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark small" style="font-size:0.85rem;">${n.title}</div>
                        <div class="text-muted small" style="font-size:0.75rem;">${n.message}</div>
                        <div class="text-end text-muted" style="font-size:0.65rem;">${time}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Nạp dropdown môn học biểu đồ
    const selectFilter = document.getElementById('chartCourseFilter');
    if (selectFilter && selectFilter.options.length === 0) {
        data.courses.forEach(c => {
            selectFilter.innerHTML += `<option value="${c.id}">${c.code} - ${c.name}</option>`;
        });
    }

    // Biểu đồ
    if (!isReload) {
        loadCourseChartsData();
    } else {
        loadCourseChartsData();
    }
}

function submitAttendance(sessionId, type) {
    let payload = { session_id: sessionId };
    
    if (type === 'qr') {
        const val = document.getElementById('inpQrToken').value.trim();
        if (!val) { showToast('Vui lòng nhập token QR.', 'warning'); return; }
        payload.qr_token = val;
    } else if (type === 'code') {
        const val = document.getElementById('inpAttCode').value.trim();
        if (!val || val.length !== 6) { showToast('Vui lòng nhập đúng mã 6 số.', 'warning'); return; }
        payload.code = val;
    }

    fetch(`${BASE_URL}/api/attendance/submit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadDashboard();
        } else {
            showToast(res.message || 'Lỗi điểm danh', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi máy chủ.', 'danger');
    });
}

function loadCourseChartsData() {
    const courseId = document.getElementById('chartCourseFilter').value;
    if (!courseId) return;

    fetch(`${BASE_URL}/api/student/dashboard/course-charts?course_id=${courseId}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                renderCourseCharts(res.data);
            }
        })
        .catch(err => console.error(err));
}

function renderCourseCharts(chartData) {
    // 1. Vẽ biểu đồ Doughnut
    const ctx1 = document.getElementById('chartStudentAttendanceDoughnut').getContext('2d');
    const att = chartData.attendance;
    
    const labels1 = ['Có mặt', 'Đi muộn', 'Vắng'];
    const data1 = [att.present, att.late, att.absent + att.excused];
    const colors1 = ['#10b981', '#f59e0b', '#ef4444'];

    if (chartStudentAttendanceDoughnut) chartStudentAttendanceDoughnut.destroy();
    
    const hasData = data1.some(v => v > 0);
    
    if (!hasData) {
        document.getElementById('chartStudentAttendanceEmpty').classList.remove('d-none');
    } else {
        document.getElementById('chartStudentAttendanceEmpty').classList.add('d-none');
    }
    
    let chartDataValues, chartLabels, chartColors;
    if (hasData) {
        chartDataValues = data1;
        chartLabels = labels1;
        chartColors = colors1;
    } else {
        // Vẽ 100% màu xám Chưa có dữ liệu, nhưng legend vẫn hiển thị đầy đủ các trạng thái
        chartDataValues = [0, 0, 0, 1];
        chartLabels = ['Có mặt', 'Đi muộn', 'Vắng', 'Chưa có dữ liệu'];
        chartColors = ['#10b981', '#f59e0b', '#ef4444', '#cbd5e1'];
    }

    chartStudentAttendanceDoughnut = new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartDataValues,
                backgroundColor: chartColors,
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        boxWidth: 12, 
                        padding: 15,
                        filter: function(item, chart) {
                            return item.text !== 'Chưa có dữ liệu';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 2. Vẽ biểu đồ đường xu hướng CPI
    const ctx2 = document.getElementById('chartStudentCpiTrendsLine').getContext('2d');
    
    const hasCpiData = chartData.cpi_trends && chartData.cpi_trends.length > 0 && chartData.cpi_trends.some(x => x.cpi_score !== null);
    if (!hasCpiData) {
        document.getElementById('chartStudentCpiEmpty').classList.remove('d-none');
    } else {
        document.getElementById('chartStudentCpiEmpty').classList.add('d-none');
    }

    let labels2 = chartData.cpi_trends.map(x => x.date.substring(5).replace('-', '/'));
    let data2 = chartData.cpi_trends.map(x => x.cpi_score);
    
    if (labels2.length === 0) {
        // Khung biểu đồ CPI mẫu khi rỗng để vẫn có lưới tọa độ đẹp mắt
        labels2 = ['Buổi 1', 'Buổi 2', 'Buổi 3', 'Buổi 4', 'Buổi 5'];
        data2 = [null, null, null, null, null];
    }

    if (chartStudentCpiTrendsLine) chartStudentCpiTrendsLine.destroy();
    chartStudentCpiTrendsLine = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: labels2,
            datasets: [{
                label: 'CPI của tôi',
                data: data2,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.08)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 100 }
            }
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
