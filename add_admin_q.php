<?php
session_start();
require("lib/conn.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user role and info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role, username FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$role = $user['role'];
$username = $user['username'];

// Handle Add to Queue (Admin Function)
if ($role === 'Admin' && isset($_POST['add_to_queue'])) {
    $patient_id = $_POST['patient_id'];
    
    // Add patient to queue
    $stmt = $conn->prepare("INSERT INTO queue (patient_id, status) VALUES (?, 'waiting')");
    $stmt->execute([$patient_id]);
    
    // Refresh to show updated queue
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle View Display button
if (isset($_POST['view_display'])) {
    header("Location: ".$_SERVER['PHP_SELF'].'?display=1');
    exit();
}

// Get patients (for admin)
$patients = [];
if ($role === 'Admin') {
    $stmt = $conn->query("SELECT * FROM patients");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get current queue
$stmt = $conn->query("SELECT q.id, p.name, q.status 
                     FROM queue q 
                     JOIN patients p ON q.patient_id = p.id 
                     WHERE q.status = 'waiting'
                     ORDER BY q.id ASC");
$queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current queue position (for patient)
$queue_position = 0;
if ($role === 'patient') {
    $stmt = $conn->prepare("SELECT COUNT(*) as position FROM queue WHERE status = 'waiting' AND id < (SELECT id FROM queue WHERE patient_id = ? ORDER BY id DESC LIMIT 1)");
    $stmt->execute([$user_id]);
    $queue_position = $stmt->fetch(PDO::FETCH_ASSOC)['position'] + 1;
}

// Check if we should show just the display
$show_display_only = isset($_GET['display']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $show_display_only ? 'Queue Display' : 'Queue Management' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1d3557;
            --secondary-color: #457b9d;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .queue-display {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .queue-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 5px solid var(--primary-color);
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .queue-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .current {
            background-color: #d1e7dd;
            border-left-color: #198754;
        }
        
        .current .queue-number {
            color: #198754;
        }
        
        .management-panel {
            max-width: 1000px;
            margin: 20px auto;
        }
        
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .display-only {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .navbar {
            background-color: var(--primary-color);
        }
        
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php if (!$show_display_only): ?>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-clinic-medical me-2"></i>Queue Management System
            </a>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($username) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container">
        <?php if ($show_display_only): ?>
            <!-- Display Only Mode -->
            <div class="display-only">
                <div class="queue-display">
                    <h2 class="text-center mb-4">Current Queue</h2>
                    
                    <?php if (empty($queue)): ?>
                        <div class="alert alert-info">No patients in the queue currently.</div>
                    <?php else: ?>
                        <?php foreach ($queue as $index => $item): ?>
                            <div class="queue-item <?= $index === 0 ? 'current' : '' ?>">
                                <div>
                                    <span class="queue-number">#<?= $index + 1 ?></span>
                                    <span class="ms-3"><?= htmlspecialchars($item['name']) ?></span>
                                </div>
                                <span class="badge bg-primary"><?= ucfirst($item['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Management
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Management Interface -->
            <div class="management-panel">
                <?php if ($role === 'admin'): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><i class="fas fa-user-plus me-2"></i>Add Patient to Queue</h4>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="patient_id" class="form-label">Select Patient</label>
                                        <select class="form-select" name="patient_id" required>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?= $patient['id'] ?>">
                                                    <?= htmlspecialchars($patient['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="add_to_queue" class="btn btn-primary w-100">
                                            <i class="fas fa-plus-circle me-1"></i> Add to Queue
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($role === 'patient'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h4><i class="fas fa-user me-2"></i>Patient Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-clock me-2"></i>Your Current Queue Position</h5>
                                <p class="display-4 text-center"><?= $queue_position ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Queue Display Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-list-ol me-2"></i>Current Queue</h4>
                        <form method="post">
                            <button type="submit" name="view_display" class="btn btn-light">
                                <i class="fas fa-expand me-1"></i> Fullscreen View
                            </button>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (empty($queue)): ?>
                            <div class="alert alert-warning">No patients in the queue currently.</div>
                        <?php else: ?>
                            <div class="queue-display">
                                <?php foreach ($queue as $index => $item): ?>
                                    <div class="queue-item <?= $index === 0 ? 'current' : '' ?>">
                                        <div>
                                            <span class="queue-number">#<?= $index + 1 ?></span>
                                            <span class="ms-3"><?= htmlspecialchars($item['name']) ?></span>
                                        </div>
                                        <span class="badge bg-primary"><?= ucfirst($item['status']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh the display every 30 seconds
        <?php if ($show_display_only): ?>
            setTimeout(function() {
                window.location.reload();
            }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>