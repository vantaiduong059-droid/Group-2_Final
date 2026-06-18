<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Quản lý Lớp học giảng dạy</h2>
        <div class="text-muted">Xin chào, giảng viên <span class="fw-medium text-dark"><?= $_SESSION['user']['full_name'] ?></span>!</div>
    </div>
</div>

<!-- LỰA CHỌN KHÓA HỌC -->
<div class="card-modern mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted">Chọn lớp học phần giảng dạy</label>
            <select class="form-select" id="courseSelect" style="max-width: 450px;">
                <option value="" disabled selected>-- Chọn lớp học phần --</option>
                <?php foreach ($myCourses as $course): ?>
                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['class_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-none" id="courseQuickStats">
            <span class="badge bg-primary px-3 py-2 rounded-pill me-2"><i class="bi bi-people-fill me-1"></i> <span id="courseStudentsCount">0</span> Sinh viên</span>
            <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-calendar-event me-1"></i> <span id="courseSessionsCount">0</span> Buổi học</span>
        </div>
    </div>
</div>

<!-- CONTAINER CHÍNH (Hiển thị khi chọn Khóa học) -->
<div class="d-none" id="mainDashboardContainer">
    <!-- Auto-refresh status bar -->
    <div class="d-flex align-items-center gap-2 mb-3 px-1" id="autoRefreshBar" style="font-size:0.8rem;color:#64748b;">
        <div id="refreshSpinner" class="d-none">
            <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px;border-width:2px;"></div>
        </div>
        <i class="bi bi-arrow-clockwise text-success" id="refreshIdleIcon"></i>
        <span id="refreshStatusText">Cập nhật lúc <span id="lastUpdatedTime">—</span></span>
        <span class="text-muted mx-1">·</span>
        <span>Tự làm mới sau <span class="fw-semibold text-primary" id="countdownLabel">30</span>s</span>
        <button class="btn btn-link btn-sm p-0 ms-1" id="btnManualRefresh" title="Làm mới ngay" style="font-size:0.8rem;color:#3b82f6;text-decoration:none;">
            <i class="bi bi-arrow-clockwise"></i> Làm mới
        </button>
        <span class="badge bg-warning-subtle text-warning d-none" id="pausedBadge" style="font-size:0.7rem;"><i class="bi bi-pause-circle me-1"></i>Tạm dừng (tab ẩn)</span>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs border-0 mb-4 bg-white p-2 rounded shadow-sm gap-2" id="teacherTabs" role="tablist" style="border-radius: var(--radius-md) !important;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active border-0 px-4 py-2.5 fw-semibold text-muted" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessionsTabContent" type="button" role="tab" style="border-radius: var(--radius-sm);">
                <i class="bi bi-calendar-check me-2"></i> Buổi học & Điểm danh
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link border-0 px-4 py-2.5 fw-semibold text-muted" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzesTabContent" type="button" role="tab" style="border-radius: var(--radius-sm);">
                <i class="bi bi-lightning-charge me-2"></i> Quiz nhanh
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link border-0 px-4 py-2.5 fw-semibold text-muted" id="grades-tab" data-bs-toggle="tab" data-bs-target="#gradesTabContent" type="button" role="tab" style="border-radius: var(--radius-sm);">
                <i class="bi bi-bar-chart-line me-2"></i> Bảng điểm CPI & Cấu hình
            </button>
        </li>
    </ul>

    <!-- TAB CONTENTS -->
    <div class="tab-content" id="teacherTabContents">
        
        <!-- TAB 1: SESSIONS -->
        <div class="tab-pane fade show active" id="sessionsTabContent" role="tabpanel">
            <div class="row g-4">
                <!-- Danh sách buổi học -->
                <div class="col-lg-5">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title-modern mb-0">Danh sách buổi học</h5>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnSyncSessions"><i class="bi bi-arrow-clockwise"></i> Làm mới</button>
                        </div>
                        <div class="list-group list-group-flush overflow-y-auto" id="sessionsListContainer" style="max-height: 500px; padding: 0;">
                            <!-- JS render list -->
                        </div>
                    </div>
                </div>

                <!-- Khu vực điều khiển/Xem chi tiết điểm danh của buổi học -->
                <div class="col-lg-7">
                    <div class="card-modern d-flex flex-column justify-content-center align-items-center text-center p-5" id="sessionDetailPlaceholder" style="min-height: 400px; border: 2px dashed var(--border-color-darker); background: white;">
                        <i class="bi bi-calendar2-week text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="fw-semibold text-dark">Chưa chọn buổi học</h5>
                        <p class="text-muted small" style="max-width: 320px;">Vui lòng chọn một buổi học từ danh sách bên trái để mở điểm danh, chấm điểm hoặc cộng điểm tương tác.</p>
                    </div>

                    <!-- Nội dung chi tiết buổi học -->
                    <div class="card-modern d-none" id="sessionDetailContainer" style="min-height: 400px;">
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark" id="detailSessionTitle">Buổi học ngày 10/05/2026</h5>
                                <div class="text-muted small">
                                    <span class="me-3"><i class="bi bi-clock me-1"></i> <span id="detailSessionTime">08:00 - 11:00</span></span>
                                    <span><i class="bi bi-geo-alt me-1"></i> <span id="detailSessionRoom">Phòng 102</span></span>
                                </div>
                            </div>
                            <span class="badge" id="detailSessionStatus">Scheduled</span>
                        </div>

                        <!-- Điểm danh điều khiển -->
                        <div class="p-3 mb-4 rounded border" id="attendanceControlBox" style="background-color: var(--bg-main);">
                            <!-- Scheduled: Nút mở điểm danh -->
                            <div class="d-none" id="ctrlScheduled">
                                <h6 class="fw-bold mb-2"><i class="bi bi-qr-code-scan text-primary me-2"></i>Mở cổng điểm danh lớp học</h6>
                                <p class="text-muted small mb-3">Chọn hình thức điểm danh để sinh viên có thể tự thực hiện điểm danh qua tài khoản của họ.</p>
                                <div class="row g-3">
                                    <div class="col-sm-5">
                                        <label class="small text-muted mb-1 fw-medium">Hình thức</label>
                                        <select class="form-select form-select-sm" id="attMethodSelect">
                                            <option value="Code">Nhập mã Code 6 số</option>
                                            <option value="QR">Quét mã QR động</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="small text-muted mb-1 fw-medium">Thời gian hiệu lực</label>
                                        <select class="form-select form-select-sm" id="attMinutesSelect">
                                            <option value="3">3 phút</option>
                                            <option value="5" selected>5 phút</option>
                                            <option value="10">10 phút</option>
                                            <option value="15">15 phút</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-3 d-flex align-items-end">
                                        <button class="btn btn-sm btn-primary-modern w-100" id="btnOpenAttendance">Bắt đầu</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Active: Mã code / QR đang đếm ngược -->
                            <div class="d-none text-center py-2" id="ctrlActive">
                                <div class="row align-items-center">
                                    <div class="col-md-7 border-end text-md-start">
                                        <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-hourglass-split me-2"></i>Đang mở cổng điểm danh...</h6>
                                        <div class="small text-muted mb-2">Hết hạn vào lúc: <span class="fw-semibold text-dark" id="activeExpireTime">11:15:00</span></div>
                                        
                                        <!-- Phần hiển thị Code -->
                                        <div class="d-none" id="activeCodeBox">
                                            <div class="small text-muted">Mã điểm danh:</div>
                                            <div class="display-6 fw-bold letter-spacing-2 text-primary" id="activeCode">123456</div>
                                        </div>
                                        
                                        <!-- Phần hiển thị QR -->
                                        <div class="d-none" id="activeQrBox">
                                            <div class="small text-muted mb-1">Mã QR động:</div>
                                            <button class="btn btn-sm btn-outline-dark" id="btnShowQRModal"><i class="bi bi-qr-code"></i> Hiển thị mã QR lớn</button>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mt-3 mt-md-0">
                                        <div class="fs-4 fw-bold text-danger mb-2" id="countdownTimer">05:00</div>
                                        <button class="btn btn-sm btn-danger w-100" id="btnCloseAttendance">Đóng điểm danh & Đóng buổi học</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed: Buổi học kết thúc -->
                            <div class="d-none py-1" id="ctrlCompleted">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                        <div>
                                            <div class="fw-bold text-success">Đã hoàn thành buổi học</div>
                                            <div class="small text-muted">Bảng điểm CPI của lớp đã được cập nhật tự động.</div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnRecalculateCPI"><i class="bi bi-arrow-repeat"></i> Đồng bộ CPI</button>
                                </div>
                            </div>
                        </div>

                        <!-- Danh sách điểm danh sinh viên -->
                        <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people me-2 text-muted"></i>Trạng thái điểm danh sinh viên</span>
                            <span class="small text-muted fw-normal" id="sessionAttendanceSummary">0/0 Có mặt</span>
                        </h6>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table-modern" id="sessionStudentsTable">
                                <thead>
                                    <tr>
                                        <th>Họ và tên</th>
                                        <th>Hình thức</th>
                                        <th>Thời gian</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Hành động / Tương tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Render sinh viên bằng JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: QUIZZES -->
        <div class="tab-pane fade" id="quizzesTabContent" role="tabpanel">
            <div class="row g-4">
                <!-- Form tạo quiz & Danh sách Quiz -->
                <div class="col-lg-5">
                    <!-- Form tạo -->
                    <div class="card-modern mb-4">
                        <h5 class="card-title-modern"><i class="bi bi-plus-circle text-primary me-2"></i>Tạo Quiz nhanh trong lớp</h5>
                        <form id="createQuizForm">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Chọn buổi học</label>
                                <select class="form-select form-select-sm" id="quizSessionSelect" required>
                                    <!-- Render các buổi học -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Tiêu đề Quiz</label>
                                <input type="text" class="form-control form-control-sm" id="quizTitle" placeholder="Ví dụ: Quiz ôn tập MVC 5 phút" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Tổng điểm tối đa</label>
                                    <input type="number" class="form-control form-control-sm" id="quizTotalMarks" value="10" min="1" max="100" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Thời gian làm (phút)</label>
                                    <select class="form-select form-select-sm" id="quizDurationSelect">
                                        <option value="5" selected>5 phút</option>
                                        <option value="10">10 phút</option>
                                        <option value="15">15 phút</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary-modern w-100"><i class="bi bi-play-fill me-1"></i>Kích hoạt Quiz</button>
                        </form>
                    </div>

                    <!-- Danh sách Quiz đã tạo -->
                    <div class="card-modern">
                        <h5 class="card-title-modern">Lịch sử Quiz đã kích hoạt</h5>
                        <div class="list-group list-group-flush overflow-y-auto" id="quizzesListContainer" style="max-height: 250px; padding: 0;">
                            <!-- Render Quiz list -->
                        </div>
                    </div>
                </div>

                <!-- Danh sách sinh viên làm bài -->
                <div class="col-lg-7">
                    <div class="card-modern d-flex flex-column justify-content-center align-items-center text-center p-5" id="quizSubmissionsPlaceholder" style="min-height: 400px; border: 2px dashed var(--border-color-darker); background: white;">
                        <i class="bi bi-file-earmark-text text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="fw-semibold text-dark">Chưa chọn phiên Quiz</h5>
                        <p class="text-muted small" style="max-width: 320px;">Vui lòng chọn một phiên Quiz ở bên trái để theo dõi kết quả nộp bài của sinh viên.</p>
                    </div>

                    <div class="card-modern d-none" id="quizSubmissionsContainer" style="min-height: 400px;">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold mb-1 text-dark" id="detailQuizTitle">Quiz MVC Ôn Tập</h5>
                            <div class="text-muted small">
                                <span class="me-3"><i class="bi bi-calendar3 me-1"></i> Kích hoạt: <span id="detailQuizTime">-</span></span>
                                <span><i class="bi bi-award me-1"></i> Tổng điểm: <span id="detailQuizMarks">10</span></span>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3"><i class="bi bi-check2-square text-success me-2"></i>Danh sách sinh viên đã nộp bài</h6>
                        <div class="table-responsive">
                            <table class="table-modern" id="quizSubmissionsTable">
                                <thead>
                                    <tr>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Thời gian nộp</th>
                                        <th class="text-end">Điểm số đạt được</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Render list nộp bài -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: CPI & CONFIG -->
        <div class="tab-pane fade" id="gradesTabContent" role="tabpanel">
            <div class="row g-4">
                <!-- Cấu hình rules -->
                <div class="col-lg-4">
                    <div class="card-modern">
                        <h5 class="card-title-modern"><i class="bi bi-gear-fill text-muted me-2"></i>Quy tắc tính điểm Chuyên cần & Tương tác (CPI)</h5>
                        <form id="rulesForm">
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Điểm thành phần (Hệ số)</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Có mặt đúng giờ (Present)</label>
                                <input type="number" class="form-control form-control-sm" id="rulePresent" value="2" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Đi muộn (Late)</label>
                                <input type="number" class="form-control form-control-sm" id="ruleLate" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Vắng mặt (Absent)</label>
                                <input type="number" class="form-control form-control-sm" id="ruleAbsent" value="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Cộng điểm/lần phát biểu (Interaction)</label>
                                <input type="number" class="form-control form-control-sm" id="ruleInteraction" value="1" required>
                            </div>

                            <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3 text-primary">Trọng số (%)</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Chuyên cần (%)</label>
                                    <input type="number" class="form-control form-control-sm" id="weightAttendance" value="50" min="0" max="100" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Quiz nhanh (%)</label>
                                    <input type="number" class="form-control form-control-sm" id="weightQuiz" value="50" min="0" max="100" readonly>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary-modern w-100"><i class="bi bi-save me-1"></i>Lưu cấu hình quy tắc</button>
                        </form>
                    </div>
                </div>

                <!-- Bảng điểm tổng hợp CPI -->
                <div class="col-lg-8">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title-modern mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Bảng điểm chuyên cần & tham gia lớp học (CPI)</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()"><i class="bi bi-printer"></i> In bảng điểm</button>
                                <button class="btn btn-sm btn-primary-modern" id="btnSyncAllCPI"><i class="bi bi-calculator"></i> Tính toán lại toàn bộ</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table-modern" id="cpiTable">
                                <thead>
                                    <tr>
                                        <th>Học sinh</th>
                                        <th>Email</th>
                                        <th class="text-center">Điểm chuyên cần</th>
                                        <th class="text-center">Điểm tương tác</th>
                                        <th class="text-center">Chỉ số CPI (CPI Index)</th>
                                        <th class="text-center">Đánh giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Render bảng điểm bằng JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL QR LỚN -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code me-2 text-primary"></i>Mã QR Điểm danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-muted small">Sinh viên quét mã QR bên dưới bằng điện thoại hoặc giao diện sinh viên để điểm danh.</p>
                <div class="bg-light p-4 rounded d-inline-block shadow-sm mb-3">
                    <img id="qrImageElement" src="" alt="QR Code" style="width: 250px; height: 250px; object-fit: contain;">
                </div>
                <div class="fs-5 fw-bold text-danger" id="qrModalCountdown">05:00</div>
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
    const courseSelect = document.getElementById("courseSelect");
    const mainDashboardContainer = document.getElementById("mainDashboardContainer");
    const courseQuickStats = document.getElementById("courseQuickStats");
    const sessionsListContainer = document.getElementById("sessionsListContainer");
    
    let currentCourseId = null;
    let currentSessionId = null;
    let currentQuizId = null;
    let countdownInterval = null;
    
    // ============================================
    // AUTO-POLLING CONFIG
    // ============================================
    const POLL_INTERVAL = 30; // giây
    let pollCountdown = POLL_INTERVAL;
    let pollTimer = null;
    let isPaused = false;

    function formatTime(date) {
        return date.toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    }

    function setRefreshing(loading) {
        const spinner = document.getElementById("refreshSpinner");
        const idleIcon = document.getElementById("refreshIdleIcon");
        const btnManual = document.getElementById("btnManualRefresh");
        if (spinner) spinner.classList.toggle("d-none", !loading);
        if (idleIcon) idleIcon.classList.toggle("d-none", loading);
        if (btnManual) btnManual.disabled = loading;
    }

    function pollActiveTabData() {
        if (!currentCourseId) return Promise.resolve();
        
        setRefreshing(true);
        const activeTab = document.querySelector("#teacherTabs .nav-link.active");
        let promise = Promise.resolve();

        if (activeTab) {
            if (activeTab.id === "sessions-tab") {
                let p1 = loadSessionsList(currentCourseId);
                let p2 = currentSessionId ? loadSessionAttendance(currentSessionId) : Promise.resolve();
                promise = Promise.all([p1, p2]);
            } else if (activeTab.id === "quizzes-tab") {
                let p1 = loadQuizzesHistory(currentCourseId);
                let p2 = currentQuizId ? showQuizSubmissions(currentQuizId) : Promise.resolve();
                promise = Promise.all([p1, p2]);
            } else if (activeTab.id === "grades-tab") {
                promise = loadCPITable(currentCourseId);
            }
        }

        return promise
            .then(() => {
                setRefreshing(false);
                const timeEl = document.getElementById("lastUpdatedTime");
                if (timeEl) timeEl.innerText = formatTime(new Date());
                pollCountdown = POLL_INTERVAL;
                const label = document.getElementById("countdownLabel");
                if (label) label.innerText = pollCountdown;
            })
            .catch(err => {
                setRefreshing(false);
                console.warn("Auto-polling error:", err);
                const timeEl = document.getElementById("lastUpdatedTime");
                if (timeEl) timeEl.innerHTML = '<span class="text-danger"><i class="bi bi-wifi-off me-1"></i>Mất kết nối</span>';
            });
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollCountdown = POLL_INTERVAL;
        const label = document.getElementById("countdownLabel");
        if (label) label.innerText = pollCountdown;
        const timeEl = document.getElementById("lastUpdatedTime");
        if (timeEl && timeEl.innerText === "—") {
            timeEl.innerText = formatTime(new Date());
        }

        pollTimer = setInterval(() => {
            if (isPaused) return;
            pollCountdown--;
            const label = document.getElementById("countdownLabel");
            if (label) label.innerText = pollCountdown;

            if (pollCountdown <= 0) {
                pollCountdown = POLL_INTERVAL;
                pollActiveTabData();
            }
        }, 1000);
    }

    // Tạm dừng khi ẩn tab (tiết kiệm tài nguyên)
    document.addEventListener("visibilitychange", () => {
        isPaused = document.hidden;
        const pausedBadge = document.getElementById("pausedBadge");
        if (pausedBadge) pausedBadge.classList.toggle("d-none", !isPaused);
        if (!isPaused && currentCourseId) {
            pollCountdown = POLL_INTERVAL;
            pollActiveTabData();
        }
    });

    // Nút làm mới thủ công
    document.getElementById("btnManualRefresh").addEventListener("click", () => {
        pollCountdown = POLL_INTERVAL;
        const label = document.getElementById("countdownLabel");
        if (label) label.innerText = pollCountdown;
        pollActiveTabData();
    });
    
    // Cập nhật Trọng số tự động (Tổng = 100)
    const weightAttendance = document.getElementById("weightAttendance");
    const weightQuiz = document.getElementById("weightQuiz");
    weightAttendance.addEventListener("input", function() {
        let val = parseInt(weightAttendance.value) || 0;
        if (val > 100) val = 100;
        if (val < 0) val = 0;
        weightAttendance.value = val;
        weightQuiz.value = 100 - val;
    });

    // 1. Khi chọn Lớp học phần
    courseSelect.addEventListener("change", function() {
        currentCourseId = this.value;
        if (!currentCourseId) return;
        
        mainDashboardContainer.classList.remove("d-none");
        courseQuickStats.classList.remove("d-none");
        
        // Reset view chi tiết
        document.getElementById("sessionDetailPlaceholder").classList.remove("d-none");
        document.getElementById("sessionDetailContainer").classList.add("d-none");
        
        // Tải dữ liệu lớp học phần
        loadCourseData(currentCourseId);
        
        // Khởi động auto-polling
        startPolling();
    });
    
    // Nút làm mới danh sách buổi học
    document.getElementById("btnSyncSessions").addEventListener("click", () => {
        if(currentCourseId) loadCourseData(currentCourseId);
    });

    // Tải dữ liệu của khóa học
    function loadCourseData(courseId) {
        // Tải danh sách buổi học & rules qua API
        return fetch(BASE_URL + "/api/courses/" + courseId)
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const data = res.data;
                    document.getElementById("courseStudentsCount").innerText = data.student_count || 0;
                    
                    // Cấu hình quy tắc
                    document.getElementById("rulePresent").value = data.rule_present_points;
                    document.getElementById("ruleLate").value = data.rule_late_points;
                    document.getElementById("ruleAbsent").value = data.rule_absent_points;
                    document.getElementById("ruleInteraction").value = data.rule_interaction_points;
                    document.getElementById("weightAttendance").value = data.rule_attendance_weight;
                    document.getElementById("weightQuiz").value = data.rule_quiz_weight;
                    
                    // Nạp danh sách buổi học vào Quiz Form
                    fetchSessionsForQuizSelect(courseId);
                    
                    // Tải danh sách buổi học bên trái
                    loadSessionsList(courseId);
                    
                    // Tải bảng điểm CPI
                    loadCPITable(courseId);
                }
            });
    }

    function fetchSessionsForQuizSelect(courseId) {
        return fetch(BASE_URL + "/api/sessions")
            .then(res => res.json())
            .then(res => {
                if(res.status === "success") {
                    const quizSessionSelect = document.getElementById("quizSessionSelect");
                    quizSessionSelect.innerHTML = "";
                    const filtered = res.data.filter(s => s.course_id == courseId);
                    filtered.forEach(s => {
                        const opt = document.createElement("option");
                        opt.value = s.id;
                        opt.innerText = "Buổi học ngày " + formatDate(s.session_date) + " (" + s.start_time.substring(0, 5) + ")";
                        quizSessionSelect.appendChild(opt);
                    });
                }
            });
    }

    // Tải danh sách buổi học
    function loadSessionsList(courseId) {
        return fetch(BASE_URL + "/api/sessions")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const sessions = res.data.filter(s => s.course_id == courseId);
                    document.getElementById("courseSessionsCount").innerText = sessions.length;
                    
                    sessionsListContainer.innerHTML = "";
                    if (sessions.length === 0) {
                        sessionsListContainer.innerHTML = "<div class=\"text-center text-muted py-4\">Chưa có buổi học nào được lên lịch</div>";
                        return;
                    }
                    
                    sessions.forEach(s => {
                        const activeClass = (currentSessionId == s.id) ? "active" : "";
                        let badgeColor = "bg-secondary";
                        let statusText = "Chưa học";
                        
                        if (s.status === "active") {
                            badgeColor = "bg-danger animate-pulse";
                            statusText = "Đang điểm danh";
                        } else if (s.status === "completed") {
                            badgeColor = "bg-success";
                            statusText = "Đã xong";
                        }
                        
                        const item = document.createElement("a");
                        item.href = "#";
                        item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-3 " + activeClass;
                        item.style.borderRadius = "var(--radius-md)";
                        item.style.marginBottom = "8px";
                        item.innerHTML = `
                            <div>
                                <div class="fw-bold mb-1 ${activeClass ? "text-primary" : "text-dark"}">Buổi ngày ${formatDate(s.session_date)}</div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i> ${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)}</div>
                            </div>
                            <span class="badge ${badgeColor}">${statusText}</span>
                        `;
                        
                        item.addEventListener("click", (e) => {
                            e.preventDefault();
                            // Gỡ active của các item cũ
                            document.querySelectorAll("#sessionsListContainer a").forEach(a => a.classList.remove("active"));
                            item.classList.add("active");
                            
                            showSessionDetail(s.id);
                        });
                        
                        sessionsListContainer.appendChild(item);
                    });
                }
            });
    }

    // Hiển thị chi tiết buổi học
    function showSessionDetail(sessionId) {
        currentSessionId = sessionId;
        document.getElementById("sessionDetailPlaceholder").classList.add("d-none");
        document.getElementById("sessionDetailContainer").classList.remove("d-none");
        
        return fetch(BASE_URL + "/api/sessions")
            .then(res => res.json())
            .then(res => {
                if(res.status === "success") {
                    const session = res.data.find(s => s.id == sessionId);
                    if(!session) return;
                    
                    document.getElementById("detailSessionTitle").innerText = "Buổi học ngày " + formatDate(session.session_date);
                    document.getElementById("detailSessionTime").innerText = session.start_time.substring(0,5) + " - " + session.end_time.substring(0,5);
                    document.getElementById("detailSessionRoom").innerText = session.room;
                    
                    const badge = document.getElementById("detailSessionStatus");
                    badge.className = "badge ";
                    if (session.status === "scheduled") {
                        badge.classList.add("bg-secondary");
                        badge.innerText = "Scheduled";
                        
                        document.getElementById("ctrlScheduled").classList.remove("d-none");
                        document.getElementById("ctrlActive").classList.add("d-none");
                        document.getElementById("ctrlCompleted").classList.add("d-none");
                    } else if (session.status === "active") {
                        badge.classList.add("bg-danger");
                        badge.innerText = "Active";
                        
                        document.getElementById("ctrlScheduled").classList.add("d-none");
                        document.getElementById("ctrlActive").classList.remove("d-none");
                        document.getElementById("ctrlCompleted").classList.add("d-none");
                        
                        // Đang mở điểm danh -> Setup hiển thị Code / QR
                        setupActiveSessionUI(session);
                    } else {
                        badge.classList.add("bg-success");
                        badge.innerText = "Completed";
                        
                        document.getElementById("ctrlScheduled").classList.add("d-none");
                        document.getElementById("ctrlActive").classList.add("d-none");
                        document.getElementById("ctrlCompleted").classList.remove("d-none");
                    }
                    
                    // Tải danh sách điểm danh thực tế
                    loadSessionAttendance(sessionId);
                }
            });
    }

    function setupActiveSessionUI(session) {
        if(countdownInterval) clearInterval(countdownInterval);
        
        const expireTime = new Date(session.attendance_expires_at).getTime();
        const expireStr = new Date(session.attendance_expires_at).toLocaleTimeString("vi-VN", {hour: "2-digit", minute:"2-digit", second:"2-digit"});
        document.getElementById("activeExpireTime").innerText = expireStr;
        
        if (session.attendance_code) {
            document.getElementById("activeCodeBox").classList.remove("d-none");
            document.getElementById("activeQrBox").classList.add("d-none");
            document.getElementById("activeCode").innerText = session.attendance_code;
        } else if (session.qr_token) {
            document.getElementById("activeCodeBox").classList.add("d-none");
            document.getElementById("activeQrBox").classList.remove("d-none");
            
            // Thiết lập QR code (Sử dụng API QR public hoặc tự sinh ảnh)
            // Ta dùng dịch vụ QR code generator API miễn phí
            const qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(BASE_URL + "/student/dashboard?qr_token=" + session.qr_token + "&session_id=" + session.id);
            document.getElementById("qrImageElement").src = qrUrl;
        }
        
        // Đếm ngược
        function updateTimer() {
            const now = new Date().getTime();
            const distance = expireTime - now;
            
            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById("countdownTimer").innerText = "ĐÃ HẾT HẠN";
                document.getElementById("countdownTimer").className = "fs-5 fw-bold text-danger mb-2";
                // Tự động đóng điểm danh
                autoCloseAttendance(session.id);
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            const timeStr = (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
            document.getElementById("countdownTimer").innerText = timeStr;
            document.getElementById("qrModalCountdown").innerText = timeStr;
            document.getElementById("countdownTimer").className = "fs-4 fw-bold text-danger mb-2";
        }
        
        updateTimer();
        countdownInterval = setInterval(updateTimer, 1000);
    }
    
    // Tự động đóng điểm danh khi hết hạn
    function autoCloseAttendance(sessionId) {
        clearInterval(countdownInterval);
        fetch(BASE_URL + "/api/sessions/" + sessionId + "/stop-attendance", { method: "POST" })
            .then(res => res.json())
            .then(res => {
                showToast("Hệ thống", "Đã tự động đóng điểm danh do hết thời gian.", "warning");
                showSessionDetail(sessionId);
                loadCPITable(currentCourseId);
            });
    }

    // Tải danh sách học sinh điểm danh của buổi học
    function loadSessionAttendance(sessionId) {
        return fetch(BASE_URL + "/api/sessions/" + sessionId + "/attendance")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const records = res.data;
                    const tbody = document.querySelector("#sessionStudentsTable tbody");
                    tbody.innerHTML = "";
                    
                    let presentCount = 0;
                    
                    records.forEach(r => {
                        if (r.status === "present" || r.status === "late") {
                            presentCount++;
                        }
                        
                        const row = document.createElement("tr");
                        
                        // Cột họ tên kèm avatar
                        const userCell = `
                            <div class="table-user-cell">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(r.full_name)}&background=3b82f6&color=fff" class="table-user-avatar">
                                <div>
                                    <span class="table-user-name">${r.full_name}</span>
                                    <div class="small text-muted" style="font-size:0.75rem;">${r.email}</div>
                                </div>
                            </div>
                        `;
                        
                        // Badge hình thức
                        let methodText = "-";
                        if (r.method_name === "QR") methodText = "<span class=\"badge bg-info-subtle text-info\">QR</span>";
                        else if (r.method_name === "Code") methodText = "<span class=\"badge bg-primary-subtle text-primary\">Code</span>";
                        else if (r.method_name === "Manual") methodText = "<span class=\"badge bg-secondary-subtle text-secondary\">Chấm tay</span>";
                        
                        // Định dạng thời gian
                        const timeText = r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString("vi-VN", {hour:"2-digit", minute:"2-digit"}) : "-";
                        
                        // Chọn trạng thái (select dropdown hoặc badges)
                        let statusBadge = "";
                        if (r.status === "present") statusBadge = "<span class=\"badge bg-success\">Có mặt</span>";
                        else if (r.status === "late") statusBadge = "<span class=\"badge bg-warning\">Đi muộn</span>";
                        else if (r.status === "absent") statusBadge = "<span class=\"badge bg-danger\">Vắng</span>";
                        else if (r.status === "excused") statusBadge = "<span class=\"badge bg-secondary\">Có phép</span>";
                        else statusBadge = "<span class=\"badge bg-light text-dark\">Chưa điểm danh</span>";
                        
                        // Action buttons (Chấm điểm danh tay và cộng điểm phát biểu)
                        const actionCell = `
                            <div class="d-flex justify-content-end gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.75rem;">
                                        Chấm lại
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px;">
                                        <li><a class="dropdown-item text-success py-1.5" href="#" onclick="changeStatusManual(${sessionId}, ${r.student_id}, 'present')"><i class="bi bi-check-circle me-2"></i>Có mặt</a></li>
                                        <li><a class="dropdown-item text-warning py-1.5" href="#" onclick="changeStatusManual(${sessionId}, ${r.student_id}, 'late')"><i class="bi bi-clock me-2"></i>Đi muộn</a></li>
                                        <li><a class="dropdown-item text-danger py-1.5" href="#" onclick="changeStatusManual(${sessionId}, ${r.student_id}, 'absent')"><i class="bi bi-x-circle me-2"></i>Vắng mặt</a></li>
                                        <li><a class="dropdown-item text-secondary py-1.5" href="#" onclick="changeStatusManual(${sessionId}, ${r.student_id}, 'excused')"><i class="bi bi-slash-circle me-2"></i>Có phép</a></li>
                                    </ul>
                                </div>
                                <button class="btn btn-sm btn-primary-modern py-0 px-2" onclick="addInteraction(${sessionId}, ${r.student_id})" title="Cộng 1 điểm phát biểu" style="font-size:0.75rem; height:26px;">
                                    <i class="bi bi-plus-lg"></i> <i class="bi bi-chat-dots"></i>
                                </button>
                            </div>
                        `;
                        
                        row.innerHTML = `
                            <td>${userCell}</td>
                            <td>${methodText}</td>
                            <td class="text-muted">${timeText}</td>
                            <td>${statusBadge}</td>
                            <td>${actionCell}</td>
                        `;
                        tbody.appendChild(row);
                    });
                    
                    document.getElementById("sessionAttendanceSummary").innerText = presentCount + "/" + records.length + " Có mặt";
                }
            });
    }

    // Đổi trạng thái điểm danh thủ công qua window scope
    window.changeStatusManual = function(sessionId, studentId, status) {
        fetch(BASE_URL + "/api/sessions/" + sessionId + "/attendance", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ student_id: studentId, status: status })
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === "success") {
                showToast("Điểm danh", "Đã cập nhật trạng thái điểm danh thủ công.", "success");
                loadSessionAttendance(sessionId);
                loadCPITable(currentCourseId);
            } else {
                showToast("Điểm danh", res.message, "danger");
            }
        });
    }

    // Cộng điểm phát biểu qua window scope
    window.addInteraction = function(sessionId, studentId) {
        fetch(BASE_URL + "/api/interactions", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: sessionId, student_id: studentId, type: "answer", points_awarded: 1 })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Điểm tương tác", "Đã cộng +1 điểm phát biểu thành công cho sinh viên.", "success");
                loadCPITable(currentCourseId);
            } else {
                showToast("Điểm tương tác", res.message, "danger");
            }
        });
    }

    // Mở điểm danh
    document.getElementById("btnOpenAttendance").addEventListener("click", function() {
        if(!currentSessionId) return;
        
        const method = document.getElementById("attMethodSelect").value;
        const minutes = document.getElementById("attMinutesSelect").value;
        
        fetch(BASE_URL + "/api/sessions/" + currentSessionId + "/start-attendance", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ method: method, minutes: minutes })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Điểm danh", "Đã kích hoạt phiên điểm danh lớp học.", "success");
                showSessionDetail(currentSessionId);
                loadSessionsList(currentCourseId);
            } else {
                showToast("Lỗi", res.message, "danger");
            }
        });
    });

    // Đóng điểm danh
    document.getElementById("btnCloseAttendance").addEventListener("click", function() {
        if(!currentSessionId) return;
        
        fetch(BASE_URL + "/api/sessions/" + currentSessionId + "/stop-attendance", { method: "POST" })
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    showToast("Điểm danh", "Đã đóng điểm danh và kết thúc buổi học.", "success");
                    clearInterval(countdownInterval);
                    showSessionDetail(currentSessionId);
                    loadSessionsList(currentCourseId);
                    loadCPITable(currentCourseId);
                } else {
                    showToast("Lỗi", res.message, "danger");
                }
            });
    });
    
    // Nút đồng bộ CPI thủ công của buổi học
    document.getElementById("btnRecalculateCPI").addEventListener("click", function() {
        if (currentCourseId) {
            loadCPITable(currentCourseId);
            showToast("Đồng bộ", "Đã cập nhật chỉ số chuyên cần CPI mới nhất.", "success");
        }
    });

    // Hiển thị modal QR lớn
    document.getElementById("btnShowQRModal").addEventListener("click", function() {
        const modal = new bootstrap.Modal(document.getElementById("qrModal"));
        modal.show();
    });

    // 2. PHẦN QUIZ
    
    // Submit tạo quiz mới
    document.getElementById("createQuizForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const sessionId = document.getElementById("quizSessionSelect").value;
        const title = document.getElementById("quizTitle").value;
        const totalMarks = document.getElementById("quizTotalMarks").value;
        const duration = document.getElementById("quizDurationSelect").value;
        
        // Tính thời gian kết thúc
        const startTime = new Date();
        const endTime = new Date(startTime.getTime() + duration * 60000);
        
        const formatDateTime = (date) => {
            return date.getFullYear() + "-" + 
                   ("0" + (date.getMonth() + 1)).slice(-2) + "-" + 
                   ("0" + date.getDate()).slice(-2) + " " + 
                   ("0" + date.getHours()).slice(-2) + ":" + 
                   ("0" + date.getMinutes()).slice(-2) + ":" + 
                   ("0" + date.getSeconds()).slice(-2);
        }
        
        fetch(BASE_URL + "/api/quizzes", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                session_id: sessionId,
                title: title,
                total_marks: totalMarks,
                start_time: formatDateTime(startTime),
                end_time: formatDateTime(endTime)
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Quiz", "Đã tạo và kích hoạt quiz thành công!", "success");
                document.getElementById("quizTitle").value = "";
                loadQuizzesHistory(currentCourseId);
            } else {
                showToast("Quiz lỗi", res.message, "danger");
            }
        });
    });

    // Tải danh sách quiz đã kích hoạt
    function loadQuizzesHistory(courseId) {
        // Ta cần duyệt các buổi học của Course để tìm quiz
        return fetch(BASE_URL + "/api/sessions")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const sessionIds = res.data.filter(s => s.course_id == courseId).map(s => s.id);
                    const quizzesListContainer = document.getElementById("quizzesListContainer");
                    quizzesListContainer.innerHTML = "";
                    
                    if (sessionIds.length === 0) {
                        quizzesListContainer.innerHTML = "<div class=\"text-center text-muted py-3\">Chưa có Quiz nào</div>";
                        return;
                    }
                    
                    // Lần lượt fetch quizzes cho các sessions
                    // Lấy tất cả quiz, lọc client side cho tiện
                    fetch(BASE_URL + "/api/quizzes?session_id=" + sessionIds[0]) // lấy bừa một cái, thực tế ta có thể lấy API thông minh hơn
                    // Tuy nhiên, do API quiz yêu cầu session_id, ta có thể duyệt song song hoặc sửa API.
                    // Đơn giản hơn: fetch quiz cho tất cả sessions của lớp học phần
                    let promises = sessionIds.map(sid => 
                        fetch(BASE_URL + "/api/quizzes?session_id=" + sid).then(r => r.json())
                    );
                    
                    return Promise.all(promises).then(results => {
                        let allQuizzes = [];
                        results.forEach(res => {
                            if (res.status === "success") allQuizzes = allQuizzes.concat(res.data);
                        });
                        
                        if (allQuizzes.length === 0) {
                            quizzesListContainer.innerHTML = "<div class=\"text-center text-muted py-3\">Chưa có Quiz nào được tạo</div>";
                            return;
                        }
                        
                        allQuizzes.forEach(q => {
                            const isExpired = new Date(q.end_time).getTime() < new Date().getTime();
                            const badgeColor = isExpired ? "bg-secondary" : "bg-success animate-pulse";
                            const statusText = isExpired ? "Đã đóng" : "Đang mở";
                            
                            const item = document.createElement("a");
                            item.href = "#";
                            item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 " + (currentQuizId == q.id ? "active" : "");
                            item.style.borderRadius = "var(--radius-sm)";
                            item.style.marginBottom = "5px";
                            item.innerHTML = `
                                <div>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width:180px;">${q.title}</div>
                                    <div class="small text-muted" style="font-size:0.75rem;">Hạn nộp: ${q.end_time.substring(11, 16)}</div>
                                </div>
                                <span class="badge ${badgeColor}">${statusText}</span>
                            `;
                            
                            item.addEventListener("click", (e) => {
                                e.preventDefault();
                                document.querySelectorAll("#quizzesListContainer a").forEach(a => a.classList.remove("active"));
                                item.classList.add("active");
                                showQuizSubmissions(q.id);
                            });
                            
                            quizzesListContainer.appendChild(item);
                        });
                    });
                }
            });
    }

    // Xem bài nộp của một Quiz
    function showQuizSubmissions(quizId) {
        currentQuizId = quizId;
        document.getElementById("quizSubmissionsPlaceholder").classList.add("d-none");
        document.getElementById("quizSubmissionsContainer").classList.remove("d-none");
        
        // Fetch chi tiết quiz và bài nộp
        return fetch(BASE_URL + "/api/quizzes/" + quizId + "/submissions")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const submissions = res.data;
                    const tbody = document.querySelector("#quizSubmissionsTable tbody");
                    tbody.innerHTML = "";
                    
                    // Lấy thông tin tiêu đề quiz từ list
                    document.getElementById("detailQuizTitle").innerText = "Danh sách bài làm";
                    
                    if (submissions.length === 0) {
                        tbody.innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-4\">Chưa có sinh viên nào nộp bài</td></tr>";
                        return;
                    }
                    
                    submissions.forEach(s => {
                        const row = document.createElement("tr");
                        const nameCell = `
                            <div class="table-user-cell">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(s.student_name)}&background=10b981&color=fff" class="table-user-avatar">
                                <span class="table-user-name">${s.student_name}</span>
                            </div>
                        `;
                        const submitTime = new Date(s.submitted_at).toLocaleTimeString("vi-VN", {hour:"2-digit", minute:"2-digit", second:"2-digit"});
                        
                        row.innerHTML = `
                            <td>${nameCell}</td>
                            <td class="text-muted">${s.student_email}</td>
                            <td class="text-muted">${submitTime}</td>
                            <td class="text-end fw-bold text-success">${s.score}đ</td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            });
    }

    // Khi chuyển tab, tự động tải dữ liệu
    const tabEl = document.querySelectorAll("button[data-bs-toggle=\"tab\"]");
    tabEl.forEach(t => {
        t.addEventListener("shown.bs.tab", function (event) {
            if (event.target.id === "quizzes-tab") {
                if (currentCourseId) loadQuizzesHistory(currentCourseId);
            } else if (event.target.id === "grades-tab") {
                if (currentCourseId) loadCPITable(currentCourseId);
            }
        });
    });

    // 3. PHẦN CPI & CẤU HÌNH RULES
    
    // Tải bảng điểm CPI của lớp
    function loadCPITable(courseId) {
        // Đồng bộ CPI trước bằng POST
        return fetch(BASE_URL + "/api/engagement/calculate", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                const list = res.data;
                const tbody = document.querySelector("#cpiTable tbody");
                tbody.innerHTML = "";
                
                if (list.length === 0) {
                    tbody.innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-muted py-4\">Chưa có sinh viên nào đăng ký lớp học này</td></tr>";
                    return;
                }
                
                list.forEach(r => {
                    const row = document.createElement("tr");
                    
                    // Định danh SV
                    const userCell = `
                        <div class="table-user-cell">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(r.student_name)}&background=3b82f6&color=fff" class="table-user-avatar">
                            <span class="table-user-name">${r.student_name}</span>
                        </div>
                    `;
                    
                    // Đánh giá dựa trên score
                    let rankBadge = "";
                    if (r.total_score >= 85) rankBadge = "<span class=\"badge bg-success-light text-success rounded-pill px-2.5 py-1\">Xuất sắc</span>";
                    else if (r.total_score >= 70) rankBadge = "<span class=\"badge bg-primary-light text-primary rounded-pill px-2.5 py-1\">Khá</span>";
                    else if (r.total_score >= 50) rankBadge = "<span class=\"badge bg-warning-light text-warning rounded-pill px-2.5 py-1\">Trung bình</span>";
                    else rankBadge = "<span class=\"badge bg-danger-light text-danger rounded-pill px-2.5 py-1\">Cảnh báo</span>";

                    row.innerHTML = `
                        <td>${userCell}</td>
                        <td class="text-muted">${r.student_email}</td>
                        <td class="text-center fw-medium">${r.attendance_points}đ</td>
                        <td class="text-center fw-medium">${r.interaction_points}đ</td>
                        <td class="text-center fw-bold text-primary fs-6">${r.total_score}/100</td>
                        <td class="text-center">${rankBadge}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        });
    }

    // Submit lưu quy tắc rules
    document.getElementById("rulesForm").addEventListener("submit", function(e) {
        e.preventDefault();
        if(!currentCourseId) return;
        
        const data = {
            rule_present_points: document.getElementById("rulePresent").value,
            rule_late_points: document.getElementById("ruleLate").value,
            rule_absent_points: document.getElementById("ruleAbsent").value,
            rule_interaction_points: document.getElementById("ruleInteraction").value,
            rule_attendance_weight: document.getElementById("weightAttendance").value,
            rule_quiz_weight: document.getElementById("weightQuiz").value,
            // Cần truyền lại các trường bắt buộc của Course
            code: document.getElementById("courseSelect").selectedOptions[0].text.split(" - ")[0],
            name: document.getElementById("courseSelect").selectedOptions[0].text.split(" - ")[1].split(" (")[0],
            class_code: document.getElementById("courseSelect").selectedOptions[0].text.split(" (")[1].replace(")", ""),
            credits: 3,
            periods: 45,
            teacher_id: ' . $_SESSION['user']['id'] . ',
            description: ""
        };
        
        fetch(BASE_URL + "/api/courses/" + currentCourseId, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Quy tắc", "Đã cập nhật cấu hình quy tắc và trọng số thành công.", "success");
                loadCPITable(currentCourseId);
            } else {
                showToast("Cập nhật lỗi", res.message, "danger");
            }
        });
    });

    // Nút tính toán lại toàn bộ CPI
    document.getElementById("btnSyncAllCPI").addEventListener("click", function() {
        if(currentCourseId) {
            loadCPITable(currentCourseId);
            showToast("CPI", "Đã cập nhật và đồng bộ lại CPI toàn lớp.", "success");
        }
    });

    // Helper: Định dạng ngày d/m/Y
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) + "/" + d.getFullYear();
    }
    
    // Toast notification helper
    function showToast(title, message, type = "info") {
        const toastContainer = document.querySelector(".toast-container");
        const id = "toast_" + Date.now();
        const icon = type === "success" ? "bi-check-circle-fill text-success" : 
                     type === "danger" ? "bi-x-circle-fill text-danger" : 
                     type === "warning" ? "bi-exclamation-triangle-fill text-warning" : "bi-info-circle-fill text-primary";
                     
        const toastHtml = `
            <div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius:12px; border:none; box-shadow:var(--shadow-md);">
                <div class="toast-header border-0 bg-white" style="border-radius:12px 12px 0 0;">
                    <i class="bi ${icon} me-2 fs-5"></i>
                    <strong class="me-auto text-dark fw-bold">${title}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body bg-white text-muted py-2" style="border-radius:0 0 12px 12px; font-size:0.9rem;">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML("beforeend", toastHtml);
        const toastElement = document.getElementById(id);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Xóa khỏi DOM sau khi ẩn
        toastElement.addEventListener("hidden.bs.toast", () => toastElement.remove());
    }
});
</script>
<?php 
$extraJs = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php'; 
?>
