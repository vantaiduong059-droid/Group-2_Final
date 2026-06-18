<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Chuyên cần &amp; Tương tác cá nhân</h2>
        <div class="text-muted">Xin chào, <span class="fw-medium text-dark"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>!</div>
    </div>
    <button class="btn btn-outline-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2" id="btnOpenLeaveRequest">
        <i class="bi bi-calendar-x"></i> Xin phép vắng
    </button>
</div>

<!-- Auto-refresh status bar -->
<div class="d-flex align-items-center gap-2 mb-3 px-1" id="autoRefreshBar" style="font-size:0.8rem;color:#64748b;">
    <div id="refreshSpinner" class="d-none">
        <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px;border-width:2px;"></div>
    </div>
    <i class="bi bi-arrow-clockwise text-success" id="refreshIdleIcon"></i>
    <span id="refreshStatusText">Cập nhật lúc <span id="lastUpdatedTime">—</span></span>
    <span class="text-muted mx-1">·</span>
    <button class="btn btn-outline-primary btn-sm px-3 py-1 rounded-pill d-flex align-items-center gap-1" id="btnManualRefresh" title="Đồng bộ ngay">
        <i class="bi bi-arrow-clockwise"></i> Đồng bộ dữ liệu
    </button>
</div>

<!-- CONTAINER CẢNH BÁO TỪ ALERT ENGINE -->
<div id="studentAlertsContainer"></div>

<!-- THỐNG KÊ NHANH 4 THẺ -->
<div class="row g-3 mb-4" id="statsRow">
    <div class="col-6 col-md-3">
        <div class="card-modern text-center py-3">
            <div class="fs-2 fw-bold text-primary" id="statTotal">—</div>
            <div class="small text-muted mt-1"><i class="bi bi-calendar3 me-1"></i>Tổng buổi học</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center py-3">
            <div class="fs-2 fw-bold text-success" id="statPresent">—</div>
            <div class="small text-muted mt-1"><i class="bi bi-check-circle me-1"></i>Có mặt</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center py-3">
            <div class="fs-2 fw-bold text-warning" id="statLate">—</div>
            <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i>Đi muộn</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center py-3">
            <div class="fs-2 fw-bold text-danger" id="statAbsent">—</div>
            <div class="small text-muted mt-1"><i class="bi bi-x-circle me-1"></i>Vắng mặt</div>
        </div>
    </div>
</div>

