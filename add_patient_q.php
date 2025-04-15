<?php
require('lib/conn.php');

// Fetch departments from the departments table
$departments = $conn->query("SELECT * FROM departments")->fetchAll();

// Initialize an empty array for services
$departmentServices = [];

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
        2 => 'PHAR-',   // Pharmacy
        3 => 'MED-',    // Medical Records
        4 => 'ULT-',    // Ultra-sound
        5 => 'XR-',     // X-ray
        6 => 'REH-',    // Rehabilitation
        7 => 'DIA-',    // Dialysis
        8 => 'LAB-',    // Laboratory
        9 => 'ADM-',    // Admitting
        10 => 'HMO-',   // HMO
        11 => 'INF-',   // Information
    ];

    // Get the prefix for the department
    $prefix = $departmentPrefixes[$department_id] ?? 'GEN';

    // Fetch last queue number for the department
    $stmt = $conn->prepare("SELECT queue_num FROM queues WHERE department_id = :department_id ORDER BY qid DESC LIMIT 1");
    $stmt->execute([':department_id' => $department_id]);
    $lastQueue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Extract numeric part from the queue number
    if ($lastQueue && preg_match('/\d+$/', $lastQueue['queue_num'], $matches)) {
        $lastNum = intval($matches[0]);
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

        header("Location: queue_display.php");
        exit();
    } catch (PDOException $e) {
        echo "Error adding to queue: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Patient to Queue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
        background-color: #f0f4f8;
        padding: 30px;
    }
    .form-container {
        max-width: 600px;
        margin: auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
    }
    .error-message {
        color: red;
        text-align: center;
        font-size: 14px;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <h3 class="mb-4 text-center">Add Patient to Queue</h3>
    
    <!-- Show error message if any -->
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" action="add_patient_q.php">

        <!-- Department Dropdown -->
        <div class="mb-3">
            <label for="department_id" class="form-label">Department</label>
            <select class="form-select" name="department_id" id="department_id" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['dept_id'] ?>" <?= isset($department_id) && $department_id == $dept['dept_id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Service Dropdown -->
        <div class="mb-3">
            <label for="service_name" class="form-label">Service</label>
            <select class="form-select" name="service_name" id="service_name" required>
                <option value="">Select Service</option>
                <?php if (!empty($departmentServices)): ?>
                    <?php foreach ($departmentServices as $service): ?>
                        <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3">
        <label for="service_name" class="form-label">Priority</label>
        <select class="form-select" name="priority" id="service_name" required>
        <option value="">Select Priority</option>
        <?php foreach ($priorities as $priorityOption): ?>
            <option value="<?= htmlspecialchars($priorityOption) ?>">
                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $priorityOption))) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <button type="submit" class="btn btn-primary w-100">Add to Queue</button>
    </form>
  </div>

  <!-- JavaScript to Update Service Dropdown -->
  <script>
    const departmentSelect = document.getElementById('department_id');
    departmentSelect.addEventListener('change', function () {
        const selectedDept = this.value;
        window.location.href = `add_patient_q.php?department_id=${selectedDept}`;
    });
  </script>

</body>
</html>
