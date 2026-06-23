<?php ob_start(); ?>
<!-- Nạp Chart.js CDN để vẽ các biểu đồ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.stat-icon.red {
    background: #fee2e2;
    color: #ef4444;
}
.active-session-item {
    border-left: 4px solid var(--success);
    background: var(--bg-surface);
    transition: all 0.2s ease;
}
.active-session-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
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
.btn-action-custom {
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 6px 16px;
}
</style>

<div class="d-flex flex-column gap-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--text-main);">Trang chủ</h2>
            <div class="text-muted">Chào mừng bạn trở lại, <span class="fw-medium text-dark" id="lblTeacherName">Giảng viên</span>! 👋</div>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold rounded-pill" id="lblSemester">Học kỳ hè</span>
        </div>
    </div>

    <!-- HÀNG THẺ THỐNG KÊ NHANH (CARDS, STYLE GIỐNG ADMIN) -->
    <div class="row g-4">
        <!-- 1. Số lớp phụ trách -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon green me-3">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Lớp học</div>
                    <div class="value fw-bold fs-4" id="statCoursesCount">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Đang dạy</div>
                </div>
            </div>
        </div>
        <!-- 2. Tổng số sinh viên -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon blue me-3">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Học viên</div>
                    <div class="value fw-bold fs-4" id="statTotalStudents">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Sĩ số quản lý</div>
                </div>
            </div>
        </div>
        <!-- 3. Buổi học hôm nay -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon orange me-3">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Lịch hôm nay</div>
                    <div class="value fw-bold fs-4" id="statTodaySessions">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Buổi học</div>
                </div>
            </div>
        </div>
        <!-- 4. Tình trạng điểm danh hôm nay -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon purple me-3">
                    <i class="bi bi-check-all"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Điểm danh</div>
                    <div class="value fw-bold fs-5" id="statTodayAttendanceRatio">--/--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Buổi hôm nay</div>
                </div>
            </div>
        </div>
        <!-- 5. Tỷ lệ chuyên cần trung bình -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon blue me-3">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">Chuyên cần TB</div>
                    <div class="value fw-bold fs-4" id="statAvgAttendance">--%</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Toàn bộ lớp</div>
                </div>
            </div>
        </div>
        <!-- 6. CPI trung bình -->
        <div class="col-md-4 col-xl-2 col-sm-6">
            <div class="card-modern d-flex align-items-center h-100 p-3">
                <div class="stat-icon orange me-3">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="stat-info flex-grow-1">
                    <div class="title" style="font-size: 0.75rem; color: var(--text-muted);">CPI Lớp TB</div>
                    <div class="value fw-bold fs-4" id="statAvgCpi">--</div>
                    <div class="trend text-muted" style="font-size: 0.65rem;"><i class="bi bi-dash"></i> Chỉ số tham gia</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI ĐIỂM DANH NHANH (NỔI BẬT) & PHIÊN ĐANG MỞ -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-modern h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon orange me-2" style="width: 36px; height: 36px; font-size: 1rem;"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h5 class="card-title-modern mb-0">Phiên điểm danh nhanh</h5>
                </div>
                <p class="text-muted small mb-4">Mở nhanh cổng điểm danh trực tuyến cho các buổi học đang diễn ra trong ngày.</p>
                <div id="ongoingSessionsContainer">
                    <div class="text-center py-4 text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tải dữ liệu...</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-modern h-100">
                <h5 class="card-title-modern mb-3"><i class="bi bi-broadcast text-danger me-2"></i>Cổng đang mở điểm danh</h5>
                <div class="d-flex flex-column gap-3" id="activeSessionsContainer">
                    <div class="text-center py-4 text-muted small">Không có cổng điểm danh nào đang mở.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- LỚP HỌC SẮP DIỄN RA & PHÍM TẮT ĐIỀU HƯỚNG NHANH -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <h5 class="card-title-modern mb-3"><i class="bi bi-calendar-event text-primary me-2"></i>Lịch dạy sắp tới</h5>
                <div id="upcomingSessionContainer">
                    <div class="text-center py-4 text-muted small"><i class="bi bi-arrow-repeat spin"></i> Đang tìm buổi học kế tiếp...</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <h5 class="card-title-modern mb-3"><i class="bi bi-grid-fill text-success me-2"></i>Lối tắt dịch vụ nhanh</h5>
                <div class="row g-2">
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/my-courses" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-journal-text fs-4"></i>
                            <span class="small fw-semibold text-dark">Lớp học của tôi</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/sessions" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-calendar3 fs-4"></i>
                            <span class="small fw-semibold text-dark">Xem lịch tuần</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/quizzes" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-chat-left-dots fs-4"></i>
                            <span class="small fw-semibold text-dark">Quiz & Thảo luận</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/engagement" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-award fs-4"></i>
                            <span class="small fw-semibold text-dark">Điểm tương tác</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/alerts" class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                            <span class="small fw-semibold text-dark">Cảnh báo lớp</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="<?= BASE_URL ?>/teacher/profile" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center gap-2 border-0 bg-light" style="border-radius:10px;">
                            <i class="bi bi-person-circle fs-4"></i>
                            <span class="small fw-semibold text-dark">Hồ sơ cá nhân</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SV CẦN CHÚ Ý & THÔNG BÁO -->
    <div class="row g-4">
        <!-- SV cần chú ý -->
        <div class="col-lg-7">
            <div class="card-modern h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title-modern mb-0"><i class="bi bi-shield-exclamation text-danger me-2"></i>Sinh viên cần chú ý (Top Risk)</h5>
                    <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold rounded" style="font-size:0.75rem;">Cảnh báo học tập</span>
                </div>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Họ tên</th>
                                <th>Lớp học phần</th>
                                <th class="text-center">Số buổi vắng</th>
                                <th class="text-center">CPI hiện tại</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="tblTopRiskBody">
                            <tr><td colspan="6" class="text-center py-4 text-muted small">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thông báo -->
        <div class="col-lg-5">
            <div class="card-modern h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title-modern mb-0"><i class="bi bi-bell-fill text-warning me-2"></i>Thông báo & Tin nhắn</h5>
                    <a href="<?= BASE_URL ?>/teacher/alerts" class="text-decoration-none small text-primary fw-semibold">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="d-flex flex-column gap-2" id="notificationsContainer">
                    <div class="text-center py-4 text-muted small">Không có thông báo mới nào.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ (CHART.JS) -->
    <div class="row g-4">
        <!-- 1. Tỷ lệ chuyên cần theo lớp -->
        <div class="col-lg-4">
            <div class="card-modern shadow-sm border-0 position-relative">
                <h5 class="card-title-modern mb-3"><i class="bi bi-bar-chart-line text-success me-2"></i>Tỷ lệ chuyên cần theo lớp</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartClassAttendance"></canvas>
                    <div id="chartClassAttendanceEmpty" class="position-absolute top-50 start-50 translate-middle text-muted small d-none" style="pointer-events: none;">Chưa có dữ liệu</div>
                </div>
            </div>
        </div>

        <!-- 2. Số SV đi học theo từng buổi -->
        <div class="col-lg-4">
            <div class="card-modern shadow-sm border-0 position-relative">
                <h5 class="card-title-modern mb-3"><i class="bi bi-graph-up text-primary me-2"></i>Số SV đi học theo từng buổi</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartSessionStudents"></canvas>
                    <div id="chartSessionStudentsEmpty" class="position-absolute top-50 start-50 translate-middle text-muted small d-none" style="pointer-events: none;">Chưa có dữ liệu</div>
                </div>
            </div>
        </div>

        <!-- 3. CPI trung bình theo thời gian -->
        <div class="col-lg-4">
            <div class="card-modern shadow-sm border-0 position-relative">
                <h5 class="card-title-modern mb-3"><i class="bi bi-award text-warning me-2"></i>CPI trung bình theo thời gian</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartCpiTrends"></canvas>
                    <div id="chartCpiTrendsEmpty" class="position-absolute top-50 start-50 translate-middle text-muted small d-none" style="pointer-events: none;">Chưa có dữ liệu</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let chartClassAttendance = null;
