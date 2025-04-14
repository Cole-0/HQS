<?php
require('lib/conn.php');

// Get all departments in associative array
$departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$deptMap = [];
foreach ($departments as $dept) {
    $deptMap[$dept['dept_id']] = $dept['name'];
}

// Get all queues
$allQueuesSql = "SELECT * FROM queues ORDER BY created_at ASC";
$allQueues = $conn->query($allQueuesSql)->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Queue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <!-- Font Awesome -->
  <style>
    body {
      background: #f1f1f1;
      padding: 40px;
    }
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
      color: #1d3557;
      margin-bottom: 20px;
    }
    table {
      margin-bottom: 30px;
    }
    .table th {
      background-color: #457b9d;
      color: white;
    }
  </style>
</head>

<body>

<div class="container">
  <h2 class="text-center">Hospital Queue Display</h2>

  <h4>All Queues</h4>
<?php if (count($allQueues) > 0): ?>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Queue Number</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Department</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allQueues as $q): ?>
        <tr>
          <td>Q-<?php echo str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo ucfirst($q['status']); ?></td>
          <td><?php echo ucfirst($q['priority']); ?></td>
          <td><?php echo $deptMap[$q['department_id']] ?? 'Unknown'; ?></td>
          <td><?php echo $q['created_at']; ?></td>
          <td>
            <a href="edit_info.php?qid=<?php echo $q['qid']; ?>" class="btn btn-sm btn-primary">
              <i class="fas fa-forward-step"></i>
            </a>
            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $q['qid']; ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="text-muted">No queues found.</p>
<?php endif; ?>

</div>
<script>
  function confirmDelete(qid) {
    if (confirm("Are you sure you want to delete this queue?")) {
      window.location.href = 'delete_queue.php?qid=' + qid;
    }
  }
</script>

</body>
</html>
