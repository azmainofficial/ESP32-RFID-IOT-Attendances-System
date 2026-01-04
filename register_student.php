<?php
// register_student.php - Convert temp scan to student
require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Get RFID UID from query parameter
$rfid_uid = isset($_GET['rfid']) ? $_GET['rfid'] : '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfid_uid = $_POST['rfid_uid'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    try {
        // Validate inputs
        if (empty($name) || empty($email) || empty($rfid_uid)) {
            throw new Exception("All fields are required");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if RFID already exists
        $query = "SELECT id FROM students WHERE rfid_uid = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$rfid_uid]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("RFID UID already registered");
        }
        
        // Insert new student
        $query = "INSERT INTO students (name, rfid_uid, email) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $rfid_uid, $email]);
        
        // Mark temp scan as processed
        $query = "UPDATE temp_scans SET processed = 1 WHERE rfid_uid = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$rfid_uid]);
        
        $message = "Student registered successfully!";
        $rfid_uid = ''; // Clear form
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get pending registrations
try {
    $query = "SELECT * FROM temp_scans WHERE processed = 0 ORDER BY scan_time DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending_scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Student Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Registration Form -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="rfid_uid" class="form-label">RFID UID</label>
                                <input type="text" class="form-control" id="rfid_uid" name="rfid_uid" 
                                       value="<?php echo htmlspecialchars($rfid_uid); ?>" required readonly>
                                <div class="form-text">Scan a card first, then fill other details</div>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Register Student</button>
                            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                        </form>
                        
                        <!-- Pending Registrations Table -->
                        <hr class="my-4">
                        <h5>Pending Registrations</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>RFID UID</th>
                                        <th>Device UID</th>
                                        <th>Scan Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_scans)): ?>
                                        <?php foreach ($pending_scans as $scan): ?>
                                        <tr>
                                            <td><code><?php echo $scan['rfid_uid']; ?></code></td>
                                            <td><?php echo $scan['device_uid']; ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($scan['scan_time'])); ?></td>
                                            <td>
                                                <a href="?rfid=<?php echo $scan['rfid_uid']; ?>" 
                                                   class="btn btn-sm btn-primary">Register</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                No pending registrations
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus name field when RFID is populated
        document.addEventListener('DOMContentLoaded', function() {
            const rfidField = document.getElementById('rfid_uid');
            if (rfidField.value) {
                document.getElementById('name').focus();
            }
        });
    </script>
</body>
</html>