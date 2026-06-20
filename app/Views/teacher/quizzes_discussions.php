<?php ob_start(); ?>

<style>
    .nav-tabs-modern {
        border-bottom: 2px solid var(--border-color-darker);
        gap: 8px;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: all 0.2s ease;
        background: transparent;
    }
    .nav-tabs-modern .nav-link:hover {
        color: var(--primary);
        background: rgba(37, 99, 235, 0.04);
    }
    .nav-tabs-modern .nav-link.active {
        color: var(--primary);
        background: transparent;
    }
    .nav-tabs-modern .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
    }
    .quiz-question-item {
        padding: 14px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background: #fafafa;
        margin-bottom: 12px;
        position: relative;
    }
    .question-actions {
        position: absolute;
        top: 14px;
        right: 14px;
        display: flex;
        gap: 6px;
    }
    .discussion-card {
        border-radius: 14px;
        border: 1px solid var(--border-color);
        padding: 16px;
        margin-bottom: 16px;
        background: #ffffff;
    }
    .reply-item {
        border-left: 3px solid var(--primary);
        padding-left: 12px;
        margin-top: 10px;
        font-size: 0.88rem;
    }
</style>

<div class="d-flex flex-column gap-4">
    <!-- Chọn lớp giảng dạy -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Quản lý Quiz & Thảo luận Lớp học</h3>
            <div class="text-muted small">Thiết lập bài trắc nghiệm mini, câu hỏi và diễn đàn trao đổi lớp học phần.</div>
        </div>
        <div style="width: 250px;">
            <label class="form-label fw-semibold text-secondary small mb-1">Chọn lớp học phần phụ trách</label>
            <select class="form-select" id="courseSelector" onchange="onCourseChange()">
                <option value="">-- Chọn lớp học --</option>
                <?php foreach ($myCourses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['code'] ?> - <?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Dữ liệu trống khi chưa chọn lớp -->
    <div id="noCourseWarning" class="card-modern py-5 text-center text-muted">
        <i class="bi bi-journal-check fs-1"></i>
        <h5 class="fw-bold mt-3">Vui lòng chọn một lớp học phần phụ trách ở góc trên để quản lý dữ liệu.</h5>
    </div>

    <!-- Khung chức năng chính -->
    <div id="mainContentArea" style="display: none;" class="row g-4">
        <!-- Logs tương tác bên trái (col-md-4) -->
        <div class="col-12 col-md-4">
            <div class="card-modern p-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-muted me-2"></i>Log Tương tác Sinh viên</h5>
                <div id="classLogsContainer" style="max-height: 500px; overflow-y: auto;" class="d-flex flex-column gap-3">
                    <div class="text-muted small text-center py-4">Đang tải lịch sử tương tác...</div>
                </div>
            </div>
        </div>

        <!-- CRUD bên phải (col-md-8) -->
        <div class="col-12 col-md-8">
            <div class="card-modern p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <ul class="nav nav-tabs nav-tabs-modern border-0 mb-0" id="teacherTab" role="tablist" style="gap: 8px;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="quiz-tab" data-bs-toggle="tab" data-bs-target="#tab-quiz" type="button" role="tab">
                                <i class="bi bi-patch-question me-1"></i> Mini-Quiz & Câu hỏi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="discussion-tab" data-bs-toggle="tab" data-bs-target="#tab-discussion" type="button" role="tab">
                                <i class="bi bi-chat-dots me-1"></i> Diễn đàn Thảo luận lớp
                            </button>
                        </li>
                    </ul>
                    <button class="btn btn-sm btn-outline-secondary px-3 py-1.5 fw-semibold" onclick="loadDiscussionsAndLogs(); loadQuizzes();" title="Làm mới"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
                </div>

                <div class="tab-content" id="teacherTabContent">
                    <!-- Tab Quiz -->
                    <div class="tab-pane fade show active" id="tab-quiz" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Danh sách Mini-Quiz</h6>
                            <button class="btn btn-sm btn-primary-modern px-3 fw-bold" onclick="openQuizModal()">
                                <i class="bi bi-plus-lg"></i> Tạo Quiz mới
                            </button>
                        </div>
                        <div id="quizzesContainer" class="d-flex flex-column gap-3">
                            <!-- JS load quizzes -->
                        </div>
                    </div>

                    <!-- Tab Thảo luận -->
                    <div class="tab-pane fade" id="tab-discussion" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Chủ đề thảo luận lớp</h6>
                            <button class="btn btn-sm btn-primary-modern px-3 fw-bold" onclick="openDiscussionModal()">
                                <i class="bi bi-chat-plus"></i> Đăng chủ đề mới
                            </button>
                        </div>
                        <div id="discussionsContainer">
                            <!-- JS load discussions -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo/sửa Quiz -->
<div class="modal fade" id="quizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="quizModalTitle">Tạo Mini-Quiz mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="quizForm">
                    <input type="hidden" id="quizId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tiêu đề Quiz</label>
                        <input type="text" class="form-control" id="quizTitle" placeholder="Ví dụ: Quiz ôn tập chương 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Chọn buổi học áp dụng</label>
                        <select class="form-select" id="quizSessionId" required>
                            <!-- JS load completed sessions -->
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Thời gian bắt đầu</label>
                            <input type="datetime-local" class="form-control" id="quizStartTime" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small">Thời gian kết thúc</label>
                            <input type="datetime-local" class="form-control" id="quizEndTime" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tổng điểm tối đa</label>
                        <input type="number" class="form-control" id="quizTotalMarks" value="10" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveQuiz()">Lưu Quiz</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal quản lý câu hỏi trắc nghiệm -->
<div class="modal fade" id="questionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Câu hỏi trắc nghiệm của Quiz</h5>
                    <div class="text-muted small" id="questionsModalSubtitle">Quiz: ...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Form thêm/sửa câu hỏi bên trái -->
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3 text-primary" id="questionFormTitle">Thêm câu hỏi mới</h6>
                        <form id="questionForm">
                            <input type="hidden" id="questionId">
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-secondary small">Nội dung câu hỏi</label>
                                <textarea class="form-control" id="questionText" rows="3" required></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-secondary small">Lựa chọn A</label>
                                <input type="text" class="form-control form-control-sm" id="optionA" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-secondary small">Lựa chọn B</label>
                                <input type="text" class="form-control form-control-sm" id="optionB" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-secondary small">Lựa chọn C</label>
                                <input type="text" class="form-control form-control-sm" id="optionC" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-secondary small">Lựa chọn D</label>
                                <input type="text" class="form-control form-control-sm" id="optionD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Đáp án đúng</label>
                                <select class="form-select form-select-sm" id="correctOption" required>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary-modern w-100 fw-bold" onclick="saveQuestion()">Lưu câu hỏi</button>
                            <button type="button" class="btn btn-sm btn-light w-100 fw-semibold mt-2" onclick="resetQuestionForm()">Reset Form</button>
                        </form>
                    </div>
                    <!-- Danh sách câu hỏi bên phải -->
                    <div class="col-md-7" style="max-height: 480px; overflow-y: auto;">
                        <h6 class="fw-bold mb-3 text-dark">Danh sách câu hỏi hiện tại</h6>
                        <div id="questionsContainer">
                            <!-- JS load questions -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo Thảo luận mới -->
<div class="modal fade" id="discussionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Đăng chủ đề thảo luận mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discussionForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Tiêu đề chủ đề</label>
                        <input type="text" class="form-control" id="discTitle" placeholder="Ví dụ: Thảo luận về đồ án cuối kỳ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Nội dung trao đổi / Câu hỏi thảo luận</label>
                        <textarea class="form-control" id="discContent" rows="4" placeholder="Nhập câu hỏi hoặc gợi ý thảo luận cho cả lớp..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary-modern" onclick="saveDiscussion()">Đăng bài</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Định nghĩa biến để JS gọi AJAX
    let currentQuizId = null;
</script>

<?php 
// Nạp file JS xử lý CRUD tương ứng
$extraJs = '<script src="' . BASE_URL . '/assets/js/teacher_quizzes.js?v=' . time() . '"></script>';
$content = ob_get_clean();
require_once '../app/Views/layouts/teacher_layout.php'; 
?>
