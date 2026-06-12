<?php
// app/Patterns/Observer/AttendanceSubject.php
require_once 'SubjectInterface.php';

class AttendanceSubject implements SubjectInterface {
    private $observers = [];

    public function attach(ObserverInterface $observer) {
        $this->observers[] = $observer;
    }

    public function detach(ObserverInterface $observer) {
        $this->observers = array_filter($this->observers, function($obs) use ($observer) {
            return $obs !== $observer;
        });
    }

    public function notify($eventData) {
        foreach ($this->observers as $observer) {
            $observer->update($eventData);
        }
    }
    
    // Method trigger action chính
    public function recordAttendance($sessionId, $studentId, $status) {
        // ... (Logic insert vào CSDL qua Repository sẽ được gọi từ Controller) ...
        
        // Sau khi insert xong, notify các observers
        $eventData = [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status' => $status
        ];
        
        $this->notify($eventData);
    }
}
