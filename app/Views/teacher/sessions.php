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
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active">Lịch dạy</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Lịch giảng dạy & Lịch thi</h3>
        </div>
        <!-- Bộ lọc lớp học -->
        <div class="d-flex align-items-center gap-2">
            <label class="fw-semibold text-muted small mb-0 d-none d-sm-inline">Lớp học phần:</label>
            <select class="form-select form-select-sm" style="width: 220px; border-radius: 20px;" id="courseFilter">
                <option value="">-- Tất cả lớp --</option>
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
                    <label class="form-check-label text-nowrap" for="filterStudy"><span class="dot-filter dot-study">●</span> Lịch dạy</label>
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

<script>
let currentDate = new Date();
let selectedType = 'all';
let teacherCourseIds = [];
let allSessions = [];

document.addEventListener('DOMContentLoaded', () => {
    // Set datepicker mặc định là ngày hôm nay
    document.getElementById('scheduleDatePicker').value = formatDateDb(currentDate);
    
    loadMyCourses();
    setupEventListeners();
});

// Lắng nghe sự kiện đổi học kỳ trên Topbar
window.addEventListener('semesterChanged', () => {
    loadMyCourses();
});

function setupEventListeners() {
    // Datepicker
    const datePicker = document.getElementById('scheduleDatePicker');
    datePicker.addEventListener('change', () => {
        if (datePicker.value) {
            currentDate = new Date(datePicker.value);
            loadSessions();
        }
    });

    // Lọc loại lịch
    const radioFilters = document.querySelectorAll('input[name="scheduleFilter"]');
    radioFilters.forEach(radio => {
        radio.addEventListener('change', () => {
            selectedType = radio.value;
            renderCalendar();
        });
    });

    // Lọc lớp học phần
    document.getElementById('courseFilter').addEventListener('change', () => {
        renderCalendar();
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
    loadSessions();
}

function goToCurrentWeek() {
    currentDate = new Date();
    document.getElementById('scheduleDatePicker').value = formatDateDb(currentDate);
    loadSessions();
}

function toggleCompactSchedule() {
    const table = document.getElementById('scheduleTable');
    const toggleBtn = document.getElementById('btnCompactToggle');
    const toggleText = document.getElementById('textCompactToggle');
    
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

function loadMyCourses() {
    fetch(`${BASE_URL}/api/teacher/courses`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const sel = document.getElementById('courseFilter');
                while (sel.options.length > 1) {
                    sel.remove(1);
                }
                teacherCourseIds = [];
                res.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.code} - ${c.name}`;
                    sel.appendChild(opt);
                    teacherCourseIds.push(c.id);
                });
                loadSessions();
            }
        });
}

function loadSessions() {
    const monday = getMonday(currentDate);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    const params = new URLSearchParams({
        start: formatDateDb(monday),
        end: formatDateDb(sunday)
    });

    // Reset các cell
    const cells = document.querySelectorAll('.schedule-cell-grid');
    cells.forEach(c => c.innerHTML = '');

    fetch(`${BASE_URL}/api/sessions?${params}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                allSessions = res.data.filter(s => teacherCourseIds.includes(parseInt(s.course_id)));
                renderCalendar();
            }
        })
        .catch(err => console.error(err));
}

function renderCalendar() {
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
        const dayIndex = sessDate.getDay(); // 0: CN, 1: T2, 2: T3 ...
        const colNum = dayIndex === 0 ? 8 : dayIndex + 1; // map 1: T2 (col index 2), v.v., CN: 8

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
            const isExam = s.session_type === 'exam';
            const cardClass = isExam ? 'session-card-exam' : 'session-card-normal';
            const typeLabel = isExam ? '<span class="badge bg-warning text-dark me-1" style="font-size:0.65rem;">Thi</span>' : '';
            const statusLabel = s.status === 'completed' ? '<span class="badge bg-secondary" style="font-size:0.6rem;">Hoàn thành</span>' : (s.status === 'active' ? '<span class="badge bg-success" style="font-size:0.6rem;">Đang dạy</span>' : '<span class="badge bg-primary" style="font-size:0.6rem;">Sắp diễn ra</span>');

            const cardHtml = `
                <div class="session-item-card ${cardClass}" onclick="location.href='${BASE_URL}/teacher/session-detail/${s.id}'">
                    <div class="session-course-title">${s.course_name}</div>
                    <div class="session-meta-line"><i class="bi bi-clock"></i> ${s.start_time.substring(0, 5)} - ${s.end_time.substring(0, 5)}</div>
                    <div class="session-meta-line"><i class="bi bi-geo-alt"></i> Phòng: ${s.room || 'N/A'}</div>
                    <div class="session-meta-line"><i class="bi bi-person"></i> GV: ${s.teacher_name || 'N/A'} (${s.teacher_email || 'N/A'})</div>
                    <div class="mt-2 d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div>${typeLabel}</div>
                        <div>${statusLabel}</div>
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
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php';
?>
