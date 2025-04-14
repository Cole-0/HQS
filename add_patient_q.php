<?php
require('lib/conn.php');

// Fetch departments
$departments = $conn->query("SELECT * FROM departments")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = $_POST['service_name']; // Manual input
    $department_id = $_POST['department_id'];
    $priority = $_POST['priority'];

    // Department ID to prefix mapping
    $departmentPrefixes = [
        1 => 'BIL-',    // Billing
        2 => 'PHAR-',   // Pharmacy
        3 => 'MED-',   // Medical Records
        4 => 'ULT-',  // Ultra-sound
        5 => 'XR-', // X-ray
        6 => 'REH-', // Rehabilitation
        7 => 'DIA-', // Dialysis
        8 => 'LAB-', // Laboratory
        9 => 'ADM-', // Admitting
        // Extend this as needed
    ];

    // Get prefix or use default
    $prefix = $departmentPrefixes[$department_id] ?? 'GEN';

    // Fetch last queue for department
    $stmt = $conn->prepare("SELECT queue_num FROM queues WHERE department_id = :department_id ORDER BY qid DESC LIMIT 1");
    $stmt->execute([':department_id' => $department_id]);
    $lastQueue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Extract numeric part
    if ($lastQueue && preg_match('/\d+$/', $lastQueue['queue_num'], $matches)) {
        $lastNum = intval($matches[0]);
    } else {
        $lastNum = 0;
    }

    // Pad to 3 digits
    $numericPart = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    $queue_num = $prefix . $numericPart;

    try {
        $sql = "INSERT INTO queues (queue_num, service_name, service_id, department_id, status, priority, created_at)
                VALUES (:queue_num, :service_name, NULL, :department_id, 'waiting', :priority, NOW())";
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
  </style>
</head>
<body>

  <div class="form-container">
    <h3 class="mb-4 text-center">Add Patient to Queue</h3>
    <form method="POST" action="add_patient_q.php">

    <div class="mb-3">
            <label for="department_id" class="form-label">Department</label>
            <select class="form-select" name="department_id" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['dept_id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="service_name" class="form-label">Service</label>
            <input type="text" class="form-control" name="service_name" required>
        </div>


        <div class="mb-3">
            <label for="priority" class="form-label">Priority</label>
            <select class="form-select" name="priority" required>
                <option value="NORMAL">Normal</option>
                <option value="URGENT">Urgent</option>
                <option value="EMERGENCY">Emergency</option>
                <option value="PWD">PWD</option>
                <option value="SENIOR_CITIZEN">Senior Citizen</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Add to Queue</button>
    </form>
  </div>

</body>
</html>