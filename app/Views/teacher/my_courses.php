<?php ob_start(); ?>
<div class="d-flex flex-column gap-4">
    <div>
        <h3 class="fw-bold mb-1">Lớp học của tôi</h3>
        <p class="text-muted mb-0">Danh sách các lớp học phần bạn đang phụ trách</p>
    </div>

    <div class="row g-4" id="myCoursesGrid">
        <?php if (empty($myCourses)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
            <div>Bạn chưa được phân công lớp học nào.</div>
        </div>
        <?php else: ?>
        <?php foreach ($myCourses as $c): ?>
        <?php
        $db = Database::getInstance()->getConnection();
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM course_students WHERE course_id = ?");
        $stmtCount->execute([$c['id']]);
        $studentCount = $stmtCount->fetchColumn();
        $stmtSess = $db->prepare("SELECT COUNT(*) FROM class_sessions WHERE course_id = ? AND status='completed'");
        $stmtSess->execute([$c['id']]);
        $sessCompleted = $stmtSess->fetchColumn();
        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM class_sessions WHERE course_id = ?");
        $stmtTotal->execute([$c['id']]);
        $sessTotal = $stmtTotal->fetchColumn();
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-modern p-4 h-100" style="cursor: pointer; transition: all 0.2s;" onclick="window.location.href='<?= BASE_URL ?>/teacher/course-students/<?= $c['id'] ?>'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="fw-bold fs-6 text-primary"><?= htmlspecialchars($c['code']) ?></div>
                    <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($c['class_code'] ?? '') ?></span>
                </div>
                <h5 class="fw-bold mb-2" style="line-height: 1.4;"><?= htmlspecialchars($c['name']) ?></h5>
                <div class="text-muted small mb-3"><?= htmlspecialchars($c['description'] ?? 'Không có mô tả') ?></div>
                <div class="row g-2 text-center mt-auto">
                    <div class="col-4">
                        <div class="fw-bold text-dark"><?= $studentCount ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;">Sinh viên</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-dark"><?= $sessTotal ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;">Buổi học</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-dark"><?= $c['credits'] ?? '--' ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;">Tín chỉ</div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/teacher/course-students/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill" onclick="event.stopPropagation()">
                        <i class="bi bi-people me-1"></i>Danh sách sinh viên
                    </a>
                    <a href="<?= BASE_URL ?>/teacher/sessions?course_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary flex-fill" onclick="event.stopPropagation()">
                        <i class="bi bi-calendar me-1"></i>Lịch học
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php';
?>
