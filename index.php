<?php
// index.php - Main Dashboard
require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

// Get today's stats
$today = date('Y-m-d');
$stats = [];

try {
    // Today's total attendance
    $query = "SELECT COUNT(*) as total FROM attendance WHERE DATE(scan_time) = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$today]);
    $stats['today_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total students
    $query = "SELECT COUNT(*) as total FROM students";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total devices
    $query = "SELECT COUNT(*) as total FROM devices";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_devices'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending registrations
    $query = "SELECT COUNT(*) as total FROM temp_scans WHERE processed = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent attendance
    $query = "SELECT a.scan_time, s.name, s.rfid_uid, c.subject_name, r.room_name 
              FROM attendance a 
              JOIN students s ON a.student_id = s.id 
              JOIN classes c ON a.class_id = c.id 
              JOIN rooms r ON c.room_id = r.id 
              ORDER BY a.scan_time DESC LIMIT 10";
    $recent_stmt = $db->prepare($query);
    $recent_stmt->execute();
    $recent_attendance = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <nav class="navbar navbar-light">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#">
                            <i class="bi bi-r-square"></i> RFID System
                        </a>
                    </div>
                </nav>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="device_manager.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cpu"></i> Device Manager
                    </a>
                    <a href="class_scheduler.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-event"></i> Class Scheduler
                    </a>
                    <a href="student_registration.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-plus"></i> Student Registration
                    </a>
                    <a href="view_attendance.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-check"></i> View Attendance
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Dashboard</h1>
                        <div class="text-muted"><?php echo date('F j, Y'); ?></div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-primary">Today's Attendance</h5>
                                            <h2 class="mb-0"><?php echo $stats['today_total']; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-check-circle-fill text-primary fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-success">Total Students</h5>
                                            <h2 class="mb-0"><?php echo $stats['total_students']; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-people-fill text-success fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-warning">Active Devices</h5>
                                            <h2 class="mb-0"><?php echo $stats['total_devices']; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-cpu-fill text-warning fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-danger">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-danger">Pending Registrations</h5>
                                            <h2 class="mb-0"><?php echo $stats['pending_registrations']; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Recent Attendance</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recent_attendance)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Student</th>
                                                    <th>RFID UID</th>
                                                    <th>Subject</th>
                                                    <th>Room</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_attendance as $record): ?>
                                                <tr>
                                                    <td><?php echo date('H:i:s', strtotime($record['scan_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo $record['rfid_uid']; ?></span></td>
                                                    <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['room_name']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">No attendance records for today</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>