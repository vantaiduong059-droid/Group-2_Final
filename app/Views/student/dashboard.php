<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Chuyên cần & Tương tác cá nhân</h2>
        <div class="text-muted">Xin chào, sinh viên <span class="fw-medium text-dark"><?= $_SESSION['user']['full_name'] ?></span>!</div>
    </div>
</div>

<!-- CONTAINER HỘP THÔNG BÁO CẢNH BÁO TỪ ALERT ENGINE -->
<div id="studentAlertsContainer">
    <!-- JS render các Alert Banner tại đây -->
</div>

<!-- KHU VỰC PHIÊN HỌC ĐANG DIỄN RA (ĐANG MỞ ĐIỂM DANH HOẶC LÀM QUIZ) -->
<div class="row g-4 mb-4">
    <!-- Hộp Điểm danh trực tuyến -->
    <div class="col-lg-6 d-none" id="attendanceActiveBox">
        <div class="card-modern border-primary" style="border: 2px solid var(--primary); background: #f0f7ff;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-danger animate-pulse mb-2">ĐANG DIỄN RA</span>
                    <h5 class="fw-bold text-dark mb-1" id="activeSessionName">Môn học: Phát triển ứng dụng Web</h5>
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
                    <!-- Tab Code -->
                    <div class="tab-pane fade show active" id="pill-code" role="tabpanel">
                        <form id="submitCodeForm" class="d-flex gap-2">
                            <input type="text" class="form-control text-center fw-bold fs-5 letter-spacing-2" id="attendanceCodeInput" placeholder="MÃ CODE 6 SỐ" maxlength="6" required>
                            <button type="submit" class="btn btn-primary-modern px-4">Gửi</button>
                        </form>
                    </div>
                    <!-- Tab QR -->
                    <div class="tab-pane fade text-center py-2" id="pill-qr" role="tabpanel">
                        <p class="text-muted small">Nhấp nút bên dưới để khởi động camera giả lập quét mã QR của giảng viên.</p>
                        <button class="btn btn-outline-primary rounded-pill px-4" id="btnStartFakeQRScan"><i class="bi bi-camera me-2"></i>Quét QR</button>
                    </div>
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
                    <h5 class="fw-bold text-dark mb-1" id="activeQuizTitle">Mini Quiz 5 phút ôn tập</h5>
                    <div class="text-muted small" id="activeQuizSessionName">Buổi học ngày 12/06/2026</div>
                </div>
                <i class="bi bi-lightning-fill text-success fs-3"></i>
            </div>
            <p class="text-muted small">Giảng viên vừa kích hoạt một bài trắc nghiệm ngắn. Hãy tham gia để cộng điểm tương tác CPI của bạn.</p>
            <button class="btn btn-success w-100 py-2.5 rounded-pill fw-semibold" id="btnStartQuiz"><i class="bi bi-pencil-square me-2"></i>Bắt đầu làm Quiz</button>
        </div>
    </div>
</div>

<!-- TIẾN ĐỘ HỌC TẬP TỔNG QUAN -->
<div class="row g-4 mb-4">
    <!-- Biểu đồ / Thống kê CPI -->
    <div class="col-lg-5">
        <div class="card-modern">
            <h5 class="card-title-modern">Chỉ số tham gia lớp học (CPI Index)</h5>
            <div class="d-flex flex-column align-items-center justify-content-center py-3">
                <!-- Vòng tròn CPI Gauge -->
                <div class="position-relative d-flex align-items-center justify-content-center" style="width: 160px; height: 160px;">
                    <svg class="w-100 h-100" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                        <path class="text-light" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-primary" stroke-width="3" stroke-dasharray="100, 100" stroke-linecap="round" stroke="currentColor" fill="none" id="cpiGaugePath" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="position-absolute text-center">
                        <div class="display-6 fw-bold text-dark" id="cpiIndexVal">100</div>
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

    <!-- Danh sách các lớp học phần tham gia và điểm số -->
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
                            <th class="text-center">Số buổi vắng</th>
                            <th class="text-end">CPI học phần</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS render -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- LỊCH SỬ CHI TIẾT -->
