<?php
require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $subject_name = trim($_POST['subject_name']);
    $room_id = $_POST['room_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $day_of_week = $_POST['day_of_week'];
    
    try {
        $query = "INSERT INTO classes (subject_name, room_id, start_time, end_time, day_of_week) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$subject_name, $room_id, $start_time, $end_time, $day_of_week]);
        $message = "Class scheduled successfully!";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get rooms
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
    <title>Class Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h2>Class Scheduler</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Schedule New Class</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="subject_name" class="form-label">Subject Name</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select a room</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['room_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="day_of_week" class="form-label">Day of Week</label>
                                <select class="form-select" id="day_of_week" name="day_of_week" required>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="7">Sunday</option>
                                </select>
                            </div>
                            <button type="submit" name="add_class" class="btn btn-primary">Schedule Class</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>