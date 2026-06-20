<?php ob_start(); ?>
<div class="d-flex flex-column gap-4">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/my-courses" class="text-decoration-none text-muted">Lớp học của tôi</a></li>
            <li class="breadcrumb-item active">Sinh viên</li>
        </ol></nav>
        <h3 class="fw-bold mb-0">
            <?= htmlspecialchars($course['name']) ?>
            <span class="text-muted fs-5 fw-normal">(<?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['class_code'] ?? '') ?>)</span>
        </h3>
        <p class="text-muted mb-0">Tổng số sinh viên: <strong><?= count($students) ?></strong></p>
    </div>

    <div class="card-modern p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="search-bar" style="max-width: 300px;">
                <i class="bi bi-search"></i>
                <input type="text" id="studentSearch" class="form-control" placeholder="Tìm kiếm sinh viên...">
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>In danh sách
            </button>
        </div>

        <?php if (empty($students)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 d-block mb-3"></i>
            <div>Lớp chưa có sinh viên nào</div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="studentsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Sinh viên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Chuyên cần</th>
                        <th>CPI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php
                                $avatarSrc = $s['avatar_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($s['full_name']) . "&background=6366f1&color=fff&size=32";
                                ?>
                                <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                <div>
                                    <div class="fw-semibold small"><?= htmlspecialchars($s['full_name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="small"><?= htmlspecialchars($s['phone'] ?? '--') ?></td>
                        <td>
                            <span class="badge bg-light text-dark border small att-badge" data-student-id="<?= $s['id'] ?>">--</span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary small cpi-badge" data-student-id="<?= $s['id'] ?>">--</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Search filter
    document.getElementById('studentSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#studentsTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Load attendance & CPI stats
    loadStudentStats();
});

function loadStudentStats() {
    const courseId = <?= $course['id'] ?>;
    fetch(`${BASE_URL}/api/courses/${courseId}/engagement`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            const data = res.data || [];
            data.forEach(d => {
                const attBadge = document.querySelector(`.att-badge[data-student-id="${d.student_id}"]`);
                const cpiBadge = document.querySelector(`.cpi-badge[data-student-id="${d.student_id}"]`);
                if (attBadge) {
                    const rate = d.attendance_rate || 0;
                    const cls = rate >= 80 ? 'bg-success' : rate >= 65 ? 'bg-warning text-dark' : 'bg-danger';
                    attBadge.className = `badge ${cls} small att-badge`;
                    attBadge.textContent = rate + '%';
                }
                if (cpiBadge) {
                    cpiBadge.textContent = d.total_score ? Math.round(d.total_score) : '--';
                }
            });
        }).catch(() => {});
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php';
?>