<div class="row g-4">
    <!-- Lịch sử điểm danh chi tiết -->
    <div class="col-lg-6">
        <div class="card-modern">
            <h5 class="card-title-modern">Lịch sử điểm danh chi tiết</h5>
            <div class="table-responsive" style="max-height: 350px;">
                <table class="table-modern" id="studentAttendanceTable">
                    <thead>
                        <tr>
                            <th>Ngày học</th>
                            <th>Môn học</th>
                            <th>Hình thức</th>
                            <th>Thời gian</th>
                            <th class="text-end">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS render -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lịch sử Quiz làm bài -->
    <div class="col-lg-6">
        <div class="card-modern">
            <h5 class="card-title-modern">Lịch sử Quiz trắc nghiệm đã làm</h5>
            <div class="table-responsive" style="max-height: 350px;">
                <table class="table-modern" id="studentQuizHistoryTable">
                    <thead>
                        <tr>
                            <th>Tên Quiz</th>
                            <th>Lớp học phần</th>
                            <th>Thời gian nộp</th>
                            <th class="text-end">Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS render -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LÀM QUIZ MÔ PHỎNG -->
<div class="modal fade" id="quizPlayModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="quizPlayModalTitle">Làm bài Quiz nhanh</h5>
                <span class="badge bg-danger ms-auto fs-6 px-3" id="quizPlayTimer">05:00</span>
            </div>
            <div class="modal-body py-4">
                <div class="alert alert-info py-2 small mb-4"><i class="bi bi-info-circle me-2"></i>Chọn câu trả lời chính xác nhất. Mỗi câu trả lời đúng cộng điểm trực tiếp vào CPI.</div>
                
                <!-- Bộ câu hỏi giả lập đẹp mắt -->
                <div id="quizQuestionBox">
                    <div class="fw-bold mb-3" style="font-size:1.05rem;">Câu 1: Repository Pattern trong PHP MVC giải quyết nhiệm vụ chính nào?</div>
                    <div class="d-flex flex-column gap-2.5">
                        <button class="btn btn-outline-secondary text-start py-2.5 px-3 rounded quiz-option-btn" data-correct="false" data-score="3.33">
                            <span class="fw-bold me-2">A.</span>Xây dựng các thành phần của giao diện HTML.
                        </button>
                        <button class="btn btn-outline-secondary text-start py-2.5 px-3 rounded quiz-option-btn" data-correct="true" data-score="3.33">
                            <span class="fw-bold me-2">B.</span>Tách biệt logic truy vấn CSDL ra khỏi Controller/Model.
                        </button>
                        <button class="btn btn-outline-secondary text-start py-2.5 px-3 rounded quiz-option-btn" data-correct="false" data-score="3.33">
                            <span class="fw-bold me-2">C.</span>Quản lý kết nối Database theo mô hình Singleton.
                        </button>
                        <button class="btn btn-outline-secondary text-start py-2.5 px-3 rounded quiz-option-btn" data-correct="false" data-score="3.33">
                            <span class="fw-bold me-2">D.</span>Điều hướng URL và bắt các request HTTP của client.
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnSubmitFakeQuiz">Nộp bài làm</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QUÉT QR GIẢ LẬP CAMERA -->
<div class="modal fade" id="qrScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera me-2 text-primary"></i>Quét mã QR Điểm danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="qr-scan-box position-relative mx-auto rounded mb-4 overflow-hidden border border-3 border-primary" style="width:280px; height:200px; background:#000;">
                    <!-- Laser quét mô phỏng -->
                    <div class="qr-laser position-absolute w-100" style="height:3px; background:rgba(59, 130, 246, 0.8); box-shadow:0 0 8px #3b82f6; animation: scanLaser 2s infinite linear;"></div>
                    <div class="d-flex h-100 flex-column align-items-center justify-content-center text-white p-3">
                        <i class="bi bi-upc-scan fs-1 mb-2 text-primary animate-pulse"></i>
                        <div class="small">Đang tìm mã QR điểm danh...</div>
                    </div>
                </div>
                
                <div class="px-4">
                    <label class="form-label small fw-semibold text-muted">Hoặc nhập Token QR nhận được (giả lập camera tự động điền)</label>
                    <input type="text" class="form-control text-center" id="qrTokenFakeInput" placeholder="Nhập token QR..." required>
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
    0% { top: 0px; }
    50% { top: 197px; }
    100% { top: 0px; }
}
.letter-spacing-2 {
    letter-spacing: 4px;
}
.quiz-option-btn.selected {
    background-color: var(--primary-light) !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    font-weight: 600;
}
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
    
    // Tự động đọc QR Token từ URL (nếu quét thật từ điện thoại)
    const urlParams = new URLSearchParams(window.location.search);
    const qrTokenFromUrl = urlParams.get("qr_token");
    const sessionIdFromUrl = urlParams.get("session_id");

    // Load tất cả dữ liệu qua REST API
    loadDashboardData();

    function loadDashboardData() {
        fetch(BASE_URL + "/api/student/dashboard-data")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const data = res.data;
                    
                    // 1. Render cảnh báo (Alert Engine Banners)
                    renderAlertBanners(data.alerts);
                    
                    // 2. Render khóa học & Điểm tích lũy (CPI)
                    renderCoursesTable(data.courses, data.scores);
                    
                    // 3. Render lịch sử điểm danh
                    renderAttendanceTable(data.history);
                    
                    // 4. Render các hoạt động điểm danh đang mở (Active Sessions)
                    handleActiveSessions(data.activeSessions);
                    
                    // Tải lịch sử Quiz làm bài
                    loadStudentQuizHistory();
                    
                    // Nếu có token QR trên URL thì tự động điền và kích hoạt quét QR điểm danh
                    if (qrTokenFromUrl && sessionIdFromUrl) {
                        showQRScanModal(sessionIdFromUrl, qrTokenFromUrl);
                    }
                }
            });
    }

    function renderAlertBanners(alerts) {
        const container = document.getElementById("studentAlertsContainer");
        container.innerHTML = "";
        
        if (alerts.length === 0) return;
        
        alerts.forEach(a => {
            const banner = document.createElement("div");
            banner.className = "alert alert-danger border-0 d-flex justify-content-between align-items-center mb-4 p-3 shadow-sm";
            banner.style.borderRadius = "var(--radius-lg)";
            banner.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0.5">Cảnh báo học tập (Môn: ${a.course_name})</div>
                        <div class="small text-danger" style="font-weight: 500;">${a.message}</div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3" onclick="dismissAlert(${a.id})">Đã hiểu</button>
            `;
            container.appendChild(banner);
        });
    }

    // Dismiss alert
    window.dismissAlert = function(alertId) {
        fetch(BASE_URL + "/api/alerts/" + alertId, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ is_read: 1 })
        })
        .then(res => res.json())
        .then(res => {
            loadDashboardData();
        });
    }

    function renderCoursesTable(courses, scores) {
        const tbody = document.querySelector("#studentCoursesTable tbody");
        tbody.innerHTML = "";
        
        if (courses.length === 0) {
            tbody.innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-4\">Bạn chưa được xếp vào học phần nào</td></tr>";
            return;
        }

        // Tính toán tổng điểm CPI trung bình của sinh viên trên tất cả học phần
        let totalCPI = 0;
        let countCPI = 0;
        let sumAttPoints = 0;
        let sumIntPoints = 0;

        courses.forEach(c => {
            // Lấy score cho course này
            const scoreRow = scores.find(s => s.course_id == c.id) || { attendance_points: 0, interaction_points: 0, total_score: 100 };
            
            totalCPI += scoreRow.total_score;
            countCPI++;
            sumAttPoints += scoreRow.attendance_points;
            sumIntPoints += scoreRow.interaction_points;

            // Đếm số buổi vắng (absent) từ lịch sử (ở backend đã có hàm hỗ trợ hoặc ta đếm)
            // Lấy số buổi vắng môn này qua API hoặc query database, trong REST data.history ta đếm được
            // Ở đây để đơn giản, ta sẽ dùng API fetch đếm vắng (hoặc ta đếm client-side từ dữ liệu history)
            let absentCount = 0; // Tải động ở dưới hoặc lấy giá trị tĩnh để hiển thị
            
            const row = document.createElement("tr");
            row.innerHTML = `
                <td class="fw-bold text-primary">${c.code}</td>
                <td class="fw-medium">${c.name}</td>
                <td class="text-muted">${c.teacher_name || "Chưa phân công"}</td>
                <td class="text-center fw-semibold text-danger" id="course_absent_${c.id}">0</td>
                <td class="text-end fw-bold text-dark fs-6">${scoreRow.total_score}/100</td>
            `;
            tbody.appendChild(row);
        });

        // Thiết lập CPI Gauge tròn
        const finalCPI = countCPI > 0 ? Math.round(totalCPI / countCPI) : 100;
        document.getElementById("cpiIndexVal").innerText = finalCPI;
        document.getElementById("cpiAttendancePoints").innerText = sumAttPoints + "đ";
        document.getElementById("cpiInteractionPoints").innerText = sumIntPoints + "đ";
        
        // Cập nhật stroke của SVG gauge
        const dashArray = (finalCPI / 100) * 100;
        document.getElementById("cpiGaugePath").setAttribute("stroke-dasharray", dashArray + ", 100");
    }

    function renderAttendanceTable(history) {
        const tbody = document.querySelector("#studentAttendanceTable tbody");
        tbody.innerHTML = "";
        
        // Reset bảng vắng học phần trên client side
        document.querySelectorAll("[id^=course_absent_]").forEach(td => td.innerText = 0);
        
        if (history.length === 0) {
            tbody.innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-4\">Chưa có lịch sử điểm danh</td></tr>";
            return;
        }

        // Đếm vắng học phần
        const absentMap = {};

        history.forEach(h => {
            const row = document.createElement("tr");
            
            let statusBadge = "";
            if (h.status === "present") statusBadge = "<span class=\"badge bg-success\">Có mặt</span>";
            else if (h.status === "late") statusBadge = "<span class=\"badge bg-warning\">Đi muộn</span>";
            else if (h.status === "absent") {
                statusBadge = "<span class=\"badge bg-danger\">Vắng mặt</span>";
                // Tăng biến đếm vắng
                const courseId = h.course_id; // Giả sử h có course_id? h.course_code ta có thể ánh xạ.
                // Ở đây h có course_name và course_code, ta đếm theo course_code.
                // Để đếm vắng theo từng môn, ta dùng logic so khớp code
                const td = document.querySelector(`td[id^="course_absent_"]`); // Hoặc làm ở backend, ở đây ta đếm
                // Ta tìm td tương ứng môn học thông qua query Selector bằng cách thêm class hoặc ID môn học.
            } else if (h.status === "excused") statusBadge = "<span class=\"badge bg-secondary\">Có phép</span>";
            else statusBadge = "<span class=\"badge bg-light text-dark\">Không điểm danh</span>";

            // Cập nhật vắng học phần trên UI
            if (h.status === "absent") {
                // Ta tìm course có code tương ứng
                // Do courses table chứa list môn học, ta có thể dùng code so khớp
                // Tìm dòng trong courses table
                const rows = document.querySelectorAll("#studentCoursesTable tbody tr");
                rows.forEach(tr => {
                    if (tr.querySelector("td").innerText === h.course_code) {
                        const countTd = tr.querySelectorAll("td")[3];
                        countTd.innerText = parseInt(countTd.innerText) + 1;
                    }
                });
            }

            const methodText = h.method_name ? `<span class="badge bg-light text-muted">${h.method_name}</span>` : "-";
            const dateStr = formatDate(h.session_date);
            const timeText = h.recorded_at ? new Date(h.recorded_at).toLocaleTimeString("vi-VN", {hour:"2-digit", minute:"2-digit"}) : "-";

            row.innerHTML = `
                <td class="fw-medium">${dateStr}</td>
                <td class="text-truncate text-muted" style="max-width:140px;">${h.course_name}</td>
                <td>${methodText}</td>
                <td class="text-muted">${timeText}</td>
                <td class="text-end">${statusBadge}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Xử lý hiển thị phiên điểm danh & quiz
    let attInterval = null;
    function handleActiveSessions(activeSessions) {
        if (attInterval) clearInterval(attInterval);
        
        if (activeSessions.length === 0) {
            document.getElementById("attendanceActiveBox").classList.add("d-none");
            return;
        }

        // Lấy session active đầu tiên
        const session = activeSessions[0];
        activeSession = session;
        
        document.getElementById("attendanceActiveBox").classList.remove("d-none");
        document.getElementById("activeSessionName").innerText = session.course_code + " - " + session.course_name;
        
        const expireTime = new Date(session.attendance_expires_at).getTime();
        
        function updateAttTimer() {
            const now = new Date().getTime();
            const distance = expireTime - now;
            
            if (distance < 0) {
                clearInterval(attInterval);
                document.getElementById("attCountdownTimer").innerText = "HẾT HẠN";
                document.getElementById("attendanceActiveBox").classList.add("d-none");
                loadDashboardData();
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("attCountdownTimer").innerText = (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
        }
        
        updateAttTimer();
        attInterval = setInterval(updateAttTimer, 1000);
        
        // Đồng thời tải Quiz đang mở (nếu có)
        loadActiveQuizzes(session.id);
    }

    // Tải Quiz đang mở của buổi học hiện tại
    function loadActiveQuizzes(sessionId) {
        fetch(BASE_URL + "/api/quizzes?session_id=" + sessionId)
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    // Tìm quiz chưa làm và còn hạn
                    const openQuizzes = res.data.filter(q => {
                        const now = new Date().getTime();
                        const isExpired = new Date(q.end_time).getTime() < now;
                        return !isExpired && !q.is_submitted;
                    });
                    
                    if (openQuizzes.length === 0) {
                        document.getElementById("quizzesActiveBox").classList.add("d-none");
                        return;
                    }
                    
                    const quiz = openQuizzes[0];
                    activeQuiz = quiz;
                    
                    document.getElementById("quizzesActiveBox").classList.remove("d-none");
                    document.getElementById("activeQuizTitle").innerText = quiz.title;
                    document.getElementById("activeQuizSessionName").innerText = "Tổng điểm: " + quiz.total_marks + "đ | Tổng hợp CPI";
                }
            });
    }

    // 1. Submit Code điểm danh
    document.getElementById("submitCodeForm").addEventListener("submit", function(e) {
        e.preventDefault();
        if(!activeSession) return;
        
        const code = document.getElementById("attendanceCodeInput").value;
        
        fetch(BASE_URL + "/api/attendance/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: activeSession.id, code: code })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Thành công", res.message, "success");
                document.getElementById("attendanceCodeInput").value = "";
                document.getElementById("attendanceActiveBox").classList.add("d-none");
                loadDashboardData();
            } else {
                showToast("Lỗi điểm danh", res.message, "danger");
            }
        });
    });

    // 2. Điểm danh QR
    
    // Mở modal quét camera giả lập
    document.getElementById("btnStartFakeQRScan").addEventListener("click", function() {
        if (!activeSession) return;
        
        qrScanModalInstance = new bootstrap.Modal(document.getElementById("qrScanModal"));
        qrScanModalInstance.show();
        
        // Giả lập camera quét QR, tự động điền QR token sau 2 giây
        setTimeout(() => {
            // Lấy token thật từ DB của buổi học đang active (chỉ cho mục đích giả lập)
            fetch(BASE_URL + "/api/sessions")
                .then(res => res.json())
                .then(res => {
                    const session = res.data.find(s => s.id == activeSession.id);
                    if(session && session.qr_token) {
                        document.getElementById("qrTokenFakeInput").value = session.qr_token;
                        showToast("Giả lập Camera", "Đã nhận dạng thành công mã QR điểm danh!", "success");
                    }
                });
        }, 2500);
    });
    
    // Mở modal QR từ link (nếu sinh viên quét thật)
    function showQRScanModal(sessionId, token) {
        qrScanModalInstance = new bootstrap.Modal(document.getElementById("qrScanModal"));
        qrScanModalInstance.show();
        document.getElementById("qrTokenFakeInput").value = token;
    }

    // Xác nhận điểm danh QR
    document.getElementById("btnConfirmQRScan").addEventListener("click", function() {
        if(!activeSession) return;
        const qrToken = document.getElementById("qrTokenFakeInput").value;
        
        fetch(BASE_URL + "/api/attendance/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ session_id: activeSession.id, qr_token: qrToken })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Thành công", res.message, "success");
                if(qrScanModalInstance) qrScanModalInstance.hide();
                document.getElementById("attendanceActiveBox").classList.add("d-none");
                loadDashboardData();
            } else {
                showToast("Lỗi điểm danh", res.message, "danger");
            }
        });
    });

    // 3. PHẦN QUIZ
    
    // Bắt đầu làm Quiz
    document.getElementById("btnStartQuiz").addEventListener("click", function() {
        if(!activeQuiz) return;
        
        quizPlayModalInstance = new bootstrap.Modal(document.getElementById("quizPlayModal"));
        document.getElementById("quizPlayModalTitle").innerText = activeQuiz.title;
        
        quizPlayModalInstance.show();
        
        // Đồng hồ đếm ngược Quiz
        const endTime = new Date(activeQuiz.end_time).getTime();
        let qTimer = setInterval(() => {
            const now = new Date().getTime();
            const dist = endTime - now;
            if (dist < 0) {
                clearInterval(qTimer);
                document.getElementById("quizPlayTimer").innerText = "HẾT GIỜ";
                submitQuizAnswers(activeQuiz.id, 0); // nộp 0 điểm
                return;
            }
            const min = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
            const sec = Math.floor((dist % (1000 * 60)) / 1000);
            document.getElementById("quizPlayTimer").innerText = (min < 10 ? "0" + min : min) + ":" + (sec < 10 ? "0" + sec : sec);
        }, 1000);
    });

    // Logic chọn câu trả lời
    const optionBtns = document.querySelectorAll(".quiz-option-btn");
    optionBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            optionBtns.forEach(b => b.classList.remove("selected"));
            btn.classList.add("selected");
        });
    });

    // Nộp Quiz bài làm
    document.getElementById("btnSubmitFakeQuiz").addEventListener("click", function() {
        if (!activeQuiz) return;
        
        const selectedOpt = document.querySelector(".quiz-option-btn.selected");
        if (!selectedOpt) {
            showToast("Quiz", "Vui lòng chọn một đáp án trước khi nộp.", "warning");
            return;
        }

        const isCorrect = selectedOpt.getAttribute("data-correct") === "true";
        // Trong demo này, nộp trắc nghiệm 1 câu: nếu đúng được 10 điểm, sai được 0 điểm
        const score = isCorrect ? activeQuiz.total_marks : 0;
        
        submitQuizAnswers(activeQuiz.id, score);
    });

    function submitQuizAnswers(quizId, score) {
        fetch(BASE_URL + "/api/quizzes/" + quizId + "/submit", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ score: score })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === "success") {
                showToast("Quiz", "Đã nộp bài Quiz thành công! Điểm của bạn: " + score + "đ", "success");
                if (quizPlayModalInstance) quizPlayModalInstance.hide();
                document.getElementById("quizzesActiveBox").classList.add("d-none");
                loadDashboardData();
            } else {
                showToast("Lỗi nộp bài", res.message, "danger");
            }
        });
    }

    // Tải lịch sử các bài Quiz đã làm
    function loadStudentQuizHistory() {
        fetch(BASE_URL + "/api/student/dashboard-data")
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    const courses = res.data.courses;
                    const tbody = document.querySelector("#studentQuizHistoryTable tbody");
                    tbody.innerHTML = "";
                    
                    let allPromises = courses.map(c => 
                        fetch(BASE_URL + "/api/quizzes?session_id=").then(r => r.json()) // dummy fetch sửa sau
                    );
                    
                    // Thực tế để lấy lịch sử nộp quiz của SV, ta truy vấn CSDL
                    // Bảng quiz_submissions JOIN quiz_sessions JOIN class_sessions JOIN courses
                    // Hãy tạo một API route hoặc truy vấn trực tiếp trong CSDL từ `dashboardData`!
                    // Thực ra trong databaseData() chúng ta chưa trả về quiz history.
                    // Hãy viết thêm một hàm trả về Quiz submissions của học sinh.
                    // Đợi đã, hãy fetch danh sách quiz học sinh làm trực tiếp từ database.
                    // Ta đã viết hàm getStudentSubmissionsInCourse() trong QuizRepository.
                    // Ta có thể gọi qua API hoặc chèn nó vào trong dashboardData().
                    // Hãy xem file StudentApiController.php:
                    // dashboardData chưa nạp quiz history.
                    // Ta có thể sửa StudentApiController.php để chèn quiz history vào dashboardData!
                    // Đúng vậy. Xem lại dashboardData() trong StudentApiController.php:
                    // Nó trả về: courses, scores, history, activeSessions, alerts.
                    // Chúng ta có thể thêm `quizzes` vào dữ liệu trả về, để trong đó có kết quả làm bài của SV.
                    // Nhưng khoan, hãy xem hàm loadStudentQuizHistory() ở frontend. Nó có thể gọi một API mới hoặc ta cập nhật `dashboardData()`.
                    // Ta có thể nhanh chóng sửa `StudentApiController.php` để thêm 'quizHistory' vào JSON.
                    // Đó là giải pháp thông minh và tối ưu nhất!
                }
            });
    }

    // Cập nhật: Tải lịch sử làm Quiz thông qua API mới hoặc chèn vào dashboardData
    // Hãy kiểm tra xem nếu ta đã thêm quizHistory vào dashboardData chưa.
    // Lát nữa chúng ta sẽ cập nhật StudentApiController.php để trả về 'quizHistory'.
    // Ở đây ta cứ viết logic render frontend trước:
    window.renderQuizHistory = function(quizHistory) {
        const tbody = document.querySelector("#studentQuizHistoryTable tbody");
        tbody.innerHTML = "";
        
        if (!quizHistory || quizHistory.length === 0) {
            tbody.innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-4\">Bạn chưa tham gia bài Quiz nào</td></tr>";
            return;
        }
        
        quizHistory.forEach(qh => {
            const row = document.createElement("tr");
            const submitTime = new Date(qh.submitted_at).toLocaleDateString("vi-VN") + " " + new Date(qh.submitted_at).toLocaleTimeString("vi-VN", {hour:"2-digit", minute:"2-digit"});
            row.innerHTML = `
                <td class="fw-medium">${qh.quiz_title}</td>
                <td class="text-muted">${qh.course_name || "Môn học"}</td>
                <td class="text-muted">${submitTime}</td>
                <td class="text-end fw-bold text-success">${qh.score} / ${qh.total_marks}đ</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Tải và chạy render Quiz History
    // (Chúng ta sẽ sửa StudentApiController ở bước tiếp theo để nạp trường này vào dashboardData)
    // Để frontend có thể hiển thị luôn, ta sẽ thiết lập fetch từ api dashboardData
    fetch(BASE_URL + "/api/student/dashboard-data")
        .then(res => res.json())
        .then(res => {
            if(res.status === "success" && res.data.quizHistory) {
                renderQuizHistory(res.data.quizHistory);
            } else {
                // Giả lập nếu chưa cập nhật API
                document.querySelector("#studentQuizHistoryTable tbody").innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-4\">Chưa có dữ liệu bài Quiz nào</td></tr>";
            }
        });

    // Helpers
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) + "/" + d.getFullYear();
    }

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
        toastElement.addEventListener("hidden.bs.toast", () => toastElement.remove());
    }
});
</script>
<?php 
$extraJs = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php'; 
?>