<!-- PHIÊN HỌC ĐANG DIỄN RA -->
<div class="row g-4 mb-4" id="activeSessionsRow">
    <!-- Hộp Điểm danh trực tuyến -->
    <div class="col-lg-6 d-none" id="attendanceActiveBox">
        <div class="card-modern border-primary" style="border: 2px solid var(--primary); background: #f0f7ff;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-danger animate-pulse mb-2">ĐANG DIỄN RA</span>
                    <h5 class="fw-bold text-dark mb-1" id="activeSessionName">Môn học</h5>
                    <div class="text-muted small"><i class="bi bi-clock me-1"></i> Cổng điểm danh đang mở!</div>
                </div>
                <div class="fs-4 fw-bold text-danger" id="attCountdownTimer">05:00</div>
            </div>
            <div class="p-3 bg-white rounded border mb-3">
                <div class="nav nav-pills nav-fill gap-2 mb-3" id="attMethodTabs" role="tablist">
                    <button class="nav-link active py-2 border-0" id="pill-code-tab" data-bs-toggle="pill" data-bs-target="#pill-code" type="button" role="tab"><i class="bi bi-key me-2"></i>Nhập mã Code</button>
                    <button class="nav-link py-2 border-0" id="pill-qr-tab" data-bs-toggle="pill" data-bs-target="#pill-qr" type="button" role="tab"><i class="bi bi-qr-code me-2"></i>Quét QR</button>
                </div>
                <div class="tab-content" id="attMethodTabContents">
                    <div class="tab-pane fade show active" id="pill-code" role="tabpanel">
                        <form id="submitCodeForm" class="d-flex gap-2">
                            <input type="text" class="form-control text-center fw-bold fs-5 letter-spacing-2" id="attendanceCodeInput" placeholder="MÃ CODE 6 SỐ" maxlength="6" required>
                            <button type="submit" class="btn btn-primary-modern px-4">Gửi</button>
                        </form>
                    </div>
                    <div class="tab-pane fade text-center py-2" id="pill-qr" role="tabpanel">
                        <p class="text-muted small">Nhấp nút bên dưới để giả lập quét mã QR.</p>
                        <button class="btn btn-outline-primary rounded-pill px-4" id="btnStartFakeQRScan"><i class="bi bi-camera me-2"></i>Quét QR</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Đã điểm danh rồi + Nút Tương tác -->
    <div class="col-lg-6 d-none" id="alreadyAttendedBox">
        <div class="card-modern" style="border: 2px solid var(--success); background: #f0fdf4;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                    <i class="bi bi-check-lg fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold text-success fs-6">Đã điểm danh thành công!</div>
                    <div class="text-muted small" id="alreadyAttendedSessionName">Buổi học đang diễn ra</div>
                    <div class="text-muted small">Trạng thái: <span class="fw-semibold" id="alreadyAttendedStatus"></span></div>
                </div>
            </div>
            <!-- Nút tương tác lớp học -->
            <div class="border-top pt-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-hand-index-thumb me-1"></i>Tương tác trong buổi học (cộng điểm CPI)</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm rounded-pill flex-fill" onclick="logInteraction('question')">
                        <i class="bi bi-question-circle me-1"></i>Đặt câu hỏi <span class="badge bg-primary ms-1">+1</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill flex-fill" onclick="logInteraction('answer')">
                        <i class="bi bi-chat-dots me-1"></i>Trả lời <span class="badge bg-success ms-1">+2</span>
                    </button>
                    <button class="btn btn-outline-info btn-sm rounded-pill flex-fill" onclick="logInteraction('discussion')">
                        <i class="bi bi-people me-1"></i>Thảo luận <span class="badge bg-info ms-1">+1</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hộp Quiz Đang diễn ra -->
    <div class="col-lg-6 d-none" id="quizzesActiveBox">
        <div class="card-modern" style="border: 2px solid var(--success); background: #f0fdf4;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-success mb-2">QUIZ ĐANG MỞ</span>
                    <h5 class="fw-bold text-dark mb-1" id="activeQuizTitle">Mini Quiz</h5>
                    <div class="text-muted small" id="activeQuizSessionName">Buổi học</div>
                </div>
                <i class="bi bi-lightning-fill text-success fs-3"></i>
            </div>
            <p class="text-muted small">Giảng viên vừa kích hoạt một bài trắc nghiệm ngắn. Hãy tham gia để cộng điểm CPI!</p>
            <button class="btn btn-success w-100 py-2 rounded-pill fw-semibold" id="btnStartQuiz"><i class="bi bi-pencil-square me-2"></i>Bắt đầu làm Quiz</button>
        </div>
    </div>
</div>

<!-- TIẾN ĐỘ HỌC TẬP TỔNG QUAN -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card-modern">
            <h5 class="card-title-modern">Chỉ số tham gia lớp học (CPI Index)</h5>
            <div class="d-flex flex-column align-items-center justify-content-center py-3">
                <div class="position-relative d-flex align-items-center justify-content-center" style="width: 160px; height: 160px;">
                    <svg class="w-100 h-100" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                        <path class="text-light" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-primary" stroke-width="3" stroke-dasharray="0, 100" stroke-linecap="round" stroke="currentColor" fill="none" id="cpiGaugePath" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" style="transition: stroke-dasharray 1s ease-in-out;" />
                    </svg>
                    <div class="position-absolute text-center">
                        <div class="display-6 fw-bold text-dark" id="cpiIndexVal">—</div>
                        <div class="small text-muted fw-semibold">Điểm CPI</div>
                    </div>
                </div>
                <div class="w-100 mt-4 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small"><i class="bi bi-calendar-check-fill text-primary me-2"></i>Điểm chuyên cần tích lũy</span>
                        <span class="fw-semibold" id="cpiAttendancePoints">0đ</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-chat-left-dots-fill text-success me-2"></i>Điểm tương tác tích lũy</span>
                        <span class="fw-semibold" id="cpiInteractionPoints">0đ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-modern">
            <h5 class="card-title-modern">Các lớp học phần tham gia</h5>
            <div class="table-responsive">
                <table class="table-modern" id="studentCoursesTable">
                    <thead>
                        <tr>
                            <th>Mã lớp</th>
                            <th>Tên học phần</th>
                            <th>Giảng viên</th>
                            <th class="text-center">Vắng</th>
                            <th class="text-center">CPI</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- LỊCH SỬ CHI TIẾT -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <h5 class="card-title-modern">Lịch sử điểm danh chi tiết</h5>
            <div class="table-responsive" style="max-height: 350px; overflow-y:auto;">
                <table class="table-modern" id="studentAttendanceTable">
                    <thead>
                        <tr>
                            <th>Ngày học</th>
                            <th>Môn học</th>
                            <th>Hình thức</th>
                            <th class="text-end">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-modern">
            <h5 class="card-title-modern">Lịch sử Quiz trắc nghiệm đã làm</h5>
            <div class="table-responsive" style="max-height: 350px; overflow-y:auto;">
                <table class="table-modern" id="studentQuizHistoryTable">
                    <thead>
                        <tr>
                            <th>Tên Quiz</th>
                            <th>Học phần</th>
                            <th>Thời gian</th>
                            <th class="text-end">Kết quả</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ĐƠN XIN PHÉP VẮNG -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title-modern mb-0">Lịch sử đơn xin phép vắng</h5>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnOpenLeaveRequest2"><i class="bi bi-plus me-1"></i>Gửi đơn mới</button>
            </div>
            <div class="table-responsive">
                <table class="table-modern" id="leaveRequestsTable">
                    <thead>
                        <tr>
                            <th>Ngày học</th>
                            <th>Môn học</th>
                            <th>Lý do</th>
                            <th>Ghi chú GV</th>
                            <th class="text-end">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL XIN PHÉP VẮNG -->
