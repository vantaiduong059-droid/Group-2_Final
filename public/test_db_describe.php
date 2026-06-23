<?php
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=attendance_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== DESCRIBE quiz_submissions ===\n";
    $stmt = $db->query("DESCRIBE quiz_submissions");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
}