let chartSessionStudents = null;
let chartCpiTrends = null;
let activeInterval = null;
let currentPollingTime = 60000;

document.addEventListener('DOMContentLoaded', () => {
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
    fetch(`${BASE_URL}/api/teacher/dashboard`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                updateUI(res.data, isReload);
            }
        })
        .catch(err => console.error(err));
}

function updateUI(data, isReload) {
    const info = data.teacher_info;
    const stats = data.stats;
    
    // Header
    document.getElementById('lblTeacherName').textContent = info.full_name;
    document.getElementById('lblSemester').textContent = info.semester;
    
    // Hàng thống kê nhanh
    document.getElementById('statCoursesCount').textContent = info.courses_count;
    document.getElementById('statTotalStudents').textContent = stats.total_students;
    document.getElementById('statTodaySessions').textContent = stats.today_sessions_count;
    document.getElementById('statTodayAttendanceRatio').textContent = `${stats.today_attended_students}/${stats.today_total_students_needed}`;
    if (stats.avg_attendance_rate !== null) {
        document.getElementById('statAvgAttendance').textContent = `${stats.avg_attendance_rate}%`;
        document.getElementById('statAvgAttendance').classList.remove('fs-6');
        document.getElementById('statAvgAttendance').classList.add('fs-4');
    } else {
        document.getElementById('statAvgAttendance').textContent = 'Chưa có dữ liệu';
        document.getElementById('statAvgAttendance').classList.remove('fs-4');
        document.getElementById('statAvgAttendance').classList.add('fs-6');
    }
    if (stats.avg_cpi !== null) {
        document.getElementById('statAvgCpi').textContent = stats.avg_cpi;
        document.getElementById('statAvgCpi').classList.remove('fs-6');
        document.getElementById('statAvgCpi').classList.add('fs-4');
    } else {
        document.getElementById('statAvgCpi').textContent = 'Chưa có dữ liệu';
        document.getElementById('statAvgCpi').classList.remove('fs-4');
        document.getElementById('statAvgCpi').classList.add('fs-6');
    }
    
    // Nếu chưa học buổi nào, dòng trạng thái hiển thị màu xám trung tính "Chưa có dữ liệu đánh giá"
    if (stats.passed_sessions_count === 0) {
        document.getElementById('statAvgCpi').parentElement.querySelector('.trend').innerHTML = `<span class="text-muted"><i class="bi bi-info-circle-fill"></i> Chưa có dữ liệu đánh giá</span>`;
    } else if (stats.flagged_students_count > 0) {
        document.getElementById('statAvgCpi').parentElement.querySelector('.trend').innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> ${stats.flagged_students_count} SV cảnh báo</span>`;
    } else {
        document.getElementById('statAvgCpi').parentElement.querySelector('.trend').innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill"></i> Các lớp ổn định</span>`;
    }

    // Ongoing Sessions (Điểm danh nhanh)
    const ongoingContainer = document.getElementById('ongoingSessionsContainer');
    const ongoing = data.ongoing_sessions || [];
    if (ongoing.length === 0) {
        ongoingContainer.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x fs-2 mb-2 d-block"></i>
                <span class="small">Hiện không có lớp học nào đang diễn ra của bạn tại thời điểm này.</span>
            </div>
        `;
    } else {
        ongoingContainer.innerHTML = ongoing.map(s => `
            <div class="card p-3 mb-3 border-0 bg-light" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">${s.course_name}</h6>
                        <div class="text-muted small"><span class="fw-semibold text-secondary">${s.course_code}</span> | Phòng ${s.room || 'N/A'} | Ca ${s.period || 'N/A'}</div>
                        <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i> Giờ học: ${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="${BASE_URL}/teacher/session-detail/${s.id}?start=QR" class="btn btn-sm btn-success btn-action-custom"><i class="bi bi-qr-code me-1"></i>Tạo QR</a>
                        <a href="${BASE_URL}/teacher/session-detail/${s.id}?start=Code" class="btn btn-sm btn-primary btn-action-custom"><i class="bi bi-hash me-1"></i>Tạo Code 1p</a>
                        <a href="${BASE_URL}/teacher/session-detail/${s.id}?start=Manual" class="btn btn-sm btn-outline-secondary btn-action-custom bg-white"><i class="bi bi-check-square me-1"></i>Thủ công</a>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Active Sessions (Cổng điểm danh đang mở)
    const activeContainer = document.getElementById('activeSessionsContainer');
    const active = data.active_sessions || [];
    
    setupDashboardPolling(active.length > 0 || ongoing.length > 0);
    
    if (active.length === 0) {
        activeContainer.innerHTML = `<div class="text-center py-4 text-muted small"><i class="bi bi-info-circle me-1"></i>Không có cổng điểm danh nào đang mở.</div>`;
    } else {
        activeContainer.innerHTML = active.map(s => {
            let methodText = 'Thủ công';
            let icon = 'bi-check-square';
            if (s.qr_token) {
                methodText = 'QR Code';
                icon = 'bi-qr-code';
            } else if (s.attendance_code) {
                methodText = `Mã: ${s.attendance_code}`;
                icon = 'bi-hash';
            }
            
            // Tính thời gian còn lại từ server
            const diffMin = s.remaining_minutes;
            const timerText = s.attendance_status === 'dang_mo' ? `Còn ${diffMin} phút` : 'Hết hạn';

            return `
                <div class="active-session-item p-3 rounded d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark small">${s.course_code} - ${s.course_name}</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <i class="bi ${icon} me-1"></i>${methodText}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge ${s.attendance_status === 'dang_mo' ? 'bg-success' : 'bg-danger'} mb-1">${timerText}</span>
                        <a href="${BASE_URL}/teacher/session-detail/${s.id}" class="btn btn-xs btn-outline-primary d-block font-semibold" style="font-size:0.7rem; padding: 2px 8px; border-radius:10px;">Vào lớp</a>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Upcoming Session
    const upcomingContainer = document.getElementById('upcomingSessionContainer');
    const upcoming = data.reminders.upcoming_session;
    if (!upcoming) {
        upcomingContainer.innerHTML = `<div class="text-center py-4 text-muted small">Không có buổi học nào khác sắp diễn ra.</div>`;
    } else {
        const upDate = new Date(upcoming.session_date);
        const dateStr = `${upDate.getDate().toString().padStart(2,'0')}/${(upDate.getMonth()+1).toString().padStart(2,'0')}/${upDate.getFullYear()}`;
        upcomingContainer.innerHTML = `
            <div class="p-3 border-0 bg-light rounded" style="border-radius:12px;">
                <h6 class="fw-bold mb-1 text-dark">${upcoming.course_name}</h6>
                <div class="text-muted small mb-1"><span class="fw-semibold text-secondary">${upcoming.course_code}</span> | Phòng ${upcoming.room || 'N/A'}</div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <span class="text-muted small"><i class="bi bi-clock me-1"></i> ${dateStr} (${upcoming.start_time.substring(0,5)} - ${upcoming.end_time.substring(0,5)})</span>
                    <a href="${BASE_URL}/teacher/session-detail/${upcoming.id}" class="btn btn-sm btn-primary font-semibold px-3" style="border-radius:20px;">Quản lý</a>
                </div>
            </div>
        `;
    }

    // Top Risk Students
    const trBody = document.getElementById('tblTopRiskBody');
    const risks = data.top_risk_students || [];
    if (risks.length === 0) {
        if (stats.passed_sessions_count === 0) {
            trBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted small">Chưa có dữ liệu để đánh giá</td></tr>`;
        } else {
            trBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted small">Tất cả sinh viên đều đạt chỉ số chuyên cần & CPI tốt.</td></tr>`;
        }
    } else {
        trBody.innerHTML = risks.map(r => {
            const isDanger = r.cpi_score < 50;
            const cpiColor = isDanger ? 'text-danger fw-bold' : (r.cpi_score < 70 ? 'text-warning' : 'text-success');
            return `
                <tr>
                    <td><span class="fw-semibold">${r.student_code}</span></td>
                    <td>${r.student_name}</td>
                    <td>${r.course_code}</td>
                    <td class="text-center"><span class="badge ${r.absent_count >= 3 ? 'bg-danger' : 'bg-light text-dark'}">${r.absent_count}</span></td>
                    <td class="text-center"><span class="${cpiColor}">${r.cpi_score}</span></td>
                    <td class="text-end">
                        <a href="${BASE_URL}/teacher/alerts" class="btn btn-sm btn-outline-danger btn-action-custom" style="padding: 2px 10px; font-size:0.75rem;">Xử lý</a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Notifications
    const notiContainer = document.getElementById('notificationsContainer');
    const notis = data.notifications || [];
    if (notis.length === 0) {
        notiContainer.innerHTML = `<div class="text-center py-4 text-muted small">Không có thông báo nào gần đây.</div>`;
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

    // Render Charts
    if (!isReload) {
        initCharts(data.charts);
    } else {
        updateCharts(data.charts);
    }
}

function initCharts(chartData) {
    // 1. Tỷ lệ chuyên cần theo lớp
    const ctx1 = document.getElementById('chartClassAttendance').getContext('2d');
    const labels1 = chartData.attendance_by_course.map(x => x.course_code);
    const data1 = chartData.attendance_by_course.map(x => {
        const total = parseInt(x.total) || 0;
        const attended = parseInt(x.attended) || 0;
        return total > 0 ? Math.round((attended / total) * 100) : 100;
    });

    const hasAttendanceData = chartData.attendance_by_course && chartData.attendance_by_course.length > 0;
    if (!hasAttendanceData) {
        document.getElementById('chartClassAttendanceEmpty').classList.remove('d-none');
    } else {
        document.getElementById('chartClassAttendanceEmpty').classList.add('d-none');
    }

    if (chartClassAttendance) chartClassAttendance.destroy();
    chartClassAttendance = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels1,
            datasets: [{
                label: 'Chuyên cần (%)',
                data: data1,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 6,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 100, ticks: { callback: value => value + '%' } }
            }
        }
    });

    // 2. Số SV đi học theo từng buổi
    const ctx2 = document.getElementById('chartSessionStudents').getContext('2d');
    const labels2 = chartData.attended_by_session.map(x => `${x.course_code} (${x.session_date.substring(5)})`);
    const data2 = chartData.attended_by_session.map(x => x.attended_count);

    const hasSessionData = chartData.attended_by_session && chartData.attended_by_session.length > 0;
    if (!hasSessionData) {
        document.getElementById('chartSessionStudentsEmpty').classList.remove('d-none');
    } else {
        document.getElementById('chartSessionStudentsEmpty').classList.add('d-none');
    }

    if (chartSessionStudents) chartSessionStudents.destroy();
    chartSessionStudents = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: labels2,
            datasets: [{
                label: 'Sinh viên đi học',
                data: data2,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
                y: { beginAtZero: true }
            }
        }
    });

    // 3. CPI trung bình theo thời gian
    const ctx3 = document.getElementById('chartCpiTrends').getContext('2d');
    const labels3 = chartData.cpi_trends.map(x => x.date.substring(5).replace('-', '/'));
    const data3 = chartData.cpi_trends.map(x => x.avg_cpi);

    // Kiểm tra xem có dữ liệu thật không
    const hasCpiData = chartData.cpi_trends && chartData.cpi_trends.some(x => x.avg_cpi !== null);
    if (!hasCpiData) {
        document.getElementById('chartCpiTrendsEmpty').classList.remove('d-none');
    } else {
        document.getElementById('chartCpiTrendsEmpty').classList.add('d-none');
    }

    if (chartCpiTrends) chartCpiTrends.destroy();
    chartCpiTrends = new Chart(ctx3, {
        type: 'line',
        data: {
            labels: labels3,
            datasets: [{
                label: 'CPI Trung Bình',
                data: data3,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.08)',
                fill: true,
                tension: 0.3,
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

function updateCharts(chartData) {
    if (chartClassAttendance) {
        const hasAttendanceData = chartData.attendance_by_course && chartData.attendance_by_course.length > 0;
        if (!hasAttendanceData) {
            document.getElementById('chartClassAttendanceEmpty').classList.remove('d-none');
        } else {
            document.getElementById('chartClassAttendanceEmpty').classList.add('d-none');
        }
        chartClassAttendance.data.labels = chartData.attendance_by_course.map(x => x.course_code);
        chartClassAttendance.data.datasets[0].data = chartData.attendance_by_course.map(x => {
            const total = parseInt(x.total) || 0;
            const attended = parseInt(x.attended) || 0;
            return total > 0 ? Math.round((attended / total) * 100) : 100;
        });
        chartClassAttendance.update();
    }

    if (chartSessionStudents) {
        const hasSessionData = chartData.attended_by_session && chartData.attended_by_session.length > 0;
        if (!hasSessionData) {
            document.getElementById('chartSessionStudentsEmpty').classList.remove('d-none');
        } else {
            document.getElementById('chartSessionStudentsEmpty').classList.add('d-none');
        }
        chartSessionStudents.data.labels = chartData.attended_by_session.map(x => `${x.course_code} (${x.session_date.substring(5)})`);
        chartSessionStudents.data.datasets[0].data = chartData.attended_by_session.map(x => x.attended_count);
        chartSessionStudents.update();
    }

    if (chartCpiTrends) {
        const hasCpiData = chartData.cpi_trends && chartData.cpi_trends.some(x => x.avg_cpi !== null);
        if (!hasCpiData) {
            document.getElementById('chartCpiTrendsEmpty').classList.remove('d-none');
        } else {
            document.getElementById('chartCpiTrendsEmpty').classList.add('d-none');
        }
        chartCpiTrends.data.labels = chartData.cpi_trends.map(x => x.date.substring(5).replace('-', '/'));
        chartCpiTrends.data.datasets[0].data = chartData.cpi_trends.map(x => x.avg_cpi);
        chartCpiTrends.update();
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php';
?>
