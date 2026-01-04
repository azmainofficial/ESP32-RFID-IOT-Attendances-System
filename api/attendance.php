<?php
// api/attendance.php - Main attendance recording API
header('Content-Type: application/json');
require_once '../db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input)) {
    $input = $_POST;
}

// Validate input
if (!isset($input['device_uid']) || !isset($input['rfid_uid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$device_uid = trim($input['device_uid']);
$rfid_uid = trim($input['rfid_uid']);

$database = new Database();
$db = $database->getConnection();

try {
    // Step 1: Device Check
    $query = "SELECT d.*, r.room_name FROM devices d 
              LEFT JOIN rooms r ON d.assigned_room_id = r.id 
              WHERE d.device_uid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$device_uid]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Device not registered'
        ]);
        exit;
    }
    
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($device['assigned_room_id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Device not assigned to any room'
        ]);
        exit;
    }
    
    // Step 2: Schedule Check - Find ongoing class in assigned room
    $current_time = date('H:i:s');
    $current_day = date('N'); // 1=Monday, 7=Sunday
    
    $query = "SELECT * FROM classes 
              WHERE room_id = ? 
              AND day_of_week = ? 
              AND start_time <= ? 
              AND end_time >= ? 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$device['assigned_room_id'], $current_day, $current_time, $current_time]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No ongoing class in this room'
        ]);
        exit;
    }
    
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 3: Student Check
    $query = "SELECT id, name FROM students WHERE rfid_uid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$rfid_uid]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'RFID not registered. Please register student first.'
        ]);
        exit;
    }
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 4: Duplicate Check
    $today = date('Y-m-d');
    $query = "SELECT * FROM attendance 
              WHERE student_id = ? 
              AND class_id = ? 
              AND DATE(scan_time) = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$student['id'], $class['id'], $today]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Already marked present for this class today'
        ]);
        exit;
    }
    
    // Step 5: Determine status (late if > 10 minutes after start)
    $start_time = strtotime($class['start_time']);
    $scan_time = strtotime($current_time);
    $late_threshold = 10 * 60; // 10 minutes in seconds
    
    $status = 'present';
    if (($scan_time - $start_time) > $late_threshold) {
        $status = 'late';
    }
    
    // Step 6: Insert attendance record
    $query = "INSERT INTO attendance (student_id, class_id, scan_time, status) 
              VALUES (?, ?, NOW(), ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$student['id'], $class['id'], $status]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance recorded successfully',
        'data' => [
            'student_name' => $student['name'],
            'subject' => $class['subject_name'],
            'room' => $device['room_name'],
            'time' => $current_time,
            'status' => $status
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>