<?php
require('lib/conn.php');

// Get department_id from URL or default to 8
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 2;

// Get department name
$deptName = "Unknown Department";
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE dept_id = :dept_id");
$deptStmt->execute(['dept_id' => $departmentId]);
if ($row = $deptStmt->fetch()) {
    $deptName = $row['name'];
}

// Get current queue
$currentSql = "SELECT * FROM queues WHERE status = 'in-progress' AND department_id = :dept_id ORDER BY created_at ASC LIMIT 1";
$currentStmt = $conn->prepare($currentSql);
$currentStmt->execute(['dept_id' => $departmentId]);
$currentQueue = $currentStmt->fetch();

// Get upcoming queues
$upcomingSql = "SELECT * FROM queues WHERE status = 'waiting' AND department_id = :dept_id ORDER BY created_at ASC LIMIT 3";
$upcomingStmt = $conn->prepare($upcomingSql);
$upcomingStmt->execute(['dept_id' => $departmentId]);
$upcomingQueues = $upcomingStmt->fetchAll();

// Handle the "Next in Queue" button click
if (isset($_POST['next_in_queue'])) {
    // Find the next queue in line
    $nextQueue = $upcomingQueues[0]; 

    if ($nextQueue) {
        // Update the next queue's status to 'in-progress'
        $updateSql = "UPDATE queues SET status = 'in-progress' WHERE qid = :qid";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute(['qid' => $nextQueue['qid']]);

        // Optionally, you may want to update the status of the previous queue to 'finished'
        if ($currentQueue) {
            $finishSql = "UPDATE queues SET status = 'finished' WHERE qid = :qid";
            $finishStmt = $conn->prepare($finishSql);
            $finishStmt->execute(['qid' => $currentQueue['qid']]);
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
        exit;
    }
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

    .queue-box {
      background: #fff;
      padding: 25px;
      margin: auto;
      width: 500px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
  <script>
  // Auto-refresh every 10 seconds (10000 milliseconds)
  setTimeout(() => {
    window.location.reload(); 
  }, 10000); // Change to 5000 for 5 seconds, etc.
</script>
</head>
<body>

<div class="queue-box">
  <h1>Hospital Queue</h1>
  <h2>Department: <?= htmlspecialchars($deptName) ?></h2>

  <!-- Show current queue only if it exists -->
  <?php if ($currentQueue): ?>
    <div class="current">In-Progress</div>  
    <div class="current-number">
      <?= str_pad($currentQueue['queue_num'], 3, '0', STR_PAD_LEFT); ?>
    </div>

    <div class="details">
      Service: <?= htmlspecialchars($currentQueue['service_name']); ?> |
      Priority: <strong><?= ucfirst($currentQueue['priority']); ?></strong>
    </div>
  <?php else: ?>
    <!-- No current queue message -->
    <div class="details">No queues are currently in progress.</div>
  <?php endif; ?>

  <div class="upcoming">
    <h3>Upcoming</h3>
    <?php foreach ($upcomingQueues as $q): ?>
      <span>
        <?= str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?>
        (<?= ucfirst($q['priority']); ?>)
      </span>
    <?php endforeach; ?>
  </div>

  <!-- Show the "Next in Queue" button if there are upcoming queues -->
<?php if (count($upcomingQueues) > 0): ?>
    <form method="post">
        <button type="submit" name="next_in_queue" class="next-button">Next in Queue</button>
    </form>
<?php else: ?>
    <div class="details">No more upcoming queues.</div>
<?php endif; ?><br><br>
 
</div>

</body>
</html>
