<?php ob_start(); ?>

<style>
    .upcoming-event-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        filter: brightness(0.95);
    }
    body.dark-mode .upcoming-event-item:hover {
        filter: brightness(1.1);
    }
</style>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: var(--text-main);">Trang chủ</h2>
        <div class="text-muted">Chào mừng bạn trở lại, <span class="fw-medium text-dark"><?= isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : 'Admin' ?></span>! 👋</div>
    </div>
</div>

<!-- TOP WIDGETS -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card-modern d-flex align-items-center">
            <div class="stat-icon blue me-3">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-info flex-grow-1">
                <div class="title">Tổng sinh viên</div>
                <div class="value" id="statStudents"><?= number_format($totalStudents) ?></div>
                <div class="trend text-muted"><i class="bi bi-dash"></i> Cập nhật gần đây</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-modern d-flex align-items-center">
            <div class="stat-icon green me-3">
                <i class="bi bi-book-half"></i>
            </div>
            <div class="stat-info flex-grow-1">
                <div class="title">Tổng lớp học phần</div>
                <div class="value" id="statClasses"><?= number_format($totalCourses) ?></div>
                <div class="trend text-muted"><i class="bi bi-dash"></i> Cập nhật gần đây</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-modern d-flex align-items-center">
            <div class="stat-icon orange me-3">
                <i class="bi bi-person-workspace"></i>
            </div>
            <div class="stat-info flex-grow-1">
                <div class="title">Tổng giảng viên</div>
                <div class="value" id="statTeachers"><?= number_format($totalTeachers) ?></div>
                <div class="trend text-muted"><i class="bi bi-dash"></i> Cập nhật gần đây</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-modern d-flex align-items-center">
            <div class="stat-icon purple me-3">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="stat-info flex-grow-1">
                <div class="title">Tỷ lệ chuyên cần</div>
                <div class="value"><?= $attendanceRate !== null ? $attendanceRate . '%' : '--' ?></div>
                <div class="trend text-muted"><i class="bi bi-dash"></i> Trung bình toàn hệ thống</div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-modern h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title-modern mb-0">Thống kê sinh viên</h5>
                <select class="form-select form-select-sm w-auto border-0 bg-light fw-medium text-muted">
                    <option>6 tháng qua</option>
                    <option>Năm nay</option>
                </select>
            </div>
            <div style="height: 300px; width: 100%;">
                <canvas id="studentLineChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-modern h-100">
            <h5 class="card-title-modern">Tình trạng điểm danh hôm nay</h5>
            <div style="height: 200px; width: 100%; display:flex; justify-content:center; position:relative;" class="mt-4">
                <canvas id="attendanceDonutChart"></canvas>
            </div>
            <div class="mt-4">
                <?php 
                $totalAtt = $attStats['present'] + $attStats['late'] + $attStats['absent'];
                $pctPresent = $totalAtt > 0 ? round(($attStats['present']/$totalAtt)*100,1) : 0;
                $pctLate = $totalAtt > 0 ? round(($attStats['late']/$totalAtt)*100,1) : 0;
                $pctAbsent = $totalAtt > 0 ? round(($attStats['absent']/$totalAtt)*100,1) : 0;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small"><i class="bi bi-circle-fill text-success me-2" style="font-size:8px;"></i>Có mặt</div>
                    <div class="fw-medium text-dark"><?= number_format($attStats['present']) ?> <span class="text-muted fw-normal ms-2">(<?= $pctPresent ?>%)</span></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small"><i class="bi bi-circle-fill text-warning me-2" style="font-size:8px;"></i>Đi muộn</div>
                    <div class="fw-medium text-dark"><?= number_format($attStats['late']) ?> <span class="text-muted fw-normal ms-2">(<?= $pctLate ?>%)</span></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small"><i class="bi bi-circle-fill text-danger me-2" style="font-size:8px;"></i>Vắng mặt</div>
                    <div class="fw-medium text-dark"><?= number_format($attStats['absent']) ?> <span class="text-muted fw-normal ms-2">(<?= $pctAbsent ?>%)</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTTOM LISTS -->