<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-x me-2 text-primary"></i>Gửi đơn xin phép vắng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-2"></i>Chọn buổi học bạn muốn xin phép và nhập lý do. Giảng viên sẽ xem xét và phê duyệt.</div>
                <form id="leaveRequestForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Buổi học cần xin phép <span class="text-danger">*</span></label>
                        <select class="form-select" id="leaveSessionSelect" required>
                            <option value="">-- Chọn buổi học --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do xin phép <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="leaveReason" rows="4" placeholder="Nhập lý do xin phép vắng mặt buổi học..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern px-4" id="btnSubmitLeaveRequest"><i class="bi bi-send me-2"></i>Gửi đơn</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LÀM QUIZ -->
<div class="modal fade" id="quizPlayModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="quizPlayModalTitle">Làm bài Quiz nhanh</h5>
                <span class="badge bg-danger ms-auto fs-6 px-3" id="quizPlayTimer">10:00</span>
            </div>
            <div class="modal-body py-4">
                <div class="alert alert-info py-2 small mb-4"><i class="bi bi-info-circle me-2"></i>Chọn câu trả lời chính xác nhất. Mỗi câu đúng cộng điểm trực tiếp vào CPI.</div>
                <div id="quizQuestionBox">
                    <div class="fw-bold mb-3" style="font-size:1.05rem;">Câu 1: Repository Pattern trong PHP MVC giải quyết nhiệm vụ chính nào?</div>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-outline-secondary text-start py-2 px-3 rounded quiz-option-btn" data-correct="false"><span class="fw-bold me-2">A.</span>Xây dựng các thành phần giao diện HTML.</button>
                        <button class="btn btn-outline-secondary text-start py-2 px-3 rounded quiz-option-btn" data-correct="true"><span class="fw-bold me-2">B.</span>Tách biệt logic truy vấn CSDL ra khỏi Controller/Model.</button>
                        <button class="btn btn-outline-secondary text-start py-2 px-3 rounded quiz-option-btn" data-correct="false"><span class="fw-bold me-2">C.</span>Quản lý kết nối Database theo mô hình Singleton.</button>
                        <button class="btn btn-outline-secondary text-start py-2 px-3 rounded quiz-option-btn" data-correct="false"><span class="fw-bold me-2">D.</span>Điều hướng URL và bắt các request HTTP.</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnSubmitFakeQuiz">Nộp bài làm</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QUÉT QR -->
