<?php ob_start(); ?>
<style>
.avatar-upload-wrap { position: relative; width: 100px; height: 100px; }
.avatar-upload-wrap img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color); }
.avatar-upload-btn { position: absolute; bottom: 4px; right: 4px; width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: white; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.75rem; transition: all 0.2s; }
.avatar-upload-btn:hover { background: #1d4ed8; }
.profile-card { border-radius: 16px; border: 1px solid var(--border-color); padding: 28px; background: #fff; }
</style>

<div class="d-flex flex-column gap-4" style="max-width: 720px; margin: 0 auto;">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted" onclick="history.back()">← Quay lại</a></li>
            <li class="breadcrumb-item active">Thông tin cá nhân</li>
        </ol></nav>
        <h3 class="fw-bold mb-0">Thông tin cá nhân</h3>
    </div>

    <!-- Profile Info -->
    <div class="profile-card">
        <div class="d-flex align-items-center gap-4 mb-4 pb-4 border-bottom flex-wrap">
            <div class="avatar-upload-wrap">
                <img src="" alt="Avatar" id="profileAvatar">
                <label class="avatar-upload-btn" for="avatarInput" title="Đổi ảnh">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" id="avatarInput" accept="image/*" style="display:none;" onchange="uploadAvatar(this)">
            </div>
            <div>
                <h4 class="fw-bold mb-1" id="profileNameDisplay">--</h4>
                <div class="text-muted small" id="profileRoleDisplay">--</div>
                <div class="text-muted small" id="profileEmailDisplay">--</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="fw-semibold small mb-1">Họ và tên đệm <span class="text-danger">*</span></label>
                <input type="text" id="profileLastName" class="form-control" placeholder="Nhập họ và tên đệm">
            </div>
            <div class="col-12 col-md-2">
                <label class="fw-semibold small mb-1">Tên <span class="text-danger">*</span></label>
                <input type="text" id="profileFirstName" class="form-control" placeholder="Nhập tên">
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Email</label>
                <input type="email" id="profileEmail" class="form-control" readonly style="background: var(--bg-muted, #f1f5f9); cursor: not-allowed;">
                <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Email không thể thay đổi</div>
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Số điện thoại</label>
                <input type="tel" id="profilePhone" class="form-control" placeholder="Ví dụ: 0912345678">
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Username</label>
                <input type="text" id="profileUsername" class="form-control" readonly style="background: var(--bg-muted, #f1f5f9);">
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary-modern fw-semibold" onclick="saveProfile()">
                <i class="bi bi-save2 me-2"></i>Lưu thay đổi
            </button>
            <button class="btn btn-outline-secondary" onclick="loadProfile()">
                <i class="bi bi-arrow-clockwise me-2"></i>Hủy
            </button>
        </div>
        <div id="profileResult" class="mt-3"></div>
    </div>

    <!-- Change Password -->
    <div class="profile-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Đổi mật khẩu</h6>
        <div class="row g-3">
            <div class="col-12">
                <label class="fw-semibold small mb-1">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" id="oldPassword" class="form-control" placeholder="Nhập mật khẩu hiện tại">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePw('oldPassword')"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" id="newPassword" class="form-control" placeholder="Tối thiểu 8 ký tự" oninput="checkPwStrength(this.value)">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePw('newPassword')"><i class="bi bi-eye"></i></button>
                </div>
                <div id="pwStrength" class="mt-1" style="font-size: 0.75rem;"></div>
            </div>
            <div class="col-12 col-md-6">
                <label class="fw-semibold small mb-1">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Nhập lại mật khẩu mới">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePw('confirmPassword')"><i class="bi bi-eye"></i></button>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-warning fw-semibold" onclick="changePassword()">
                <i class="bi bi-key me-2"></i>Đổi mật khẩu
            </button>
        </div>
        <div id="pwResult" class="mt-3"></div>
    </div>

    <!-- Account Info -->
    <div class="profile-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-muted"></i>Thông tin tài khoản</h6>
        <div class="row g-3 text-muted small">
            <div class="col-6"><div class="fw-semibold text-dark mb-1">Vai trò</div><span id="infoRole">--</span></div>
            <div class="col-6"><div class="fw-semibold text-dark mb-1">Ngày tạo tài khoản</div><span id="infoCreated">--</span></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadProfile);

function loadProfile() {
    fetch(`${BASE_URL}/api/profile`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            const roleMap = { admin: 'Quản trị viên', teacher: 'Giảng viên', student: 'Sinh viên' };
            
            document.getElementById('profileLastName').value = d.last_name || '';
            document.getElementById('profileFirstName').value = d.first_name || '';
            document.getElementById('profileEmail').value = d.email || '';
            document.getElementById('profilePhone').value = d.phone || '';
            document.getElementById('profileUsername').value = d.username || '';
            document.getElementById('profileNameDisplay').textContent = d.full_name || '--';
            document.getElementById('profileEmailDisplay').textContent = d.email || '--';
            document.getElementById('profileRoleDisplay').textContent = roleMap[d.role] || d.role;
            document.getElementById('infoRole').textContent = roleMap[d.role] || d.role;
            document.getElementById('infoCreated').textContent = d.created_at ? d.created_at.slice(0,10) : '--';
            
            const avatarSrc = d.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(d.full_name)}&background=${d.role === 'teacher' ? '10b981' : '6366f1'}&color=fff&size=100`;
            document.getElementById('profileAvatar').src = avatarSrc;
        });
}

function saveProfile() {
    const lastName = document.getElementById('profileLastName').value.trim();
    const firstName = document.getElementById('profileFirstName').value.trim();
    const phone = document.getElementById('profilePhone').value.trim();
    
    if (!lastName || !firstName) { showToast('Họ tên không được để trống.', 'warning'); return; }
    
    const nameRegex = /^[\p{L}\s]+$/u;
    if(!nameRegex.test(lastName) || !nameRegex.test(firstName)) {
        showToast('Họ tên chỉ được chứa chữ cái và khoảng trắng', 'warning');
        return;
    }
    
    if (phone && !/^[0-9+\-\s]{9,15}$/.test(phone)) { showToast('Số điện thoại không hợp lệ.', 'warning'); return; }

    fetch(`${BASE_URL}/api/profile`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ last_name: lastName, first_name: firstName, phone })
    }).then(r => r.json()).then(res => {
        document.getElementById('profileResult').innerHTML = `<div class="alert ${res.status === 'success' ? 'alert-success' : 'alert-danger'} py-2">${res.message}</div>`;
        if (res.status === 'success') {
            document.getElementById('profileNameDisplay').textContent = lastName + ' ' + firstName;
            showToast(res.message, 'success');
        }
    });
}

function changePassword() {
    const oldPw = document.getElementById('oldPassword').value;
    const newPw = document.getElementById('newPassword').value;
    const confPw = document.getElementById('confirmPassword').value;

    if (!oldPw || !newPw || !confPw) { showToast('Vui lòng điền đầy đủ thông tin.', 'warning'); return; }
    if (newPw.length < 8) { showToast('Mật khẩu mới phải có ít nhất 8 ký tự.', 'warning'); return; }
    if (newPw !== confPw) { showToast('Xác nhận mật khẩu không khớp.', 'warning'); return; }

    fetch(`${BASE_URL}/api/profile/password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ old_password: oldPw, new_password: newPw, confirm_password: confPw })
    }).then(r => r.json()).then(res => {
        document.getElementById('pwResult').innerHTML = `<div class="alert ${res.status === 'success' ? 'alert-success' : 'alert-danger'} py-2">${res.message}</div>`;
        if (res.status === 'success') { 
            document.getElementById('oldPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            showToast(res.message, 'success');
        }
    });
}

function uploadAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('avatar', file);
    
    showToast('Đang tải ảnh...', 'info');
    fetch(`${BASE_URL}/api/profile/avatar`, { method: 'POST', body: formData })
        .then(r => r.json()).then(res => {
            if (res.status === 'success') {
                document.getElementById('profileAvatar').src = res.avatar_url;
                showToast('Cập nhật ảnh đại diện thành công!', 'success');
            } else {
                showToast(res.message, 'danger');
            }
        });
}

function togglePw(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function checkPwStrength(pw) {
    const el = document.getElementById('pwStrength');
    if (!pw) { el.textContent = ''; return; }
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const levels = [['text-danger','Rất yếu'],['text-danger','Yếu'],['text-warning','Trung bình'],['text-success','Mạnh'],['text-success fw-bold','Rất mạnh']];
    el.className = levels[score][0];
    el.textContent = 'Độ mạnh: ' + levels[score][1];
}
</script>

<?php
$content = ob_get_clean();
$layout = $_SESSION['user']['role'] === 'teacher' ? 'teacher_layout' : 'student_layout';
require_once "../app/Views/layouts/{$layout}.php";
?>
