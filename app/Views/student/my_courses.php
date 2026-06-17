<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Học phần của tôi</h2>
        <div class="text-muted">Chi tiết chuyên cần &amp; CPI theo từng học phần</div>
    </div>
</div>

<div id="coursesLoadingState" class="text-center py-5 text-muted">
    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
    Đang tải dữ liệu học phần...
</div>

<div id="coursesContent" class="d-none">
    <!-- Course Cards -->
    <div class="row g-4" id="courseCardsList"></div>
</div>

<!-- Modal chi tiết điểm danh theo môn -->
<div class="modal fade" id="courseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="courseDetailTitle">Chi tiết học phần</h5>
                    <div class="small text-muted" id="courseDetailCode"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- CPI Breakdown -->
                <div class="row g-3 mb-4">
                    <div class="col-4 text-center">
                        <div class="fw-bold text-primary fs-4" id="detailCPI">—</div>
                        <div class="small text-muted">Điểm CPI</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-success fs-4" id="detailAttPts">—</div>
                        <div class="small text-muted">Điểm chuyên cần</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-warning fs-4" id="detailIntPts">—</div>
                        <div class="small text-muted">Điểm tương tác</div>
                    </div>
                </div>

                <!-- Rules -->
                <div class="p-3 rounded mb-4" style="background: var(--bg-card-alt,#f8fafc);">
                    <div class="small fw-bold text-muted mb-2"><i class="bi bi-gear me-1"></i>Quy tắc tính điểm của học phần</div>
                    <div class="row g-2 small" id="detailRules"></div>
                </div>

                <!-- Attendance history for this course -->
                <h6 class="fw-bold mb-3">Lịch sử điểm danh học phần này</h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Hình thức</th>
                                <th>Giờ ghi nhận</th>
                                <th class="text-end">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="detailAttendanceTbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