<div class="modal fade" id="qrScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera me-2 text-primary"></i>Quét mã QR Điểm danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="qr-scan-box position-relative mx-auto rounded mb-4 overflow-hidden border border-3 border-primary" style="width:280px; height:200px; background:#000;">
                    <div class="qr-laser position-absolute w-100" style="height:3px; background:rgba(59, 130, 246, 0.8); box-shadow:0 0 8px #3b82f6; animation: scanLaser 2s infinite linear;"></div>
                    <div class="d-flex h-100 flex-column align-items-center justify-content-center text-white p-3">
                        <i class="bi bi-upc-scan fs-1 mb-2 text-primary animate-pulse"></i>
                        <div class="small">Đang tìm mã QR điểm danh...</div>
                    </div>
                </div>
                <div class="px-4">
                    <label class="form-label small fw-semibold text-muted">Hoặc nhập Token QR (giả lập)</label>
                    <input type="text" class="form-control text-center" id="qrTokenFakeInput" placeholder="Nhập token QR...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" id="btnConfirmQRScan">Xác nhận điểm danh</button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scanLaser {
    0% { top: 0; }
    50% { top: 197px; }
    100% { top: 0; }
}
.letter-spacing-2 { letter-spacing: 4px; }
.quiz-option-btn.selected {
    background-color: var(--primary-light, #eff6ff) !important;
    border-color: var(--primary, #3b82f6) !important;
    color: var(--primary, #3b82f6) !important;
    font-weight: 600;
}
.quiz-option-btn.correct { background-color: #f0fdf4 !important; border-color: #22c55e !important; color: #16a34a !important; }
.quiz-option-btn.wrong { background-color: #fef2f2 !important; border-color: #ef4444 !important; color: #dc2626 !important; }
</style>

<?php
$content = ob_get_clean();
ob_start();
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let activeSession = null;
    let activeQuiz = null;
    let qrScanModalInstance = null;
    let quizPlayModalInstance = null;
    let leaveModalInstance = null;
    let attInterval = null;
    let allUpcomingSessions = [];

    const urlParams = new URLSearchParams(window.location.search);
    const qrTokenFromUrl = urlParams.get("qr_token");
    const sessionIdFromUrl = urlParams.get("session_id");

    // ============================================
    // SYNC MECHANISM: Đồng bộ thủ công qua nút ấn
    // ============================================
    function formatTime(date) {
        return date.toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    }

    function setRefreshing(loading) {
        document.getElementById("refreshSpinner").classList.toggle("d-none", !loading);
        document.getElementById("refreshIdleIcon").classList.toggle("d-none", loading);
        document.getElementById("btnManualRefresh").disabled = loading;
    }

    // Nút làm mới thủ công
    document.getElementById("btnManualRefresh").addEventListener("click", () => {
        loadDashboardData();
    });

    // Khởi chạy
    loadDashboardData();

    function loadDashboardData() {
        setRefreshing(true);
        fetch(BASE_URL + "/api/student/dashboard-data")
            .then(res => res.json())
            .then(res => {
                setRefreshing(false);
                // Cập nhật giờ làm mới
                const timeEl = document.getElementById("lastUpdatedTime");
                if (timeEl) timeEl.innerText = formatTime(new Date());

                if (res.status === "success") {
                    const data = res.data;
                    renderAlertBanners(data.alerts);
                    renderStatsSummary(data.attendanceSummary);
                    renderCoursesTable(data.courses, data.scores, data.attendanceSummary);
                    renderAttendanceTable(data.history.slice(0, 10));
                    renderQuizHistory(data.quizHistory);
                    renderLeaveRequests(data.myLeaveRequests);
                    handleActiveSessions(data.activeSessions);
                    allUpcomingSessions = data.upcomingSchedule || [];
                    populateLeaveSessionSelect(data.courses, data.history, allUpcomingSessions);

                    if (qrTokenFromUrl && sessionIdFromUrl) {
                        showQRScanModal(sessionIdFromUrl, qrTokenFromUrl);
                    }
                }
            })
            .catch(err => {
                setRefreshing(false);
                console.warn("Dashboard load error:", err);
                // Hiện badge offline nếu mất mạng
                const timeEl = document.getElementById("lastUpdatedTime");
                if (timeEl) timeEl.innerHTML = '<span class="text-danger"><i class="bi bi-wifi-off me-1"></i>Mất kết nối</span>';
            });
    }

    // Cảnh báo Alert Engine
    function renderAlertBanners(alerts) {
        const container = document.getElementById("studentAlertsContainer");
        container.innerHTML = "";
        if (!alerts || alerts.length === 0) return;
        alerts.forEach(a => {
            const banner = document.createElement("div");
            banner.className = "alert alert-danger border-0 d-flex justify-content-between align-items-center mb-3 p-3 shadow-sm";
            banner.style.borderRadius = "var(--radius-lg, 12px)";
            banner.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0">Cảnh báo học tập (Môn: ${escHtml(a.course_name)})</div>
                        <div class="small text-danger fw-medium">${escHtml(a.message)}</div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3 ms-3 flex-shrink-0" onclick="dismissAlert(${a.id})">Đã hiểu</button>
            `;
            container.appendChild(banner);
        });
    }

    window.dismissAlert = function(alertId) {
        fetch(BASE_URL + "/api/alerts/" + alertId, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ is_read: 1 })
        }).then(() => loadDashboardData());
    };

    // Thống kê 4 thẻ
    function renderStatsSummary(summary) {
        if (!summary) return;
        document.getElementById("statTotal").innerText = summary.total;
        document.getElementById("statPresent").innerText = summary.present;
        document.getElementById("statLate").innerText = summary.late;
        document.getElementById("statAbsent").innerText = summary.absent;
    }

    // Bảng học phần + CPI
    function renderCoursesTable(courses, scores, summary) {
        const tbody = document.querySelector("#studentCoursesTable tbody");
        tbody.innerHTML = "";
        if (!courses || courses.length === 0) {
            tbody.innerHTML = "<tr><td colspan='5' class='text-center text-muted py-4'>Bạn chưa được xếp vào học phần nào</td></tr>";
            document.getElementById("cpiIndexVal").innerText = "—";
            return;
        }

        const absentByCourse = (summary && summary.absentByCourse) ? summary.absentByCourse : {};
        let totalCPI = 0, countCPI = 0, sumAtt = 0, sumInt = 0;

        courses.forEach(c => {
            const scoreRow = scores ? scores.find(s => s.course_id == c.id) : null;
            const attPts = scoreRow ? parseInt(scoreRow.attendance_points) : 0;
            const intPts = scoreRow ? parseInt(scoreRow.interaction_points) : 0;
            const cpi = scoreRow ? parseInt(scoreRow.total_score) : 100;
            totalCPI += cpi; countCPI++;
            sumAtt += attPts; sumInt += intPts;

            const absentCount = absentByCourse[c.id] || 0;
            const cpiColor = cpi >= 80 ? "text-success" : cpi >= 60 ? "text-warning" : "text-danger";

            const row = document.createElement("tr");
            row.innerHTML = `
                <td class="fw-bold text-primary">${escHtml(c.code)}</td>
                <td class="fw-medium">${escHtml(c.name)}</td>
                <td class="text-muted small">${escHtml(c.teacher_name || "Chưa phân công")}</td>
                <td class="text-center fw-semibold ${absentCount >= 3 ? 'text-danger' : ''}">${absentCount}</td>
                <td class="text-center fw-bold ${cpiColor}">${cpi}/100</td>
            `;
            tbody.appendChild(row);
        });

        const finalCPI = countCPI > 0 ? Math.round(totalCPI / countCPI) : 100;
        document.getElementById("cpiIndexVal").innerText = finalCPI;
        document.getElementById("cpiAttendancePoints").innerText = sumAtt + "đ";
        document.getElementById("cpiInteractionPoints").innerText = sumInt + "đ";
        const dashArray = (finalCPI / 100) * 100;
        document.getElementById("cpiGaugePath").setAttribute("stroke-dasharray", dashArray + ", 100");
    }

    // Lịch sử điểm danh
    function renderAttendanceTable(history) {
        const tbody = document.querySelector("#studentAttendanceTable tbody");
        tbody.innerHTML = "";
        if (!history || history.length === 0) {
            tbody.innerHTML = "<tr><td colspan='4' class='text-center text-muted py-4'>Chưa có lịch sử điểm danh</td></tr>";
            return;
        }
        history.forEach(h => {
            const row = document.createElement("tr");
            let badge = "";
            if (h.status === "present") badge = "<span class='badge bg-success'>Có mặt</span>";
            else if (h.status === "late") badge = "<span class='badge bg-warning text-dark'>Đi muộn</span>";
            else if (h.status === "absent") badge = "<span class='badge bg-danger'>Vắng mặt</span>";
            else if (h.status === "excused") badge = "<span class='badge bg-secondary'>Có phép</span>";
            else badge = "<span class='badge bg-light text-dark'>Chưa điểm danh</span>";

            const method = h.method_name ? `<span class="badge bg-light text-muted">${escHtml(h.method_name)}</span>` : "—";
            row.innerHTML = `
                <td class="fw-medium">${formatDate(h.session_date)}</td>
                <td class="text-muted small" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(h.course_name)}</td>
                <td>${method}</td>
                <td class="text-end">${badge}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Lịch sử quiz
    function renderQuizHistory(quizHistory) {
        const tbody = document.querySelector("#studentQuizHistoryTable tbody");
        tbody.innerHTML = "";
        if (!quizHistory || quizHistory.length === 0) {
            tbody.innerHTML = "<tr><td colspan='4' class='text-center text-muted py-4'>Bạn chưa tham gia bài Quiz nào</td></tr>";
            return;
        }
        quizHistory.forEach(qh => {
            const row = document.createElement("tr");
            const t = new Date(qh.submitted_at);
            const submitTime = t.toLocaleDateString("vi-VN") + " " + t.toLocaleTimeString("vi-VN", {hour:"2-digit", minute:"2-digit"});
            const scoreColor = (qh.score / qh.total_marks) >= 0.7 ? "text-success" : "text-warning";
            row.innerHTML = `
                <td class="fw-medium">${escHtml(qh.quiz_title)}</td>
                <td class="text-muted small">${escHtml(qh.course_name || "—")}</td>
                <td class="text-muted small">${submitTime}</td>
                <td class="text-end fw-bold ${scoreColor}">${qh.score}/${qh.total_marks}đ</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Lịch sử đơn xin phép
    function renderLeaveRequests(leaves) {
        const tbody = document.querySelector("#leaveRequestsTable tbody");
        tbody.innerHTML = "";
        if (!leaves || leaves.length === 0) {
            tbody.innerHTML = "<tr><td colspan='5' class='text-center text-muted py-4'>Bạn chưa gửi đơn xin phép nào</td></tr>";
            return;
        }
        leaves.forEach(lr => {
            const row = document.createElement("tr");
            let statusBadge = "";
            if (lr.status === "pending")  statusBadge = "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split me-1'></i>Chờ duyệt</span>";
            else if (lr.status === "approved") statusBadge = "<span class='badge bg-success'><i class='bi bi-check-circle me-1'></i>Đã duyệt</span>";
            else statusBadge = "<span class='badge bg-danger'><i class='bi bi-x-circle me-1'></i>Từ chối</span>";
            row.innerHTML = `
                <td class="fw-medium">${formatDate(lr.session_date)}</td>
                <td class="text-muted small">${escHtml(lr.course_name)}</td>
                <td class="small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escHtml(lr.reason)}">${escHtml(lr.reason)}</td>
                <td class="small text-muted">${lr.teacher_note ? escHtml(lr.teacher_note) : "—"}</td>
                <td class="text-end">${statusBadge}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Active sessions
    function handleActiveSessions(activeSessions) {
        if (attInterval) clearInterval(attInterval);
        document.getElementById("attendanceActiveBox").classList.add("d-none");
        document.getElementById("alreadyAttendedBox").classList.add("d-none");

        if (!activeSessions || activeSessions.length === 0) return;

        const session = activeSessions[0];
        activeSession = session;

        if (session.student_already_attended) {
            // Đã điểm danh rồi
            document.getElementById("alreadyAttendedBox").classList.remove("d-none");
            document.getElementById("alreadyAttendedSessionName").innerText = session.course_code + " - " + session.course_name;
            const statusMap = { "present": "Có mặt ✓", "late": "Đi muộn", "excused": "Có phép" };
            document.getElementById("alreadyAttendedStatus").innerText = statusMap[session.my_attendance_status] || session.my_attendance_status;
        } else {
            // Chưa điểm danh
            document.getElementById("attendanceActiveBox").classList.remove("d-none");
            document.getElementById("activeSessionName").innerText = session.course_code + " - " + session.course_name;

            const expireTime = session.attendance_expires_at ? new Date(session.attendance_expires_at).getTime() : null;
            function updateTimer() {
                if (!expireTime) { document.getElementById("attCountdownTimer").innerText = "🔴 LIVE"; return; }
                const dist = expireTime - new Date().getTime();
                if (dist <= 0) {
                    clearInterval(attInterval);
                    document.getElementById("attCountdownTimer").innerText = "HẾT HẠN";
                    document.getElementById("attendanceActiveBox").classList.add("d-none");
                    loadDashboardData();
                    return;
                }
                const m = Math.floor((dist % (1000*60*60)) / (1000*60));
                const s = Math.floor((dist % (1000*60)) / 1000);
                document.getElementById("attCountdownTimer").innerText = (m<10?"0"+m:m)+":"+(s<10?"0"+s:s);
            }
            updateTimer();
            attInterval = setInterval(updateTimer, 1000);
        }

        // Quiz active
        loadActiveQuizzes(session.id);
    }

    function loadActiveQuizzes(sessionId) {
        fetch(BASE_URL + "/api/quizzes?session_id=" + sessionId)
            .then(res => res.json())
            .then(res => {
                if (res.status !== "success") return;
                const openQuizzes = res.data.filter(q => {
                    return !q.is_submitted && new Date(q.end_time).getTime() > new Date().getTime();
                });
                if (openQuizzes.length === 0) {
                    document.getElementById("quizzesActiveBox").classList.add("d-none");
                    return;
                }
                const quiz = openQuizzes[0];
                activeQuiz = quiz;
                document.getElementById("quizzesActiveBox").classList.remove("d-none");
                document.getElementById("activeQuizTitle").innerText = quiz.title;
                document.getElementById("activeQuizSessionName").innerText = "Tổng điểm: " + quiz.total_marks + "đ";
            });
    }

    // Submit điểm danh Code
    document.getElementById("submitCodeForm").addEventListener("submit", function(e) {
        e.preventDefault();
        if (!activeSession) return;
        const code = document.getElementById("attendanceCodeInput").value.trim();
        fetch(BASE_URL + "/api/attendance/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: activeSession.id, code: code })
        }).then(r => r.json()).then(res => {
            if (res.status === "success") {
                showToast("Thành công", res.message, "success");
                document.getElementById("attendanceCodeInput").value = "";
                loadDashboardData();
            } else {
                showToast("Lỗi điểm danh", res.message, "danger");
            }
        });
    });

    // QR Scan
    document.getElementById("btnStartFakeQRScan").addEventListener("click", function() {
        if (!activeSession) return;
        qrScanModalInstance = new bootstrap.Modal(document.getElementById("qrScanModal"));
        qrScanModalInstance.show();
        setTimeout(() => {
            fetch(BASE_URL + "/api/sessions")
                .then(r => r.json())
                .then(res => {
                    const s = res.data ? res.data.find(x => x.id == activeSession.id) : null;
                    if (s && s.qr_token) {
                        document.getElementById("qrTokenFakeInput").value = s.qr_token;
                        showToast("Camera", "Đã nhận dạng QR điểm danh!", "success");
                    }
                });
        }, 2500);
    });

    function showQRScanModal(sessionId, token) {
        qrScanModalInstance = new bootstrap.Modal(document.getElementById("qrScanModal"));
        qrScanModalInstance.show();
        document.getElementById("qrTokenFakeInput").value = token;
    }

    document.getElementById("btnConfirmQRScan").addEventListener("click", function() {
        if (!activeSession) return;
        const qrToken = document.getElementById("qrTokenFakeInput").value;
        fetch(BASE_URL + "/api/attendance/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: activeSession.id, qr_token: qrToken })
        }).then(r => r.json()).then(res => {
            if (res.status === "success") {
                showToast("Thành công", res.message, "success");
                if (qrScanModalInstance) qrScanModalInstance.hide();
                loadDashboardData();
            } else {
                showToast("Lỗi điểm danh", res.message, "danger");
            }
        });
    });

    // Log tương tác của sinh viên (Hỏi / Trả lời / Thảo luận)
    window.logInteraction = function(type) {
        if (!activeSession) { showToast("Tương tác", "Không có buổi học đang diễn ra.", "warning"); return; }
        const typeLabel = { question: "đặt câu hỏi", answer: "trả lời", discussion: "thảo luận" }[type] || type;
        fetch(BASE_URL + "/api/student/interaction", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: activeSession.id, type: type })
        }).then(r => r.json()).then(res => {
            if (res.status === "success") {
                showToast("Tương tác", res.message, "success");
                loadDashboardData();
            } else {
                showToast("Lỗi", res.message, "danger");
            }
        });
    };

    // Quiz
    document.getElementById("btnStartQuiz").addEventListener("click", function() {
        if (!activeQuiz) return;
        quizPlayModalInstance = new bootstrap.Modal(document.getElementById("quizPlayModal"));
        document.getElementById("quizPlayModalTitle").innerText = activeQuiz.title;
        quizPlayModalInstance.show();
        const endTime = new Date(activeQuiz.end_time).getTime();
        let qTimer = setInterval(() => {
            const dist = endTime - new Date().getTime();
            if (dist <= 0) { clearInterval(qTimer); document.getElementById("quizPlayTimer").innerText = "HẾT GIỜ"; submitQuizAnswers(activeQuiz.id, 0); return; }
            const m = Math.floor((dist % (1000*60*60)) / (1000*60));
            const s = Math.floor((dist % (1000*60)) / 1000);
            document.getElementById("quizPlayTimer").innerText = (m<10?"0"+m:m)+":"+(s<10?"0"+s:s);
        }, 1000);
    });

    document.querySelectorAll(".quiz-option-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.querySelectorAll(".quiz-option-btn").forEach(b => b.classList.remove("selected"));
            btn.classList.add("selected");
        });
    });

    document.getElementById("btnSubmitFakeQuiz").addEventListener("click", function() {
        if (!activeQuiz) return;
        const sel = document.querySelector(".quiz-option-btn.selected");
        if (!sel) { showToast("Quiz", "Vui lòng chọn một đáp án.", "warning"); return; }
        const isCorrect = sel.getAttribute("data-correct") === "true";
        // Show correct/wrong feedback
        document.querySelectorAll(".quiz-option-btn").forEach(b => {
            if (b.getAttribute("data-correct") === "true") b.classList.add("correct");
            else if (b.classList.contains("selected")) b.classList.add("wrong");
        });
        const score = isCorrect ? activeQuiz.total_marks : 0;
        setTimeout(() => submitQuizAnswers(activeQuiz.id, score), 800);
    });

    function submitQuizAnswers(quizId, score) {
        fetch(BASE_URL + "/api/quizzes/" + quizId + "/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ score: score })
        }).then(r => r.json()).then(res => {
            if (res.status === "success") {
                showToast("Quiz", "Nộp bài thành công! Điểm: " + score + "đ", "success");
                if (quizPlayModalInstance) quizPlayModalInstance.hide();
                document.getElementById("quizzesActiveBox").classList.add("d-none");
                loadDashboardData();
            } else {
                showToast("Lỗi nộp bài", res.message, "danger");
            }
        });
    }

    // Leave Request Modal
    function openLeaveModal() {
        leaveModalInstance = new bootstrap.Modal(document.getElementById("leaveRequestModal"));
        leaveModalInstance.show();
    }
    document.getElementById("btnOpenLeaveRequest").addEventListener("click", openLeaveModal);
    document.getElementById("btnOpenLeaveRequest2").addEventListener("click", openLeaveModal);

    function populateLeaveSessionSelect(courses, history, upcomingSchedule) {
        const sel = document.getElementById("leaveSessionSelect");
        sel.innerHTML = "<option value=''>-- Chọn buổi học --</option>";
        // Thêm các buổi học sắp tới hoặc scheduled
        const sessionMap = {};
        
        // Từ history: các buổi chưa có điểm danh (status null)
        history.forEach(h => {
            if (!h.status && !sessionMap[h.session_id]) {
                sessionMap[h.session_id] = { id: h.session_id, label: formatDate(h.session_date) + " — " + h.course_name };
            }
        });
        // Từ upcoming schedule
        upcomingSchedule.forEach(s => {
            if (!sessionMap[s.id]) {
                sessionMap[s.id] = { id: s.id, label: formatDate(s.session_date) + " — " + s.course_name + " [Sắp diễn ra]" };
            }
        });

        Object.values(sessionMap).forEach(opt => {
            const o = document.createElement("option");
            o.value = opt.id;
            o.text = opt.label;
            sel.appendChild(o);
        });

        // Nếu không có session nào
        if (Object.keys(sessionMap).length === 0) {
            const o = document.createElement("option");
            o.value = "";
            o.text = "Không có buổi học cần xin phép";
            o.disabled = true;
            sel.appendChild(o);
        }
    }

    document.getElementById("btnSubmitLeaveRequest").addEventListener("click", function() {
        const sessionId = document.getElementById("leaveSessionSelect").value;
        const reason = document.getElementById("leaveReason").value.trim();
        if (!sessionId || !reason) {
            showToast("Xin phép", "Vui lòng chọn buổi học và nhập lý do.", "warning");
            return;
        }
        fetch(BASE_URL + "/api/leave-requests", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: parseInt(sessionId), reason: reason })
        }).then(r => r.json()).then(res => {
            if (res.status === "success") {
                showToast("Đơn xin phép", res.message, "success");
                if (leaveModalInstance) leaveModalInstance.hide();
                document.getElementById("leaveRequestForm").reset();
                loadDashboardData();
            } else {
                showToast("Lỗi", res.message, "danger");
            }
        });
    });

    // Helpers
    function formatDate(d) {
        if (!d) return "—";
        const dt = new Date(d);
        return ("0"+dt.getDate()).slice(-2) + "/" + ("0"+(dt.getMonth()+1)).slice(-2) + "/" + dt.getFullYear();
    }

    function escHtml(str) {
        if (!str) return "";
        return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
    }

    function showToast(title, message, type="info") {
        const container = document.querySelector(".toast-container");
        const id = "toast_" + Date.now();
        const icon = type==="success"?"bi-check-circle-fill text-success":type==="danger"?"bi-x-circle-fill text-danger":type==="warning"?"bi-exclamation-triangle-fill text-warning":"bi-info-circle-fill text-primary";
        container.insertAdjacentHTML("beforeend", `
            <div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius:12px;border:none;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <div class="toast-header border-0 bg-white" style="border-radius:12px 12px 0 0;">
                    <i class="bi ${icon} me-2 fs-5"></i>
                    <strong class="me-auto text-dark fw-bold">${escHtml(title)}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body bg-white text-muted py-2" style="border-radius:0 0 12px 12px;font-size:0.9rem;">${escHtml(message)}</div>
            </div>
        `);
        const el = document.getElementById(id);
        const toast = new bootstrap.Toast(el, { delay: 5000 });
        toast.show();
        el.addEventListener("hidden.bs.toast", () => el.remove());
    }
});
</script>
<?php
$extraJs = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
