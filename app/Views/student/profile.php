<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Hồ sơ cá nhân</h2>
        <div class="text-muted">Thông tin tài khoản và bảo mật của bạn</div>
    </div>
</div>

<div class="row g-4">
    <!-- Thẻ thông tin cá nhân -->
    <div class="col-lg-5">
        <div class="card-modern text-center py-4 px-3">
            <div class="mb-3">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=3b82f6&color=fff&bold=true&size=128"
                     alt="Avatar" class="rounded-circle shadow" style="width:100px;height:100px;">
            </div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h5>
            <div class="badge bg-primary rounded-pill mb-3">Sinh viên</div>
            <hr>
            <div class="text-start small">
                <div class="d-flex align-items-center gap-2 mb-2 py-2 px-3 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                    <i class="bi bi-person text-primary"></i>
                    <div>
                        <div class="text-muted" style="font-size:0.75rem;">Tên đầy đủ</div>
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2 py-2 px-3 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                    <i class="bi bi-envelope text-primary"></i>
                    <div>
                        <div class="text-muted" style="font-size:0.75rem;">Email</div>
                        <div class="fw-semibold" id="profileEmail">—</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2 py-2 px-3 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                    <i class="bi bi-card-text text-primary"></i>
                    <div>
                        <div class="text-muted" style="font-size:0.75rem;">Tên đăng nhập (Mã SV)</div>
                        <div class="fw-semibold" id="profileUsername">—</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 py-2 px-3 rounded" style="background:var(--bg-card-alt,#f8fafc);">
                    <i class="bi bi-shield-check text-success"></i>
                    <div>
                        <div class="text-muted" style="font-size:0.75rem;">Vai trò</div>
                        <div class="fw-semibold text-success">Sinh viên</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form đổi mật khẩu -->
    <div class="col-lg-7">
        <div class="card-modern">
            <h5 class="card-title-modern"><i class="bi bi-key me-2"></i>Đổi mật khẩu</h5>
            <p class="text-muted small mb-4">Để bảo mật tài khoản, hãy sử dụng mật khẩu mạnh và không chia sẻ với người khác.</p>

            <div id="pwChangeAlert" class="d-none mb-3"></div>

            <form id="changePasswordForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="currentPassword" placeholder="Nhập mật khẩu hiện tại" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('currentPassword', this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="newPassword" placeholder="Ít nhất 6 ký tự" required minlength="6" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('newPassword', this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="mt-2" id="pwStrengthBar" style="display:none;">
                        <div class="progress" style="height:4px;border-radius:4px;">
                            <div class="progress-bar" id="pwStrengthFill" style="width:0%;transition:width 0.3s;"></div>
                        </div>
                        <div class="small text-muted mt-1" id="pwStrengthLabel"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" placeholder="Nhập lại mật khẩu mới" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('confirmPassword', this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="small text-danger mt-1 d-none" id="pwMatchHint">Mật khẩu xác nhận không khớp!</div>
                </div>
                <button type="submit" class="btn btn-primary-modern px-5 py-2" id="btnChangePw">
                    <i class="bi bi-shield-lock me-2"></i>Cập nhật mật khẩu
                </button>
            </form>
        </div>

        <!-- Thống kê nhanh -->
        <div class="card-modern mt-4">
            <h5 class="card-title-modern"><i class="bi bi-bar-chart me-2"></i>Tóm tắt học tập</h5>
            <div class="row g-3 text-center" id="profileStats">
                <div class="col-3">
                    <div class="fw-bold fs-4 text-primary" id="statCourses">—</div>
                    <div class="small text-muted">Học phần</div>
                </div>
                <div class="col-3">
                    <div class="fw-bold fs-4 text-success" id="statPresentPct">—</div>
                    <div class="small text-muted">Tỷ lệ có mặt</div>
                </div>
                <div class="col-3">
                    <div class="fw-bold fs-4 text-warning" id="statAvgCPI">—</div>
                    <div class="small text-muted">CPI trung bình</div>
                </div>
                <div class="col-3">
                    <div class="fw-bold fs-4 text-info" id="statAlerts">—</div>
                    <div class="small text-muted">Cảnh báo</div>
                </div>
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

    // Load thông tin profile
    fetch(BASE_URL + "/api/student/dashboard-data")
        .then(r => r.json())
        .then(res => {
            if (res.status !== "success") return;
            const data = res.data;

            // Email & username từ session hoặc courses
            fetch(BASE_URL + "/api/students")
                .then(r => r.json())
                .catch(() => null);

            // Lấy từ PHP session info
            document.getElementById("profileEmail").innerText = "<?= htmlspecialchars($_SESSION['user']['email'] ?? '—') ?>";
            document.getElementById("profileUsername").innerText = "<?= htmlspecialchars($_SESSION['user']['username'] ?? '—') ?>";

            // Thống kê
            const courses = data.courses || [];
            const summary = data.attendanceSummary || {};
            const scores = data.scores || [];
            const alerts = data.alerts || [];

            document.getElementById("statCourses").innerText = courses.length;
            document.getElementById("statAlerts").innerText = alerts.length;

            const total = summary.total || 0;
            const present = (summary.present || 0) + (summary.late || 0) + (summary.excused || 0);
            const presentPct = total > 0 ? Math.round((present / total) * 100) + "%" : "—";
            document.getElementById("statPresentPct").innerText = presentPct;

            const avgCPI = scores.length > 0
                ? Math.round(scores.reduce((s, x) => s + parseInt(x.total_score), 0) / scores.length)
                : "—";
            document.getElementById("statAvgCPI").innerText = avgCPI !== "—" ? avgCPI + "/100" : "—";
        });

    // Strength meter cho mật khẩu mới
    document.getElementById("newPassword").addEventListener("input", function() {
        const val = this.value;
        const bar = document.getElementById("pwStrengthBar");
        const fill = document.getElementById("pwStrengthFill");
        const label = document.getElementById("pwStrengthLabel");
        if (!val) { bar.style.display = "none"; return; }
        bar.style.display = "block";
        let strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        const colors = ["#ef4444","#f59e0b","#f59e0b","#22c55e","#22c55e"];
        const labels = ["Rất yếu","Yếu","Trung bình","Mạnh","Rất mạnh"];
        fill.style.width = ((strength / 5) * 100) + "%";
        fill.style.backgroundColor = colors[strength - 1] || "#e5e7eb";
        label.innerText = labels[strength - 1] || "";
    });

    // Kiểm tra khớp mật khẩu
    document.getElementById("confirmPassword").addEventListener("input", function() {
        const hint = document.getElementById("pwMatchHint");
        if (this.value && this.value !== document.getElementById("newPassword").value) {
            hint.classList.remove("d-none");
        } else {
            hint.classList.add("d-none");
        }
    });

    // Submit đổi mật khẩu
    document.getElementById("changePasswordForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const currentPw = document.getElementById("currentPassword").value.trim();
        const newPw = document.getElementById("newPassword").value.trim();
        const confirmPw = document.getElementById("confirmPassword").value.trim();
        const alertBox = document.getElementById("pwChangeAlert");

        if (newPw !== confirmPw) {
            showAlert("danger", "Mật khẩu xác nhận không khớp!");
            return;
        }
        if (newPw.length < 6) {
            showAlert("danger", "Mật khẩu mới phải có ít nhất 6 ký tự.");
            return;
        }

        const btn = document.getElementById("btnChangePw");
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang cập nhật...';

        fetch(BASE_URL + "/api/student/change-password", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ current_password: currentPw, new_password: newPw })
        }).then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-lock me-2"></i>Cập nhật mật khẩu';
            if (res.status === "success") {
                showAlert("success", "✅ " + res.message);
                document.getElementById("changePasswordForm").reset();
                document.getElementById("pwStrengthBar").style.display = "none";
            } else {
                showAlert("danger", "❌ " + res.message);
            }
        }).catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-lock me-2"></i>Cập nhật mật khẩu';
            showAlert("danger", "Lỗi kết nối máy chủ.");
        });
    });

    function showAlert(type, msg) {
        const alertBox = document.getElementById("pwChangeAlert");
        alertBox.className = "alert alert-" + type + " py-2 small";
        alertBox.innerText = msg;
        alertBox.classList.remove("d-none");
        setTimeout(() => alertBox.classList.add("d-none"), 5000);
    }
});

function togglePw(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (input.type === "password") {
        input.type = "text";
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = "password";
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>
<?php
$extraJs = ob_get_clean();
$title = "Hồ sơ cá nhân";
require_once '../app/Views/layouts/student_layout.php';
?>