ob_start();
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let dashData = null;

    fetch(BASE_URL + "/api/student/dashboard-data")
        .then(r => r.json())
        .then(res => {
            document.getElementById("coursesLoadingState").classList.add("d-none");
            if (res.status !== "success") return;
            dashData = res.data;
            renderCourseCards(dashData.courses, dashData.scores, dashData.attendanceSummary, dashData.history);
            document.getElementById("coursesContent").classList.remove("d-none");
        })
        .catch(() => document.getElementById("coursesLoadingState").innerText = "Không thể tải dữ liệu.");

    function renderCourseCards(courses, scores, summary, history) {
        const container = document.getElementById("courseCardsList");
        container.innerHTML = "";
        if (!courses || courses.length === 0) {
            container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="bi bi-journal-x" style="font-size:3rem;"></i><div class="mt-3">Bạn chưa đăng ký học phần nào</div></div>`;
            return;
        }

        const absentByCourse = (summary && summary.absentByCourse) ? summary.absentByCourse : {};

        courses.forEach(c => {
            const score = scores ? scores.find(s => s.course_id == c.id) : null;
            const cpi = score ? parseInt(score.total_score) : 100;
            const attPts = score ? parseInt(score.attendance_points) : 0;
            const intPts = score ? parseInt(score.interaction_points) : 0;
            const absent = absentByCourse[c.id] || 0;

            // Đếm buổi có mặt / muộn / vắng cho môn này
            const courseHistory = history ? history.filter(h => h.course_id == c.id) : [];
            const present = courseHistory.filter(h => h.status === "present").length;
            const late = courseHistory.filter(h => h.status === "late").length;
            const excused = courseHistory.filter(h => h.status === "excused").length;
            const total = courseHistory.length;

            const cpiColor = cpi >= 80 ? "#22c55e" : cpi >= 60 ? "#f59e0b" : "#ef4444";
            const presentPct = total > 0 ? Math.round(((present + late + excused) / total) * 100) : 100;

            const col = document.createElement("div");
            col.className = "col-lg-6";
            col.innerHTML = `
                <div class="card-modern h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="badge bg-primary-subtle text-primary rounded-pill mb-1" style="font-size:0.75rem;">${esc(c.code)}</div>
                            <h6 class="fw-bold mb-1">${esc(c.name)}</h6>
                            <div class="small text-muted"><i class="bi bi-person me-1"></i>${esc(c.teacher_name || "Chưa phân công")}</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold fs-4" style="color: ${cpiColor};">${cpi}</div>
                            <div class="small text-muted" style="font-size:0.7rem;">CPI/100</div>
                        </div>
                    </div>

                    <!-- Progress bar tỷ lệ có mặt -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Tỷ lệ có mặt</span>
                            <span class="fw-semibold">${presentPct}%</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width:${presentPct}%;border-radius:10px;"></div>
                        </div>
                    </div>

                    <!-- Stats 4 ô -->
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-3">
                            <div class="p-2 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                                <div class="fw-bold text-primary">${total}</div>
                                <div style="font-size:0.7rem;color:#6b7280;">Tổng</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                                <div class="fw-bold text-success">${present}</div>
                                <div style="font-size:0.7rem;color:#6b7280;">Có mặt</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                                <div class="fw-bold text-warning">${late}</div>
                                <div style="font-size:0.7rem;color:#6b7280;">Muộn</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                                <div class="fw-bold ${absent >= 3 ? 'text-danger' : ''}">${absent}</div>
                                <div style="font-size:0.7rem;color:#6b7280;">Vắng</div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-outline-primary btn-sm rounded-pill w-100" onclick="showCourseDetail(${c.id})">
                        <i class="bi bi-eye me-1"></i>Xem chi tiết
                    </button>
                </div>
            `;
            container.appendChild(col);
        });
    }

    window.showCourseDetail = function(courseId) {
        if (!dashData) return;
        const c = dashData.courses.find(x => x.id == courseId);
        const score = dashData.scores ? dashData.scores.find(s => s.course_id == courseId) : null;
        if (!c) return;

        document.getElementById("courseDetailTitle").innerText = c.name;
        document.getElementById("courseDetailCode").innerText = c.code + " | " + (c.class_code || "");
        document.getElementById("detailCPI").innerText = score ? score.total_score + "/100" : "—";
        document.getElementById("detailAttPts").innerText = score ? score.attendance_points + "đ" : "0đ";
        document.getElementById("detailIntPts").innerText = score ? score.interaction_points + "đ" : "0đ";

        // Rules
        document.getElementById("detailRules").innerHTML = `
            <div class="col-6"><span class="text-muted">Có mặt:</span> <span class="fw-semibold text-success">+${c.rule_present_points || 2} điểm</span></div>
            <div class="col-6"><span class="text-muted">Đi muộn:</span> <span class="fw-semibold text-warning">+${c.rule_late_points || 1} điểm</span></div>
            <div class="col-6"><span class="text-muted">Vắng mặt:</span> <span class="fw-semibold text-danger">${c.rule_absent_points || 0} điểm</span></div>
            <div class="col-6"><span class="text-muted">Phát biểu:</span> <span class="fw-semibold text-info">+${c.rule_interaction_points || 1} điểm</span></div>
            <div class="col-6"><span class="text-muted">Trọng số CC:</span> <span class="fw-semibold">${c.rule_attendance_weight || 50}%</span></div>
            <div class="col-6"><span class="text-muted">Trọng số Quiz:</span> <span class="fw-semibold">${c.rule_quiz_weight || 50}%</span></div>
        `;

        // Attendance history filtered by course
        const courseHistory = dashData.history ? dashData.history.filter(h => h.course_id == courseId) : [];
        const tbody = document.getElementById("detailAttendanceTbody");
        tbody.innerHTML = "";
        if (courseHistory.length === 0) {
            tbody.innerHTML = "<tr><td colspan='4' class='text-center text-muted py-3'>Chưa có lịch sử điểm danh cho học phần này</td></tr>";
        } else {
            courseHistory.forEach(h => {
                let badge = "";
                if (h.status === "present") badge = "<span class='badge bg-success'>Có mặt</span>";
                else if (h.status === "late") badge = "<span class='badge bg-warning text-dark'>Đi muộn</span>";
                else if (h.status === "absent") badge = "<span class='badge bg-danger'>Vắng mặt</span>";
                else if (h.status === "excused") badge = "<span class='badge bg-secondary'>Có phép</span>";
                else badge = "<span class='badge bg-light text-muted border'>Chưa điểm danh</span>";

                const dt = new Date(h.session_date);
                const dateStr = ("0"+dt.getDate()).slice(-2)+"/"+("0"+(dt.getMonth()+1)).slice(-2)+"/"+dt.getFullYear();
                const method = h.method_name ? `<span class="badge bg-light text-muted">${esc(h.method_name)}</span>` : "—";
                const recTime = h.recorded_at ? new Date(h.recorded_at).toLocaleTimeString("vi-VN", {hour:"2-digit",minute:"2-digit"}) : "—";

                const row = document.createElement("tr");
                row.innerHTML = `<td>${dateStr}</td><td>${method}</td><td class="text-muted">${recTime}</td><td class="text-end">${badge}</td>`;
                tbody.appendChild(row);
            });
        }

        new bootstrap.Modal(document.getElementById("courseDetailModal")).show();
    };

    function esc(s) { return s ? String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;") : ""; }
});
</script>
<?php
$extraJs = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
