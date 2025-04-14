<?php
require('lib/conn.php');

// Fetch all departments
$departmentsSql = "SELECT * FROM departments";
$departmentsStmt = $conn->prepare($departmentsSql);
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll();

// Fetch the queues for each department
$queues = [];
foreach ($departments as $department) {
    // Fetch the current queue for the department (in-progress)
    $currentSql = "SELECT * FROM queues WHERE status = 'in-progress' AND department_id = :dept_id ORDER BY created_at ASC LIMIT 1";
    $currentStmt = $conn->prepare($currentSql);
    $currentStmt->execute(['dept_id' => $department['dept_id']]);
    $currentQueue = $currentStmt->fetch();

    // Fetch upcoming queues for the department (waiting)
    $upcomingSql = "SELECT * FROM queues WHERE status = 'waiting' AND department_id = :dept_id ORDER BY created_at ASC LIMIT 3";
    $upcomingStmt = $conn->prepare($upcomingSql);
    $upcomingStmt->execute(['dept_id' => $department['dept_id']]);
    $upcomingQueues = $upcomingStmt->fetchAll();

    // Store department queues in an array
    $queues[] = [
        'department' => $department,
        'currentQueue' => $currentQueue,
        'upcomingQueues' => $upcomingQueues
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Queue</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 30px;
      text-align: center;
    }

    .queue-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 20px; /* Space between cards */
    }

    .queue-box {
      background: #fff;
      padding: 25px;
      width: 300px; /* Set width for each card */
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }

    h1 {
      color: #1d3557;
    }

    .current {
      font-size: 20px;
      margin: 20px 0 5px;
    }

    .current-number {
      font-size: 60px;
      color: #e63946;
    }

    .details {
      font-size: 16px;
      color: #555;
    }

    .upcoming {
      margin-top: 30px;
    }

    .upcoming span {
      display: inline-block;
      background-color: #f1faee;
      margin: 5px;
      padding: 8px 15px;
      border-radius: 8px;
      color: #457b9d;
    }

    .add-button {
      display: inline-block;
      margin-bottom: 20px;
      padding: 10px 20px;
      background-color: #1d3557;
      color: white;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
    }

    .next-button {
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #457b9d;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .next-button:hover {
      background-color: #1d3557;
    }
  </style>
</head>
<body>

<!-- Container for all queue cards -->
<div class="queue-container">
  <!-- Loop through each department and show the queues -->
  <?php foreach ($queues as $queueData): ?>
    <div class="queue-box">
      <h1>Department: <?= htmlspecialchars($queueData['department']['name']); ?></h1>

      <div class="current">In-Progress</div>  
      <div class="current-number">
        <?php
          echo $queueData['currentQueue']
            ?  str_pad($queueData['currentQueue']['queue_num'], 3, '0', STR_PAD_LEFT)
            : 'None';
        ?>
      </div>

      <div class="details">
        <?php if ($queueData['currentQueue']): ?>
          Service: <?= $queueData['currentQueue']['service_name']; ?> |
          Priority: <strong><?= ucfirst($queueData['currentQueue']['priority']); ?></strong>
        <?php endif; ?>
      </div>

    
      <!-- Display upcoming queues -->
      <div class="upcoming">
        <h3>Upcoming</h3>
        <?php foreach ($queueData['upcomingQueues'] as $q): ?>
          <span>
            <?= str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?>
            (<?= ucfirst($q['priority']); ?>)
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<a href="add_patient_q.php" class="add-button">
  ➕ Add Patient to Queue
</a>

<a href="queue_list.php" class="add-button">
  Queue List
</a>

</body>
</html>

