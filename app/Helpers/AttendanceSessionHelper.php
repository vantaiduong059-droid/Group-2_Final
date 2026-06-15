<?php
// app/Helpers/AttendanceSessionHelper.php

class AttendanceSessionHelper {
    /**
     * Xác định trạng thái của phiên điểm danh
     * Trả về array gồm:
     * - 'status': 'chua_mo', 'dang_mo', 'da_dong'
     * - 'remaining_minutes': số phút còn lại (nếu đang mở, mặc định 0 nếu đã đóng)
     */
    public static function getStatus($session) {
        if (!$session) {
            return [
                'status' => 'da_dong',
                'remaining_minutes' => 0
            ];
        }

        $status = $session['status'] ?? 'scheduled';

        if ($status === 'completed') {
            return [
                'status' => 'da_dong',
                'remaining_minutes' => 0
            ];
        }

        if ($status !== 'active') {
            return [
                'status' => 'chua_mo',
                'remaining_minutes' => 0
            ];
        }

        // Nếu status của buổi học là active, kiểm tra thời gian hết hạn của phiên
        $expiresAt = $session['attendance_expires_at'] ?? null;
        if (empty($expiresAt)) {
            // Trường hợp active nhưng không có hạn hết hạn (ví dụ Manual)
            return [
                'status' => 'dang_mo',
                'remaining_minutes' => 9999
            ];
        }

        $expiresTime = strtotime($expiresAt);
        if ($expiresTime === false) {
            return [
                'status' => 'da_dong',
                'remaining_minutes' => 0
            ];
        }

        $diff = $expiresTime - time();
        if ($diff <= 0) {
            return [
                'status' => 'da_dong',
                'remaining_minutes' => 0
            ];
        }

        return [
            'status' => 'dang_mo',
            'remaining_minutes' => (int)ceil($diff / 60)
        ];
    }
}
