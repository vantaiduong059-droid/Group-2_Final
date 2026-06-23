<?php ob_start(); ?>

<!-- Styles cho giao diện Lịch học tuần -->
<style>
    :root {
        --schedule-grid-color: rgba(0, 0, 0, 0.035);
        --cell-empty-bg: #fafafa;
        --card-normal-bg: #eff6ff; /* Xanh nhẹ */
        --card-normal-border: #bfdbfe;
        --card-normal-text: #1e3a8a;
        --card-exam-bg: #fef9c3; /* Vàng nhẹ */
        --card-exam-border: #fef08a;
        --card-exam-text: #713f12;
    }

    .schedule-header-controls {
        background: #ffffff;
        padding: 12px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-control-schedule {
        font-weight: 600;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 0.85rem;
        padding: 5px 12px;
        transition: all 0.15s ease;
    }
    
    .btn-control-schedule:hover {
        background: #f8fafc;
        color: #0284c7;
        border-color: #94a3b8;
    }

    .btn-control-schedule.active {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
    }

    .schedule-table {
        border-collapse: collapse;
        width: 100%;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid #cbd5e1;
    }

    .schedule-table th {
        background: #0284c7;
        color: #ffffff;
        font-weight: 600;
        text-align: center;
        padding: 12px 8px;
        border: 1px solid #0369a1;
        font-size: 0.95rem;
    }

    .schedule-table th.day-header {
        min-width: 130px;
    }

    .schedule-table td {
        border: 1px solid #e2e8f0;
        vertical-align: top;
        padding: 8px;
        position: relative;
    }

    .shift-col {
        background: #f8fafc;
        width: 60px;
        text-align: center;
        vertical-align: middle !important;
        font-weight: 700;
        color: #0369a1;
        font-size: 1.1rem;
        border-right: 2px solid #cbd5e1 !important;
    }

    .schedule-cell-grid {
        background-color: var(--cell-empty-bg);
        background-image: 
            linear-gradient(var(--schedule-grid-color) 1px, transparent 1px),
            linear-gradient(90deg, var(--schedule-grid-color) 1px, transparent 1px);
        background-size: 15px 15px;
        min-height: 200px;
        height: 100%;
        transition: background-color 0.2s ease;
        padding: 4px;
        border-radius: 4px;
    }

    .schedule-cell-grid:hover {
        background-color: #f8fafc;
    }

    /* Thẻ Buổi học (Session Card) */
    .session-item-card {
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        font-size: 0.8rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        border-left: 5px solid;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    .session-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }

    .session-card-normal {
        background-color: var(--card-normal-bg);
        border: 1px solid var(--card-normal-border);
        border-left-color: #2563eb;
        color: var(--card-normal-text);
    }

    .session-card-exam {
        background-color: var(--card-exam-bg);
        border: 1px solid var(--card-exam-border);
        border-left-color: #eab308;
        color: var(--card-exam-text);
    }

    /* Thẻ khi SV đã có mặt, đi muộn, hoặc vắng mặt */
    .session-item-card.att-present {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-left: 5px solid #16a34a;
        color: #14532d;
    }

    .session-item-card.att-late {
        background-color: #fffbeb;
        border: 1px solid #fef3c7;
        border-left: 5px solid #d97706;
        color: #78350f;
    }

    .session-item-card.att-absent {
        background-color: #fef2f2;
        border: 1px solid #fee2e2;
        border-left: 5px solid #dc2626;
        color: #7f1d1d;
    }

    .session-course-title {
        font-weight: 700;
        font-size: 0.88rem;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .session-meta-line {
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
    }

    .session-meta-line i {
        opacity: 0.7;
        font-size: 0.8rem;
    }

    .session-note-box {
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px dashed rgba(0, 0, 0, 0.08);
        font-style: italic;
        font-weight: 500;
        font-size: 0.72rem;
    }

    .filter-radio-group {
        background-color: #f1f5f9;
        padding: 3px;
        border-radius: 30px;
        display: flex;
        align-items: center;
    }

    .filter-radio-group label {
        cursor: pointer;
        padding: 4px 14px;
        border-radius: 30px;
        transition: all 0.2s ease;
        font-weight: 600;
        font-size: 0.82rem;
        color: #475569;
        border: none;
    }

    .filter-radio-group input[type="radio"]:checked + label {
        background-color: #ffffff;
        color: #0284c7;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .dot-filter {
        font-size: 1.1rem;
        margin-right: 4px;
        vertical-align: middle;
        display: inline-block;
        line-height: 1;
    }
    .dot-all { color: #64748b; }
    .dot-study { color: #2563eb; }
    .dot-exam { color: #eab308; }

    /* CSS cho chế độ Thu nhỏ bảng */
    .schedule-table.schedule-compact {
        table-layout: fixed !important;
        width: 100% !important;
    }
    .schedule-table.schedule-compact th.day-header {
        min-width: unset !important;
        width: 13.5% !important;
        font-size: 0.8rem !important;
        padding: 6px 4px !important;
    }
    .schedule-table.schedule-compact th:first-child {
        width: 55px !important;
    }
    .schedule-table.schedule-compact td {
        padding: 4px !important;
    }
    .schedule-table.schedule-compact .shift-col {
        font-size: 0.85rem !important;
        width: 55px !important;
    }
    .schedule-table.schedule-compact .schedule-cell-grid {
        min-height: 130px !important;
        background-size: 10px 10px !important;
    }
    .schedule-table.schedule-compact .session-item-card {
        padding: 6px 8px !important;
        margin-bottom: 6px !important;
        font-size: 0.72rem !important;
        border-left-width: 3px !important;
    }
    .schedule-table.schedule-compact .session-course-title {
        font-size: 0.78rem !important;
        margin-bottom: 2px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .schedule-table.schedule-compact .session-meta-line {
        margin-bottom: 1px !important;
        font-size: 0.68rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* CSS cho chế độ Toàn màn hình */
    #scheduleWrapper.schedule-fullscreen-mode {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 2000 !important;
        background: #f8fafc !important;
        padding: 20px !important;
        overflow-y: auto !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 15px !important;
    }

    #scheduleWrapper.schedule-fullscreen-mode .schedule-table {
        border-radius: 8px !important;
    }
</style>

<div class="d-flex flex-column gap-4" id="scheduleWrapper">
    <!-- Breadcrumb & Header -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active">Lịch học</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Lịch học của tôi</h3>
        </div>
        <!-- Bộ lọc lớp học -->
        <div class="d-flex gap-2 align-items-center">
            <label class="fw-semibold text-muted small mb-0 d-none d-sm-inline">Lớp học phần:</label>
            <select class="form-select form-select-sm" id="courseFilter" style="min-width:180px; border-radius: 20px;">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($myCourses ?? [] as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code'] . ' - ' . $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tiêu đề & Công cụ điều phối -->
    <div class="schedule-header-controls">
        <!-- Cột trái: Bộ lọc loại lịch -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 filter-radio-group">
                <div class="form-check form-check-inline m-0 p-0">
                    <input class="form-check-input d-none" type="radio" name="scheduleFilter" id="filterAll" value="all" checked>
                    <label class="form-check-label text-nowrap" for="filterAll"><span class="dot-filter dot-all">●</span> Tất cả</label>
                </div>
                <div class="form-check form-check-inline m-0 p-0">
                    <input class="form-check-input d-none" type="radio" name="scheduleFilter" id="filterStudy" value="study">
                    <label class="form-check-label text-nowrap" for="filterStudy"><span class="dot-filter dot-study">●</span> Lịch học</label>
                </div>
                <div class="form-check form-check-inline m-0 p-0">
                    <input class="form-check-input d-none" type="radio" name="scheduleFilter" id="filterExam" value="exam">
                    <label class="form-check-label text-nowrap" for="filterExam"><span class="dot-filter dot-exam">●</span> Lịch thi</label>
                </div>
            </div>
        </div>

        <!-- Cột phải: Dải công cụ điều hướng và thao tác -->
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            <!-- Datepicker -->
            <div class="input-group input-group-sm" style="max-width: 145px;">
                <span class="input-group-text bg-white border-end-0 pe-1" style="border-radius: 6px 0 0 6px;"><i class="bi bi-calendar3 text-primary" style="font-size: 0.85rem;"></i></span>
                <input type="date" class="form-control border-start-0 ps-1 fw-semibold text-primary" id="scheduleDatePicker" style="border-radius: 0 6px 6px 0; font-size: 0.85rem; padding: 4px 6px;">
            </div>

            <!-- Nhóm điều hướng tuần -->
            <div class="btn-group btn-group-sm" style="border-radius: 6px; overflow: hidden;">
                <button class="btn btn-control-schedule" onclick="navigateWeek(-1)" title="Tuần trước" style="padding: 5px 10px;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-control-schedule fw-semibold" onclick="goToCurrentWeek()" style="padding: 5px 12px;">
                    Hiện tại
                </button>
                <button class="btn btn-control-schedule" onclick="navigateWeek(1)" title="Tuần sau" style="padding: 5px 10px;">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <!-- Nhóm công cụ bảng -->
            <div class="btn-group btn-group-sm" style="border-radius: 6px; overflow: hidden;">
                <button class="btn btn-control-schedule" id="btnCompactToggle" onclick="toggleCompactSchedule()" title="Thu nhỏ/Mở rộng bảng" style="padding: 5px 12px;">
                    <i class="bi bi-arrows-angle-contract"></i><span class="ms-1 d-none d-md-inline" id="textCompactToggle">Thu nhỏ</span>
                </button>
                <button class="btn btn-control-schedule" onclick="printSchedule()" title="In lịch học" style="padding: 5px 10px;">
                    <i class="bi bi-printer"></i>
                </button>
                <button class="btn btn-control-schedule" id="btnFullscreenToggle" onclick="toggleFullscreenSchedule()" title="Toàn màn hình" style="padding: 5px 10px;">
                    <i class="bi bi-fullscreen"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Bảng Lịch học tuần -->
    <div class="card-modern p-0 overflow-hidden" id="printArea">
        <div class="table-responsive">
            <table class="schedule-table" id="scheduleTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">Ca học</th>
                        <th class="day-header" id="col-day2">Thứ 2<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day3">Thứ 3<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day4">Thứ 4<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day5">Thứ 5<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day6">Thứ 6<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day7">Thứ 7<br><small class="text-white-50">--/--</small></th>
                        <th class="day-header" id="col-day8">Chủ nhật<br><small class="text-white-50">--/--</small></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Ca Sáng -->
                    <tr>
                        <td class="shift-col">SÁNG</td>
                        <td><div class="schedule-cell-grid" id="cell-morning-2"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-3"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-4"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-5"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-6"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-7"></div></td>
                        <td><div class="schedule-cell-grid" id="cell-morning-8"></div></td>
                    </tr>
                    <!-- Ca Chiều -->
                    <tr>
                        <td class="shift-col" style="border-top: 2px solid #cbd5e1;">CHIỀU</td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-2"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-3"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-4"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-5"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-6"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-7"></div></td>
                        <td style="border-top: 2px solid #cbd5e1;"><div class="schedule-cell-grid" id="cell-afternoon-8"></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Chi tiết Buổi học -->
<div class="modal fade" id="sessionDetailModal" tabindex="-1" aria-labelledby="sessionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="sessionDetailModalLabel">Chi tiết buổi học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="small text-muted fw-semibold text-uppercase" id="mCourseCode">--</div>
                        <h4 class="fw-bold text-primary mb-1" id="mCourseName">--</h4>
                        <div class="small text-secondary" id="mTeacherName">Giảng viên: --</div>
                    </div>
                    
                    <hr class="my-1 border-light">
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-muted mb-0.5"><i class="bi bi-calendar me-1"></i> Ngày học</div>
                            <div class="fw-semibold text-dark" id="mDate">--</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted mb-0.5"><i class="bi bi-clock me-1"></i> Giờ học</div>
                            <div class="fw-semibold text-dark" id="mTime">--</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted mb-0.5"><i class="bi bi-geo-alt me-1"></i> Phòng học</div>
                            <div class="fw-semibold text-dark" id="mRoom">--</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted mb-0.5"><i class="bi bi-tag me-1"></i> Phân loại</div>
                            <div class="fw-semibold" id="mType">Lịch học</div>
                        </div>
                    </div>
                    
                    <hr class="my-1 border-light">

                    <div>
                        <div class="small text-muted mb-2"><i class="bi bi-qr-code-scan me-1"></i> Điểm danh cá nhân</div>
                        <div class="p-2.5 rounded border d-flex align-items-center justify-content-between" id="mAttendanceBox" style="background:var(--bg-main); padding: 10px 15px;">
                            <span class="fw-bold" id="mAttendanceStatus">Chưa học</span>
                            <span class="text-muted small" id="mAttendanceTime">--</span>
                        </div>
                    </div>
                    
                    <div id="mNoteBox" style="display:none;">
                        <div class="small text-muted mb-1"><i class="bi bi-sticky me-1"></i> Ghi chú</div>
                        <div class="small text-dark p-2 bg-light rounded" id="mNote"></div>
                    </div>
                </div>
            </div>
            <div class="modal-header bg-light border-top-0 pt-0">
                <div class="w-100 d-flex gap-2" id="modalActions">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = new Date();
let selectedType = 'all';
let allSessions = [];

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('scheduleDatePicker').value = formatDateDb(currentDate);
    
    loadSchedule();
    setupEventListeners();
});

// Lắng nghe sự kiện đổi học kỳ trên Topbar
window.addEventListener('semesterChanged', () => {
    loadSchedule();
});

function setupEventListeners() {
    // Datepicker
    const datePicker = document.getElementById('scheduleDatePicker');
    datePicker.addEventListener('change', () => {
        if (datePicker.value) {
            currentDate = new Date(datePicker.value);
            loadSchedule();
        }
    });

    // Lọc loại lịch
    const radioFilters = document.querySelectorAll('input[name="scheduleFilter"]');
    radioFilters.forEach(radio => {
        radio.addEventListener('change', () => {
            selectedType = radio.value;
            renderSchedule();
        });
    });

    // Lọc lớp học phần
    document.getElementById('courseFilter').addEventListener('change', () => {
        renderSchedule();
    });
}

function getMonday(d) {
    d = new Date(d);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

function formatDateDb(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function formatDateVi(d) {
    return String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear();
}

function navigateWeek(weeks) {
    currentDate.setDate(currentDate.getDate() + (weeks * 7));
    document.getElementById('scheduleDatePicker').value = formatDateDb(currentDate);
    loadSchedule();
}

function goToCurrentWeek() {
    currentDate = new Date();
    document.getElementById('scheduleDatePicker').value = formatDateDb(currentDate);
    loadSchedule();
}

function toggleCompactSchedule() {
    const table = document.getElementById('scheduleTable');
    const toggleBtn = document.getElementById('btnCompactToggle');
    
    table.classList.toggle('schedule-compact');
    
    if (table.classList.contains('schedule-compact')) {
        toggleBtn.innerHTML = '<i class="bi bi-arrows-angle-expand"></i><span class="ms-1 d-none d-md-inline" id="textCompactToggle">Mở rộng</span>';
    } else {
        toggleBtn.innerHTML = '<i class="bi bi-arrows-angle-contract"></i><span class="ms-1 d-none d-md-inline" id="textCompactToggle">Thu nhỏ</span>';
    }
}

function toggleFullscreenSchedule() {
    const wrapper = document.getElementById('scheduleWrapper');
    const icon = document.getElementById('btnFullscreenToggle').querySelector('i');
    
    wrapper.classList.toggle('schedule-fullscreen-mode');
    
    if (wrapper.classList.contains('schedule-fullscreen-mode')) {
        icon.className = 'bi bi-fullscreen-exit';
    } else {
        icon.className = 'bi bi-fullscreen';
    }
}

function printSchedule() {
    window.print();
}

function loadSchedule() {
    const courseId = document.getElementById('courseFilter').value;
    const url = `${BASE_URL}/api/student/sessions${courseId ? '?course_id=' + courseId : ''}`;
    
    const monday = getMonday(currentDate);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                allSessions = res.data;
                renderSchedule();
            }
        })
        .catch(err => console.error(err));
}

function renderSchedule() {
    const courseFilter = document.getElementById('courseFilter').value;
    
    // Lọc theo loại và lớp
    let filtered = allSessions;
    if (selectedType !== 'all') {
        filtered = filtered.filter(s => s.session_type === selectedType);
    }
    if (courseFilter) {
        filtered = filtered.filter(s => s.course_id == courseFilter);
    }

    const monday = getMonday(currentDate);
    const weekDays = [];
    const dayLabels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
    
    // 1. Cập nhật các cột header (ngày)
    for (let i = 0; i < 7; i++) {
        const day = new Date(monday);
        day.setDate(monday.getDate() + i);
        weekDays.push(day);
        
        const colHeader = document.getElementById(`col-day${i+2}`);
        if (colHeader) {
            colHeader.innerHTML = `${dayLabels[i]}<br><small class="text-white-50">${day.getDate()}/${day.getMonth()+1}</small>`;
        }
    }

    // 2. Làm sạch các ô
    const cells = document.querySelectorAll('.schedule-cell-grid');
    cells.forEach(c => c.innerHTML = '');

    // 3. Đưa dữ liệu vào ô tương ứng
    filtered.forEach(s => {
        const sessDate = new Date(s.session_date + 'T00:00:00');
        const dayIndex = sessDate.getDay();
        const colNum = dayIndex === 0 ? 8 : dayIndex + 1;

        // Chỉ đưa vào bảng nếu ngày học nằm trong tuần đang xem
        const isMorning = s.start_time < '12:00:00';
        const cellId = isMorning ? `cell-morning-${colNum}` : `cell-afternoon-${colNum}`;
        const cell = document.getElementById(cellId);
        
        // Kiểm tra xem session có đúng trong khoảng từ monday đến sunday không
        const sessTime = sessDate.getTime();
        const startOfWeek = new Date(monday);
        startOfWeek.setHours(0, 0, 0, 0);
        const endOfWeek = new Date(monday);
        endOfWeek.setDate(monday.getDate() + 6);
        endOfWeek.setHours(23, 59, 59, 999);
        
        if (cell && sessTime >= startOfWeek.getTime() && sessTime <= endOfWeek.getTime()) {
            // Xác định class điểm danh cá nhân
            let attClass = '';
            let statusText = '';
            if (s.my_attendance === 'present') {
                attClass = 'att-present';
                statusText = '<span class="badge bg-success" style="font-size:0.6rem;">Có mặt</span>';
            } else if (s.my_attendance === 'late') {
                attClass = 'att-late';
                statusText = '<span class="badge bg-warning text-dark" style="font-size:0.6rem;">Muộn</span>';
            } else if (s.my_attendance === 'absent') {
                attClass = 'att-absent';
                statusText = '<span class="badge bg-danger" style="font-size:0.6rem;">Vắng</span>';
            } else {
                attClass = s.session_type === 'exam' ? 'session-card-exam' : 'session-card-normal';
                statusText = s.status === 'completed' ? '<span class="badge bg-secondary" style="font-size:0.6rem;">Hoàn thành</span>' : (s.status === 'active' ? '<span class="badge bg-success animate-pulse" style="font-size:0.6rem;">Đang diễn ra</span>' : '<span class="badge bg-primary" style="font-size:0.6rem;">Sắp diễn ra</span>');
            }

            const typeLabel = s.session_type === 'exam' ? '<span class="badge bg-warning text-dark me-1" style="font-size:0.65rem;">Thi</span>' : '';
            const sessJson = encodeURIComponent(JSON.stringify(s));

            const cardHtml = `
                <div class="session-item-card ${attClass}" onclick="showSessionDetail('${sessJson}')">
                    <div class="session-course-title">${s.course_name}</div>
                    <div class="session-meta-line"><i class="bi bi-clock"></i> ${s.start_time.substring(0, 5)} - ${s.end_time.substring(0, 5)}</div>
                    <div class="session-meta-line"><i class="bi bi-geo-alt"></i> Phòng: ${s.room || 'N/A'}</div>
                    <div class="session-meta-line"><i class="bi bi-person"></i> GV: ${s.teacher_name || 'N/A'} (${s.teacher_email || 'N/A'})</div>
                    <div class="mt-2 d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div>${typeLabel}</div>
                        <div>${statusText}</div>
                    </div>
                    ${s.note ? `<div class="session-note-box"><i class="bi bi-sticky"></i> ${s.note}</div>` : ''}
                </div>
            `;
            cell.innerHTML += cardHtml;
        }
    });

    // 4. Nếu ô nào trống ở chế độ Desktop, hiển thị ký tự trống nhạt
    cells.forEach(c => {
        if (!c.innerHTML) {
            c.innerHTML = '<div class="text-center text-muted py-3 small d-none d-md-block" style="opacity: 0.35;">--</div>';
        }
    });
}

function showSessionDetail(sessJson) {
    const s = JSON.parse(decodeURIComponent(sessJson));
    document.getElementById('mCourseCode').textContent = s.course_code || '';
    document.getElementById('mCourseName').textContent = s.course_name || '';
    document.getElementById('mTeacherName').textContent = s.teacher_name ? 'Giảng viên: ' + s.teacher_name : 'Giảng viên: Chưa phân công';
    document.getElementById('mDate').textContent = formatDateVi(new Date(s.session_date + 'T00:00:00'));
    document.getElementById('mTime').textContent = `${s.start_time.slice(0,5)} - ${s.end_time.slice(0,5)}`;
    document.getElementById('mRoom').textContent = s.room || 'Chưa xếp phòng';
    
    const typeEl = document.getElementById('mType');
    if (s.session_type === 'exam') {
        typeEl.textContent = 'Lịch thi';
        typeEl.className = 'fw-semibold text-warning';
    } else {
        typeEl.textContent = 'Lịch học';
        typeEl.className = 'fw-semibold text-primary';
    }

    const attStatus = document.getElementById('mAttendanceStatus');
    const attTime = document.getElementById('mAttendanceTime');
    const attBox = document.getElementById('mAttendanceBox');
    
    if (s.my_attendance === 'present') {
        attStatus.textContent = 'Có mặt';
        attStatus.className = 'fw-bold text-success';
        attTime.textContent = s.attended_at ? s.attended_at.slice(11,16) : '';
        attBox.style.borderColor = 'var(--success)';
    } else if (s.my_attendance === 'late') {
        attStatus.textContent = 'Đi muộn';
        attStatus.className = 'fw-bold text-warning';
        attTime.textContent = s.attended_at ? s.attended_at.slice(11,16) : '';
        attBox.style.borderColor = 'var(--warning)';
    } else if (s.my_attendance === 'absent') {
        attStatus.textContent = 'Vắng mặt';
        attStatus.className = 'fw-bold text-danger';
        attTime.textContent = 'Hệ thống tự động ghi nhận';
        attBox.style.borderColor = 'var(--danger)';
    } else {
        attStatus.textContent = 'Chưa điểm danh';
        attStatus.className = 'fw-bold text-muted';
        attTime.textContent = '';
        attBox.style.borderColor = 'var(--border-color-darker)';
    }

    const noteBox = document.getElementById('mNoteBox');
    if (s.note) {
        document.getElementById('mNote').textContent = s.note;
        noteBox.style.display = 'block';
    } else {
        noteBox.style.display = 'none';
    }

    // Tạo nút hành động
    const actionsContainer = document.getElementById('modalActions');
    actionsContainer.innerHTML = '';
    if (s.status === 'active') {
        const btnAttendance = document.createElement('a');
        btnAttendance.href = `${BASE_URL}/student/attendance`;
        btnAttendance.className = 'btn btn-primary-modern flex-fill text-center d-flex align-items-center justify-content-center gap-1';
        btnAttendance.innerHTML = '<i class="bi bi-qr-code-scan"></i> Điểm danh ngay';
        
        const btnQuiz = document.createElement('a');
        btnQuiz.href = `${BASE_URL}/student/quiz`;
        btnQuiz.className = 'btn btn-outline-primary flex-fill text-center d-flex align-items-center justify-content-center gap-1';
        btnQuiz.innerHTML = '<i class="bi bi-patch-question"></i> Vào phòng Quiz';
        
        actionsContainer.appendChild(btnAttendance);
        actionsContainer.appendChild(btnQuiz);
    } else {
        const btnClose = document.createElement('button');
        btnClose.type = 'button';
        btnClose.className = 'btn btn-secondary flex-fill';
        btnClose.textContent = 'Đóng';
        btnClose.setAttribute('data-bs-dismiss', 'modal');
        actionsContainer.appendChild(btnClose);
    }

    const modal = new bootstrap.Modal(document.getElementById('sessionDetailModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
