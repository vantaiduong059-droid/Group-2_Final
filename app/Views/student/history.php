<?php ob_start(); ?>
<style>
.history-stat { background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid var(--border-color); text-align: center; }
</style>

<div class="d-flex flex-column gap-4">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active">Lịch sử & Thống kê</li>
        </ol></nav>
        <h3 class="fw-bold mb-0">Lịch sử điểm danh & Tương tác</h3>
    </div>

    <!-- Attendance stats by course -->
    <div class="card-modern p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Thống kê chuyên cần theo môn học</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Môn học</th>
                        <th class="text-center">Tổng buổi đã diễn ra</th>
                        <th class="text-center text-success">Có mặt</th>
                        <th class="text-center text-warning">Đi muộn</th>
                        <th class="text-center text-danger">Vắng</th>
                        <th class="text-center">Tỷ lệ chuyên cần</th>
                    </tr>
                </thead>
                <tbody id="courseAttSummaryTable">
                    <tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CPI by class -->
    <div class="card-modern p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-award-fill text-warning me-2"></i>Chỉ số tham gia (CPI) theo lớp</h6>
        <div id="cpiByClassList">
            <div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
        </div>
    </div>

    <!-- Attendance history table -->
    <div class="card-modern p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2"></i>Chi tiết điểm danh</h6>
            <select class="form-select form-select-sm" id="histCourseFilter" style="max-width:200px;">
                <option value="">Tất cả lớp</option>
                <?php foreach ($myCourses ?? [] as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Ngày</th><th>Lớp học</th><th>Giờ học</th><th>Trạng thái</th><th>Hình thức</th><th>Thời điểm ĐD</th></tr></thead>
                <tbody id="attHistoryTable"><tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- Interaction history -->
    <div class="card-modern p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-primary me-2"></i>Lịch sử tương tác gần đây</h6>
        <div id="interactionHistList">
            <div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>
        </div>
    </div>
</div>

<script>
let historyData = { attendance: [], interactions: [], cpi_by_class: [] };

document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    document.getElementById('histCourseFilter').addEventListener('change', filterAttendance);
});

function loadHistory() {
    fetch(`${BASE_URL}/api/student/history`)
        .then(r => r.json()).then(res => {
            if (res.status !== 'success') return;
            historyData = res.data;
            
            // Render detailed attendance summary table by course
            renderCourseAttSummary(historyData.attendance_by_course);

            // CPI by class
            renderCpiByClass(historyData.cpi_by_class);
            
            // Attendance & Interaction tables (filter based on selected course)
            filterAttendance();
        }).catch(err => console.error(err));
}

function renderCourseAttSummary(coursesAtt) {
    const tbody = document.getElementById('courseAttSummaryTable');
    if (!coursesAtt || coursesAtt.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Chưa đăng ký môn học nào</td></tr>';
        return;
    }
    
    let totalPassed = 0, totalPres = 0, totalLate = 0, totalAbs = 0;
    
    let html = coursesAtt.map(c => {
        totalPassed += c.passed_sessions;
        totalPres += c.present;
        totalLate += c.late;
        totalAbs += c.absent;
        
        let absentText = c.absent;
        if (c.is_over_limit) {
            absentText = `<span class="text-danger fw-bold" title="Vượt quá giới hạn vắng cho phép (${c.absent_limit} buổi)">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>${c.absent}
            </span>`;
        }
        
        const rateText = c.passed_sessions > 0 ? `${c.attendance_rate}%` : '<span class="text-muted small">Chưa có dữ liệu</span>';
        
        return `<tr>
            <td>
                <div class="fw-semibold">${c.course_name}</div>
                <div class="text-muted small">${c.course_code}</div>
            </td>
            <td class="text-center">${c.passed_sessions}</td>
            <td class="text-center text-success fw-medium">${c.present}</td>
            <td class="text-center text-warning fw-medium">${c.late}</td>
            <td class="text-center fw-medium">${absentText}</td>
            <td class="text-center fw-bold">${rateText}</td>
        </tr>`;
    }).join('');
    
    // Thêm dòng tổng cộng
    const overallRate = totalPassed > 0 ? Math.round((totalPres + totalLate) / totalPassed * 1000) / 10 : 100;
    const overallRateText = totalPassed > 0 ? `${overallRate}%` : '<span class="text-muted small">Chưa có dữ liệu</span>';
    
    html += `<tr class="table-light fw-bold border-top-2">
        <td>Tổng cộng toàn học kỳ</td>
        <td class="text-center">${totalPassed}</td>
        <td class="text-center text-success">${totalPres}</td>
        <td class="text-center text-warning">${totalLate}</td>
        <td class="text-center text-danger">${totalAbs}</td>
        <td class="text-center text-primary">${overallRateText}</td>
    </tr>`;
    
    tbody.innerHTML = html;
}

