<?php
// api/new_scan.php - Registration helper API
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
    // Check if RFID already exists in students
    $query = "SELECT id FROM students WHERE rfid_uid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$rfid_uid]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'info',
            'message' => 'Student already registered'
        ]);
        exit;
    }
    
    // Insert into temp_scans
    $query = "INSERT INTO temp_scans (device_uid, rfid_uid, scan_time) 
              VALUES (?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE scan_time = NOW(), processed = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$device_uid, $rfid_uid]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Card scanned for registration',
        'rfid_uid' => $rfid_uid
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>