<div class="row g-4">
    <!-- Cột 1: Sinh viên mới nhập học -->
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title-modern mb-0">Sinh viên mới nhập học</h5>
                <a href="<?= BASE_URL ?>/admin/students" class="text-decoration-none text-primary fw-medium small">Xem tất cả <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Họ và tên</th>
                            <th>Lớp</th>
                            <th>Ngày nhập học</th>
                            <th>Phụ huynh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($newStudents)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu sinh viên mới</td></tr>
                        <?php else: ?>
                        <?php foreach($newStudents as $student): ?>
                        <tr>
                            <td>
                                <div class="table-user-cell">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&background=f1f5f9" class="table-user-avatar">
                                    <span class="table-user-name"><?= htmlspecialchars($student['full_name']) ?></span>
                                </div>
                            </td>
                            <td class="fw-medium">Chưa xếp</td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($student['created_at'])) ?></td>
                            <td class="text-muted">-</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Cột 2 & 3 gộp chung do không gian hẹp, chia nửa tiếp -->
    <div class="col-lg-6">
        <div class="row g-4">
            <!-- Sự kiện -->
            <div class="col-md-12">
                <div class="card-modern">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title-modern mb-0">Sự kiện sắp tới</h5>
                        <a href="<?= BASE_URL ?>/admin/sessions" class="text-decoration-none text-primary fw-medium small">Xem tất cả</a>
                    </div>
                    
                    <?php if (empty($upcomingEvents)): ?>
                    <div class="text-center text-muted">Không có sự kiện sắp tới.</div>
                    <?php else: ?>
                    <?php foreach($upcomingEvents as $event): ?>
                    <a href="<?= BASE_URL ?>/admin/sessions/<?= $event['id'] ?>/attendance" class="d-flex align-items-center gap-3 mb-3 p-3 rounded text-decoration-none upcoming-event-item" style="background-color: var(--bg-main); transition: all 0.2s ease;">
                        <div class="bg-primary-subtle text-primary rounded d-flex flex-column align-items-center justify-content-center px-3 py-2" style="min-width:60px;">
                            <span class="fw-bold fs-5" style="line-height:1;"><?= date('d', strtotime($event['session_date'])) ?></span>
                            <span class="small">Tháng <?= date('n', strtotime($event['session_date'])) ?></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($event['course_name']) ?></div>
                            <div class="text-muted small"><i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($event['start_time'])) ?></div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$extraJs = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Vẽ Biểu đồ đường (Line Chart)
    const ctxLine = document.getElementById("studentLineChart").getContext("2d");
    
    // Tạo gradient dưới đường cong
    let gradientLine = ctxLine.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, "rgba(59, 130, 246, 0.2)");
    gradientLine.addColorStop(1, "rgba(59, 130, 246, 0)");

    new Chart(ctxLine, {
        type: "line",
        data: {
            labels: ' . $chartLabels . ',
            datasets: [{
                label: "Sinh viên",
                data: ' . $chartValues . ',
                borderColor: "#3b82f6",
                backgroundColor: gradientLine,
                borderWidth: 3,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#3b82f6",
                pointBorderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4 // Làm mượt đường cong
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#1e293b",
                    padding: 12,
                    titleFont: { size: 13, family: "Inter" },
                    bodyFont: { size: 14, weight: "bold", family: "Inter" },
                    displayColors: false,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { color: "#f1f5f9", drawBorder: false },
                    border: { display: false },
                    ticks: { color: "#94a3b8", font: { family: "Inter", size: 12 } }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    border: { display: false },
                    ticks: { color: "#94a3b8", font: { family: "Inter", size: 12 } }
                }
            }
        }
    });

    // 2. Vẽ Biểu đồ tròn (Donut Chart)
    const ctxDonut = document.getElementById("attendanceDonutChart").getContext("2d");
    const hasAttData = ' . ($totalAtt > 0 ? 'true' : 'false') . ';
    const attDataValues = hasAttData ? [' . (int)$attStats["present"] . ', ' . (int)$attStats["late"] . ', ' . (int)$attStats["absent"] . '] : [1];
    const attDataColors = hasAttData ? ["#10b981", "#f59e0b", "#ef4444"] : ["#cbd5e1"];
    const attDataLabels = hasAttData ? ["Có mặt", "Đi muộn", "Vắng mặt"] : ["Chưa có dữ liệu"];

    new Chart(ctxDonut, {
        type: "doughnut",
        data: {
            labels: attDataLabels,
            datasets: [{
                data: attDataValues,
                backgroundColor: attDataColors,
                borderWidth: 0,
                hoverOffset: hasAttData ? 4 : 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "75%",
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: hasAttData,
                    backgroundColor: "#1e293b",
                    padding: 10,
                    bodyFont: { family: "Inter", size: 13 },
                    cornerRadius: 8
                }
            }
        },
        plugins: [{
            id: "textCenter",
            beforeDraw: function(chart) {
                var width = chart.width, height = chart.height, ctx = chart.ctx;
                ctx.restore();
                var fontSize = (height / 114).toFixed(2);
                ctx.font = "bold " + fontSize + "em Inter";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#0f172a";
                var text = hasAttData ? "' . number_format($totalAtt) . '" : "0",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2 - 10;
                ctx.fillText(text, textX, textY);
                
                ctx.font = "500 0.8rem Inter";
                ctx.fillStyle = "#64748b";
                var text2 = "Tổng số",
                    text2X = Math.round((width - ctx.measureText(text2).width) / 2),
                    text2Y = height / 2 + 15;
                ctx.fillText(text2, text2X, text2Y);
                ctx.save();
            }
        }]
    });
});
</script>
';
$content = ob_get_clean();
require_once '../app/Views/layouts/admin_layout.php'; 
?>
