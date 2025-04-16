<?php
require('lib/conn.php');

// Fetch all departments
$departmentsStmt = $conn->prepare("SELECT * FROM departments");
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll();

$queues = [];
foreach ($departments as $department) {
    $currentStmt = $conn->prepare("
        SELECT * FROM queues 
        WHERE status = 'in-progress' AND department_id = :dept_id
        ORDER BY 
            CASE priority
                WHEN 'emergency' THEN 1
                WHEN 'PWD' THEN 2
                WHEN 'Senior_Citizen' THEN 3
                WHEN 'pregnant' THEN 4
                ELSE 5
            END,
            CAST(SUBSTRING(queue_num, 5) AS UNSIGNED) ASC
        LIMIT 1
    ");
    $currentStmt->execute(['dept_id' => $department['dept_id']]);
    $currentQueue = $currentStmt->fetch();

    $upcomingSql = "
    SELECT * 
    FROM queues 
    WHERE status = 'waiting' 
    AND department_id = :dept_id 
    ORDER BY 
       CASE 
    WHEN priority = 'emergency' THEN 0
    WHEN priority IN ('PWD', 'Senior_Citizen', 'pregnant') THEN 1
    ELSE 2
END,

        created_at ASC
    ";
      $upcomingStmt = $conn->prepare($upcomingSql);
    $upcomingStmt->execute(['dept_id' => $department['dept_id']]);
    $upcomingQueues = $upcomingStmt->fetchAll();

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
  <title>Now Serving - Hospital Queue</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }

    .display-container {
      padding: 40px 20px;
      width: 100%;
      text-align: center;
    }

    h1 {
      font-size: 4rem;
      font-weight: bold;
      color: #1d3557;
      margin-bottom: 50px;
    }

    .cards-wrapper {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 40px;
    }

    .queue-card {
      background-color: white;
      border-radius: 24px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      width: 500px;
      padding: 50px 40px;
      text-align: center;
      transition: transform 0.3s;
    }

    .queue-card:hover {
      transform: translateY(-5px);
    }

    .queue-label {
      font-size: 2rem;
      color: #1d3557;
      font-weight: 600;
      margin-bottom: 12px;
    }

    .queue-value {
      font-size: 3.2rem;
      color: #e63946;
      font-weight: bold;
      margin-bottom: 25px;
      word-wrap: break-word;
    }

    .department-name {
      font-size: 2.8rem;
      color: #1d3557;
      font-weight: 700;
      margin-bottom: 30px;
    }

    .priority {
      font-size: 2rem;
      color: #457b9d;
      font-weight: 600;
      margin-bottom: 25px;
    }

    .upcoming-section {
      margin-top: 25px;
    }

    .upcoming-section h3 {
      font-size: 1.6rem;
      color: #1d3557;
      margin-bottom: 10px;
    }

    .upcoming-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .upcoming-item {
      background-color: #f1faee;
      color: #457b9d;
      font-size: 1.2rem;
      padding: 8px 14px;
      border-radius: 8px;
      font-weight: 600;
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2.5rem;
      }
      .queue-card {
        width: 90%;
        padding: 40px 20px;
      }
      .department-name {
        font-size: 2.2rem;
      }
      .queue-value {
        font-size: 2.4rem;
      }
      .priority {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="display-container">
    <h1>Now Serving</h1>
    <div class="cards-wrapper">
      <?php foreach ($queues as $queueData): ?>
        <div class="queue-card">
          <div class="department-name">
            <?= strtoupper(htmlspecialchars($queueData['department']['name'])); ?>
          </div>

          <div class="queue-label">Queue Number</div>
          <div class="queue-value">
            <?= $queueData['currentQueue']
              ? htmlspecialchars($queueData['currentQueue']['queue_num'])
              : 'None'; ?>
          </div>

          <div class="queue-label">Priority</div>
          <div class="priority">
            <?= $queueData['currentQueue']
              ? ucfirst($queueData['currentQueue']['priority'])
              : '—'; ?>
          </div>

          <div class="upcoming-section">
            <h3>Upcoming Queues</h3>
            <div class="upcoming-list">
              <?php if (count($queueData['upcomingQueues']) == 0): ?>
                <div class="upcoming-item">None</div>
              <?php else: ?>
                <?php foreach ($queueData['upcomingQueues'] as $q): ?>
                  <div class="upcoming-item">
                    <?= htmlspecialchars($q['queue_num']) ?> (<?= ucfirst($q['priority']) ?>)
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    // Auto-refresh every 10 seconds
    setInterval(function () {
      location.reload();
    }, 10000);
  </script>
</body>
</html>
