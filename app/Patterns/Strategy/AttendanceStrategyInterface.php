<?php
// app/Patterns/Strategy/AttendanceStrategyInterface.php

interface AttendanceStrategyInterface {
    /**
     * Validate and record attendance
     * @param int $sessionId
     * @param int $studentId
     * @param array $data Additional data (e.g., code, qr_token)
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateAndRecord($sessionId, $studentId, $data);
}
