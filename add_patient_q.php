<?php
session_start();
require('lib/conn.php');

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

// Fetch departments from the departments table
$departments = $conn->query("SELECT * FROM departments")->fetchAll();

// Initialize an empty array for services
$departmentServices = [];

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$departmentId = $_SESSION['dept_id'] ?? null;

// Check if department_id is set in the query string (GET)
if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $department_id = $_GET['department_id'];

    // Fetch services for the selected department from the services table
    $stmt = $conn->prepare("SELECT service_name FROM services WHERE department_id = :department_id");
    $stmt->execute([':department_id' => $department_id]);
    $departmentServices = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If no services are found, set an error message
    if (empty($departmentServices)) {
        $error_message = "No services found for this department.";
    }
} else {
    // If no department is selected, set an error message
    $error_message = "Please select a department.";
}

// Fetch ENUM values for the 'priority' column from 'queues' table
$priorities = [];
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM queues LIKE 'priority'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
        $enumValues = explode(",", $matches[1]);
        foreach ($enumValues as $value) {
            $priorities[] = trim($value, "'");
        }
    }
} catch (PDOException $e) {
    echo "Error fetching priorities: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = $_POST['service_name']; // Manual input
    $department_id = $_POST['department_id'];
    $priority = $_POST['priority'];

    // Department ID to prefix mapping
    $departmentPrefixes = [
        1 => 'BIL-',    // Billing
        2 => 'PHA-',   // Pharmacy
        3 => 'MED-',    // Medical Records
        4 => 'ULT-',    // Ultra-sound
        5 => 'XR-',     // X-ray
        6 => 'REH-',    // Rehabilitation
        7 => 'DIA-',    // Dialysis
        8 => 'LAB-',    // Laboratory
        9 => 'ADM-',    // Admitting
        10 => 'HMO-',   // HMO
        11 => 'INF-',   // Information
        13 => 'ER-',   // Emergency Room
        14 => 'SW-'   // Social Worker
    ];

    // Get the prefix for the department
    $prefix = $departmentPrefixes[$department_id] ?? 'GEN';

    // Fetch the last queue number globally (regardless of department)
    $stmt = $conn->prepare("SELECT queue_num FROM queues ORDER BY qid DESC LIMIT 1");
    $stmt->execute();
    $lastQueue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Extract numeric part from the last queue number
    if ($lastQueue && preg_match('/(\d+)$/', $lastQueue['queue_num'], $matches)) {
        $lastNum = intval($matches[1]);
    } else {
        $lastNum = 0;
    }

    // Pad to 3 digits for the next queue number
    $numericPart = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    $queue_num = $prefix . $numericPart;

    try {
        // Insert into the queues table
        $sql = "INSERT INTO queues (queue_num, service_name, department_id, status, priority, created_at)
                VALUES (:queue_num, :service_name, :department_id, 'waiting', :priority, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':queue_num' => $queue_num,
            ':service_name' => $service_name,
            ':department_id' => $department_id,
            ':priority' => $priority
        ]);
        if ($role == "Admin"){
          header("Location: queue_display_admin.php");
        } else{
          header("Location: queue_display.php");
        }
        
        exit();
    } catch (PDOException $e) {
        echo "Error adding to queue: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Patient to Queue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #2980b9;
      --accent-color: #e74c3c;
      --light-color: #ecf0f1;
      --dark-color: #2c3e50;
      --success-color: #2ecc71;
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .header {
      background-color: #1d3557;
      color: white;
      padding: 1.5rem 0;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .form-container {
      max-width: 800px;
      margin: 0 auto 3rem;
      background: white;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .form-title {
      color: var(--dark-color);
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
      position: relative;
      padding-bottom: 0.5rem;
    }
    
    .form-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: var(--primary-color);
    }
    
    .form-label {
      font-weight: 500;
      color: var(--dark-color);
      margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      border: 1px solid #ced4da;
      transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 0.75rem;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.3s;
      letter-spacing: 0.5px;
    }
    
    .btn-primary:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
      transform: translateY(-2px);
    }
    
    .error-message {
      color: var(--accent-color);
      text-align: center;
      font-size: 1rem;
      margin-bottom: 1.5rem;
      padding: 0.75rem;
      background-color: rgba(231, 76, 60, 0.1);
      border-radius: 8px;
      border-left: 4px solid var(--accent-color);
    }
    
    .info-box {
      background-color: var(--light-color);
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      border-left: 4px solid var(--primary-color);
    }
    
    .info-box h5 {
      color: var(--dark-color);
      margin-bottom: 0.5rem;
    }
    
    .info-box p {
      margin-bottom: 0;
      color: #7f8c8d;
    }
    
    .footer {
      background-color: var(--dark-color);
      color: white;
      padding: 1.5rem 0;
      margin-top: auto;
      text-align: center;
    }
    
    @media (max-width: 768px) {
      .form-container {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

  <header class="header">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-hospital-alt me-2"></i>Hospital Queue System</h1>
        <a href="queue_display.php" class="btn btn-outline-light">
          <i class="fas fa-tv me-2"></i>View Display
        </a>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="form-container">
      <h3 class="form-title"><i class="fas fa-user-plus me-2"></i>Add Patient to Queue</h3>
      
      <div class="info-box">
        <h5><i class="fas fa-info-circle me-2"></i>Instructions</h5>
        <p>Please select the department, service, and priority level to generate a queue number for the patient.</p>
      </div>
      
      <!-- Show error message if any -->
      <?php if (isset($error_message)): ?>
          <div class="error-message">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
          </div>
      <?php endif; ?>

      <form method="POST" action="add_patient_q.php">
        <div class="row">
          <!-- Department Dropdown -->
          <div class="col-md-6 mb-4">
            <label for="department_id" class="form-label">
              <i class="fas fa-building me-2"></i>Department
            </label>
            <select class="form-select" name="department_id" id="department_id" required>
              <option value="">Select Department</option>
              <?php foreach ($departments as $dept): ?>
                  <option value="<?= $dept['dept_id'] ?>" <?= isset($department_id) && $department_id == $dept['dept_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                  </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Service Dropdown -->
          <div class="col-md-6 mb-4">
            <label for="service_name" class="form-label">
              <i class="fas fa-procedures me-2"></i>Service
            </label>
            <select class="form-select" name="service_name" id="service_name" required>
              <option value="">Select Service</option>
              <?php if (!empty($departmentServices)): ?>
                  <?php foreach ($departmentServices as $service): ?>
                      <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
                  <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <div class="row">
          <!-- Priority Dropdown -->
          <div class="col-md-6 mb-4">
            <label for="priority" class="form-label">
              <i class="fas fa-exclamation-triangle me-2"></i>Priority
            </label>
            <select class="form-select" name="priority" id="priority" required>
              <option value="">Select Priority</option>
              <?php foreach ($priorities as $priorityOption): ?>
                  <option value="<?= htmlspecialchars($priorityOption) ?>">
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $priorityOption))) ?>
                  </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Submit Button -->
          <div class="col-md-6 d-flex align-items-end mb-4">
            <button type="submit" class="btn btn-primary w-100 py-3">
              <i class="fas fa-plus-circle me-2"></i>Add to Queue
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript to Update Service Dropdown -->
  <script>
    const departmentSelect = document.getElementById('department_id');
    departmentSelect.addEventListener('change', function () {
        const selectedDept = this.value;
        if (selectedDept) {
            window.location.href = `add_patient_q.php?department_id=${selectedDept}`;
        }
    });
    
    // Enhance form controls
    document.querySelectorAll('.form-control, .form-select').forEach(element => {
      element.addEventListener('focus', function() {
        this.parentElement.querySelector('.form-label').style.color = 'var(--primary-color)';
      });
      
      element.addEventListener('blur', function() {
        this.parentElement.querySelector('.form-label').style.color = 'var(--dark-color)';
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>