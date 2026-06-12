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
        background: #0284c7; /* Xanh dương đậm đà */
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

    /* Kẻ ô vở học sinh sang trọng */
    .schedule-cell-grid {
        background-color: var(--cell-empty-bg);
        background-image: 
            linear-gradient(var(--schedule-grid-color) 1px, transparent 1px),
            linear-gradient(90deg, var(--schedule-grid-color) 1px, transparent 1px);
        background-size: 15px 15px;
        min-height: 280px;
        height: 100%;
        transition: background-color 0.2s ease;
    }

    .schedule-cell-grid:hover {
        background-color: #f8fafc;
    }

    /* Thẻ Buổi học (Session Card) */
    .session-item-card {
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        font-size: 0.85rem;
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
        font-size: 0.95rem;
        margin-bottom: 5px;
        line-height: 1.3;
    }

    .session-meta-line {
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .session-meta-line i {
        opacity: 0.7;
        font-size: 0.9rem;
    }

    .session-note-box {
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px dashed rgba(0, 0, 0, 0.08);
        font-style: italic;
        font-weight: 500;
    }

    /* Nút thao tác ẩn/hiện khi hover card */
    .session-card-actions {
        position: absolute;
        top: 6px;
        right: 6px;
        display: none;
        gap: 4px;
    }

    .session-item-card:hover .session-card-actions {
        display: flex;
    }

    .btn-card-action {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: rgba(255,255,255,0.85);
        color: #334155;
        font-size: 0.75rem;
        transition: all 0.15s ease;
    }

    .btn-card-action:hover {
        background: #ffffff;
        transform: scale(1.1);
    }
    
    .btn-card-action.edit:hover {
        color: #2563eb;
    }
    
    .btn-card-action.delete:hover {
        color: #dc2626;
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

    /* CSS cho các chấm tròn filter */
    .dot-filter {
        font-size: 1.1rem;
        margin-right: 4px;
        vertical-align: middle;
        display: inline-block;
        line-height: 1;
    }
    .dot-all {
        color: #64748b; /* Xám */
    }
    .dot-study {
        color: #2563eb; /* Xanh dương đậm */
    }
    .dot-exam {
        color: #eab308; /* Vàng sẫm */
    }

    /* CSS cho chế độ Thu nhỏ bảng */
    .schedule-table.schedule-compact {
        table-layout: fixed !important;
        width: 100% !important;
    }
    .schedule-table.schedule-compact th.day-header {
        min-width: unset !important;
        width: 13.5% !important; /* chia đều 7 cột */
        font-size: 0.8rem !important;
        padding: 6px 4px !important;
    }
    .schedule-table.schedule-compact th:first-child {
        width: 55px !important; /* ca học */
    }
    .schedule-table.schedule-compact td {
        padding: 4px !important;
    }
    .schedule-table.schedule-compact .shift-col {
        font-size: 0.85rem !important;
        width: 55px !important;
    }
    .schedule-table.schedule-compact .schedule-cell-grid {
        min-height: 150px !important;
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
    .schedule-table.schedule-compact .session-note-box {
        margin-top: 3px !important;
        padding-top: 3px !important;
        font-size: 0.65rem !important;
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

<!-- Wrapper để quản lý chế độ Toàn màn hình -->
<div id="scheduleWrapper">
    <!-- Tiêu đề & Công cụ điều phối -->
    <div class="schedule-header-controls">
        <!-- Cột trái: Tiêu đề và Bộ lọc -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h3 class="fw-bold mb-0" style="color: var(--text-main); font-size: 1.35rem; letter-spacing: -0.5px;">Lịch học, lịch thi theo tuần</h3>
            
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
                <input type="date" class="form-control border-start-0 ps-1 fw-semibold text-primary" id="scheduleDatePicker" value="2026-05-26" style="border-radius: 0 6px 6px 0; font-size: 0.85rem; padding: 4px 6px;">
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

            <!-- Nút Tạo mới -->
            <button class="btn btn-primary-modern btn-sm px-3 fw-bold d-flex align-items-center gap-1" onclick="openSessionModal()" style="height: 31px; border-radius: 6px; font-size: 0.85rem; padding: 4px 12px; background: #0284c7; border: 1px solid #0284c7;">
                <i class="bi bi-plus-lg" style="font-size: 0.85rem;"></i> Tạo buổi học
            </button>
        </div>
    </div>

    <!-- Khung Bảng Lịch học tuần -->
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

<!-- Modal tạo/sửa buổi học -->
<div class="modal fade" id="sessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="sessionModalTitle">Tạo buổi học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sessionForm">
                    <input type="hidden" id="sessionId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Khóa học / Học phần</label>
                        <select class="form-select" id="sessionCourseId" required>
                            <option value="">-- Chọn khóa học --</option>
                            <!-- JS load courses -->
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Ngày học</label>
                            <input type="date" class="form-control" id="sessionDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Tiết học (VD: 1 - 3, 7 - 8)</label>
                            <input type="text" class="form-control" id="sessionPeriod" placeholder="Ví dụ: 1 - 3" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Giờ bắt đầu</label>
                            <input type="time" class="form-control" id="sessionStartTime" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Giờ kết thúc</label>
                            <input type="time" class="form-control" id="sessionEndTime" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Phòng học (VD: Phòng 512, số 1 Phan Tây Nhạc)</label>
                        <input type="text" class="form-control" id="sessionRoom" placeholder="Ví dụ: Phòng học 402, số 1 Phan Tây Nhạc" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Ghi chú (Nhập chữ "Thi" để chuyển thẻ màu vàng)</label>
                        <textarea class="form-control" id="sessionNote" rows="2" placeholder="Ví dụ: Lịch học bù cho ngày 28/04/2026 hoặc Lịch thi học kỳ"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Trạng thái</label>
                        <select class="form-select" id="sessionStatus">
                            <option value="scheduled">Sắp diễn ra</option>
                            <option value="active">Đang diễn ra</option>
                            <option value="completed">Đã hoàn thành</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveSession()">Lưu thông tin</button>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '<script src="' . BASE_URL . '/assets/js/sessions.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
