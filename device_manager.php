<?php
require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    $device_uid = trim($_POST['device_uid']);
    $room_id = $_POST['room_id'];
    
    try {
        $query = "INSERT INTO devices (device_uid, assigned_room_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$device_uid, $room_id]);
        $message = "Device added successfully!";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get rooms for dropdown
$rooms = [];
try {
    $query = "SELECT * FROM rooms ORDER BY room_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h2>Device Manager</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Add New Device</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="device_uid" class="form-label">Device UID</label>
                                <input type="text" class="form-control" id="device_uid" name="device_uid" required>
                                <div class="form-text">Unique identifier from ESP32</div>
                            </div>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Assign to Room</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select a room</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['room_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_device" class="btn btn-primary">Add Device</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>