function renderCpiByClass(cpiList) {
    const el = document.getElementById('cpiByClassList');
    if (!cpiList || cpiList.length === 0) {
        el.innerHTML = '<div class="text-muted small text-center py-3">Chưa có dữ liệu CPI</div>';
        return;
    }
    el.innerHTML = cpiList.map(c => {
        const hasPassedSess = c.passed_sessions && c.passed_sessions > 0;
        
        let scoreText, color, barWidth, barColor;
        
        if (!hasPassedSess) {
            scoreText = '<span class="text-muted small fw-medium">Chưa có dữ liệu</span>';
            color = '#64748b'; // xám slate
            barWidth = 100;
            barColor = '#cbd5e1'; // xám nhạt
        } else {
            const pct = Math.min(100, Math.round(c.total_score || 0));
            scoreText = `${pct}/100`;
            color = pct >= 70 
                ? '#10b981' 
                : (pct >= 40 ? '#f59e0b' : '#ef4444');
            barWidth = pct;
            barColor = color;
        }
        
        return `<div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small">${c.course_name} <span class="text-muted">(${c.course_code})</span></span>
                <span class="fw-bold" style="color:${color};">${scoreText}</span>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:${barWidth}%;background:${barColor};border-radius:4px;transition:width 1s;"></div>
            </div>
        </div>`;
    }).join('');
}

function filterAttendance() {
    const courseId = document.getElementById('histCourseFilter').value;
    const courseCode = document.getElementById('histCourseFilter').options[document.getElementById('histCourseFilter').selectedIndex]?.text;
    
    let filteredAtt = historyData.attendance;
    let filteredInt = historyData.interactions;
    
    if (courseId && courseCode && courseCode !== 'Tất cả lớp') {
        filteredAtt = historyData.attendance.filter(a => a.course_code === courseCode);
        filteredInt = historyData.interactions.filter(i => i.course_code === courseCode);
    }
    
    renderAttendanceTable(filteredAtt);
    renderInteractions(filteredInt);
}

function renderAttendanceTable(data) {
    const tbody = document.getElementById('attHistoryTable');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có lịch sử điểm danh</td></tr>';
        return;
    }
    const statusMap = { present: ['bg-success','Có mặt'], late: ['bg-warning text-dark','Đi muộn'], absent: ['bg-danger','Vắng'], null: ['bg-danger','Vắng'] };
    tbody.innerHTML = data.map(r => {
        const [cls, txt] = statusMap[r.status] || statusMap[null];
        const isVang = (!r.status || r.status === 'absent');
        const hinhThuc = isVang ? '--' : (r.method_name || '--');
        return `<tr>
            <td class="small">${r.session_date}</td>
            <td class="small fw-semibold">${r.course_code}</td>
            <td class="small">${(r.start_time||'').slice(0,5)} - ${(r.end_time||'').slice(0,5)}</td>
            <td><span class="badge ${cls} small">${txt}</span></td>
            <td class="small text-muted">${hinhThuc}</td>
            <td class="small text-muted">${r.recorded_at ? r.recorded_at.slice(0,16) : '--'}</td>
        </tr>`;
    }).join('');
}

function renderInteractions(interactions) {
    const el = document.getElementById('interactionHistList');
    if (!interactions || interactions.length === 0) {
        el.innerHTML = '<div class="text-muted small text-center py-3">Chưa có hoạt động tương tác nào</div>';
        return;
    }
    const typeMap = { question: ['bi-question-circle text-primary','Đặt câu hỏi'], answer: ['bi-check-circle text-success','Trả lời'], discussion: ['bi-chat-dots text-info','Thảo luận'] };
    el.innerHTML = `<div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>Thời gian</th><th>Loại</th><th>Lớp</th><th>Điểm</th></tr></thead>
        <tbody>${interactions.map(i => {
            const [icon, label] = typeMap[i.type] || ['bi-lightning','Tương tác'];
            return `<tr>
                <td class="small">${i.created_at?.slice(0,16) || '--'}</td>
                <td class="small"><i class="bi ${icon} me-1"></i>${label}</td>
                <td class="small">${i.course_code || '--'}</td>
                <td class="small fw-bold text-success">+${i.points || 0}</td>
            </tr>`;
        }).join('')}</tbody>
    </table></div>`;
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/Views/layouts/student_layout.php';
?>
