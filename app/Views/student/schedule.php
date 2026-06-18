<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Lịch học sắp tới</h2>
        <div class="text-muted">Các buổi học trong <span class="fw-medium text-dark">7 ngày tới</span> của bạn</div>
    </div>
</div>

<div id="scheduleAlertsContainer"></div>

<div class="card-modern">
    <div id="scheduleLoadingState" class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        Đang tải lịch học...
    </div>
    <div id="scheduleEmptyState" class="text-center py-5 d-none">
        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
        <div class="mt-3 text-muted fw-medium">Không có buổi học nào trong 7 ngày tới</div>
        <div class="small text-muted mt-1">Hãy kiểm tra lại sau khi giảng viên lên lịch buổi học mới</div>
    </div>
    <div id="scheduleTable" class="d-none">
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Ngày học</th>
                        <th>Thứ</th>
                        <th>Môn học</th>
                        <th>Giảng viên</th>
                        <th>Giờ học</th>
                        <th>Phòng học</th>
                        <th class="text-center">Tiết</th>
                        <th class="text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody id="scheduleTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Timeline view for upcoming week -->
<div class="row g-3 mt-4" id="weekDayCards"></div>

<?php
$content = ob_get_clean();
ob_start();
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch(BASE_URL + "/api/student/dashboard-data")
        .then(r => r.json())
        .then(res => {
            document.getElementById("scheduleLoadingState").classList.add("d-none");
            if (res.status !== "success") return;
            const schedule = res.data.upcomingSchedule || [];
            renderSchedule(schedule);
        })
        .catch(() => {
            document.getElementById("scheduleLoadingState").classList.add("d-none");
            document.getElementById("scheduleEmptyState").classList.remove("d-none");
        });

    function renderSchedule(schedule) {
        if (schedule.length === 0) {
            document.getElementById("scheduleEmptyState").classList.remove("d-none");
            renderWeekCards([]);
            return;
        }
        document.getElementById("scheduleTable").classList.remove("d-none");
        const tbody = document.getElementById("scheduleTbody");
        tbody.innerHTML = "";

        const dayNames = ["CN", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7"];

        schedule.forEach(s => {
            const dt = new Date(s.session_date);
            const dayName = dayNames[dt.getDay()];
            const dateStr = ("0"+dt.getDate()).slice(-2) + "/" + ("0"+(dt.getMonth()+1)).slice(-2) + "/" + dt.getFullYear();

            let statusBadge = "";
            const isToday = new Date().toDateString() === dt.toDateString();
            if (s.status === "active") statusBadge = "<span class='badge bg-danger animate-pulse'>ĐANG DIỄN RA</span>";
            else if (isToday) statusBadge = "<span class='badge bg-warning text-dark'>Hôm nay</span>";
            else statusBadge = "<span class='badge bg-light text-muted border'>Sắp diễn ra</span>";

            const startTime = s.start_time ? s.start_time.substring(0, 5) : "—";
            const endTime = s.end_time ? s.end_time.substring(0, 5) : "—";

            const row = document.createElement("tr");
            if (isToday) row.style.backgroundColor = "rgba(59,130,246,0.04)";
            row.innerHTML = `
                <td class="fw-medium">${dateStr}</td>
                <td class="text-muted">${dayName}</td>
                <td>
                    <div class="fw-semibold">${esc(s.course_name)}</div>
                    <div class="small text-primary">${esc(s.course_code)}</div>
                </td>
                <td class="text-muted small">${esc(s.teacher_name || "—")}</td>
                <td class="text-muted small"><i class="bi bi-clock me-1"></i>${startTime} — ${endTime}</td>
                <td class="small">${s.room ? `<i class="bi bi-door-open me-1 text-muted"></i>${esc(s.room)}` : '—'}</td>
                <td class="text-center text-muted small">${s.period ? `Tiết ${esc(s.period)}` : '—'}</td>
                <td class="text-center">${statusBadge}</td>
            `;
            tbody.appendChild(row);
        });

        renderWeekCards(schedule);
    }

    function renderWeekCards(schedule) {
        const container = document.getElementById("weekDayCards");
        container.innerHTML = "";

        // Tạo 7 ngày từ hôm nay
        const today = new Date();
        const dayNames = ["Chủ nhật", "Thứ hai", "Thứ ba", "Thứ tư", "Thứ năm", "Thứ sáu", "Thứ bảy"];

        for (let i = 0; i < 7; i++) {
            const d = new Date(today);
            d.setDate(today.getDate() + i);
            const dateStr = d.toISOString().split("T")[0];

            // Tìm các buổi học trong ngày này
            const sessionsToday = schedule.filter(s => s.session_date === dateStr || s.session_date.startsWith(dateStr));

            const isToday = i === 0;
            const dayLabel = isToday ? "Hôm nay" : dayNames[d.getDay()];
            const dateLabel = ("0"+d.getDate()).slice(-2)+"/"+("0"+(d.getMonth()+1)).slice(-2);

            const col = document.createElement("div");
            col.className = "col-md-3 col-6";
            col.innerHTML = `
                <div class="card-modern h-100" style="${isToday ? 'border: 2px solid var(--primary);' : ''}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-bold ${isToday ? 'text-primary' : ''}" style="font-size:0.95rem;">${dayLabel}</div>
                            <div class="small text-muted">${dateLabel}</div>
                        </div>
                        ${sessionsToday.length > 0 ? `<span class="badge bg-primary rounded-pill">${sessionsToday.length}</span>` : '<span class="text-muted small">Trống</span>'}
                    </div>
                    <div class="d-flex flex-column gap-2">
                        ${sessionsToday.map(s => `
                            <div class="p-2 rounded" style="background:var(--bg-card-alt,#f8fafc);border-left:3px solid var(--primary);">
                                <div class="fw-semibold small">${esc(s.course_code)}</div>
                                <div class="text-muted" style="font-size:0.75rem;">${s.start_time ? s.start_time.substring(0,5) : "—"} — ${s.end_time ? s.end_time.substring(0,5) : "—"}</div>
                                ${s.room ? `<div class="text-muted" style="font-size:0.7rem;"><i class="bi bi-door-open me-1"></i>${esc(s.room)}</div>` : ''}
                                ${s.period ? `<div class="text-muted" style="font-size:0.7rem;"><i class="bi bi-clock me-1"></i>Tiết ${esc(s.period)}</div>` : ''}
                            </div>
                        `).join("") || '<div class="text-muted small py-2 text-center">Không có lịch</div>'}
                    </div>
                </div>
            `;
            container.appendChild(col);
        }
    }

    function esc(s) { return s ? String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;") : ""; }
});
</script>
<?php
$extraJs = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
