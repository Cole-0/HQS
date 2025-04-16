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
    $allUpcomingQueues = $upcomingStmt->fetchAll();

    $queues[] = [
        'department' => $department,
        'currentQueue' => $currentQueue,
        'upcomingQueues' => array_slice($allUpcomingQueues, 0, 3),
        'extraQueues' => array_slice($allUpcomingQueues, 3)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Queue</title>
  <style>
    * {
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', sans-serif;
  margin: 0;
  background: #f1f5f9;
  display: flex;
  min-height: 100vh;
}

/* Hamburger Button */
.hamburger {
  display: none;
  position: fixed;
  top: 15px;
  left: 15px;
  font-size: 24px;
  background: #1d3557;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  z-index: 1000;
  cursor: pointer;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background-color: #1d3557;
  color: white;
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
}

.sidebar h2 {
  font-size: 1.6rem;
  margin-bottom: 40px;
  text-align: center;
  font-weight: bold;
  letter-spacing: 1px;
}

.nav-link {
  margin: 12px 0;
  text-decoration: none;
  color: white;
  font-size: 18px;
  font-weight: bold;
  display: flex;
  align-items: center;
  padding: 10px 15px;
  border-radius: 8px;
  transition: background 0.3s ease;
  white-space: nowrap;
}

.nav-link:hover {
  background-color: #457b9d;
}

.nav-link::before {
  margin-right: 8px;
  font-size: 1.1rem;
}

.nav-link:first-of-type::before {
  content: "➕";
}

.nav-link:nth-of-type(2)::before {
  content: "📋";
}

/* Main content */
.main-content {
  flex: 1;
  padding: 40px;
  transition: margin-left 0.3s ease;
}

h1 {
  font-family: Arial;
  font-weight: 900;
  color: #1d3557;
  text-align: center;
  margin-bottom: 30px;
}

.department-box {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  margin-bottom: 30px;
  padding: 20px 25px;
  transition: box-shadow 0.3s;
}

.department-box:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.department-header {
  font-size: 1.2em;
  font-weight: bold;
  color: #1d3557;
  margin-bottom: 15px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
}

.queue-info {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 15px;
}

.queue-item {
  flex: 1 1 220px;
  background: #f1faee;
  padding: 14px;
  border-radius: 8px;
  color: #457b9d;
  text-align: center;
  transition: background 0.2s ease;
}

.queue-item:hover {
  background: #e8f3f1;
}

.current-queue {
  font-size: 28px;
  color: #e63946;
  font-weight: bold;
}

.btn-toggle {
  background: none;
  border: none;
  color: #1d3557;
  cursor: pointer;
  font-size: 0.85rem;
  margin-top: 10px;
  text-decoration: underline;
  padding: 5px;
}

.extra-queues {
  margin-top: 10px;
}

.queue-list span {
  display: inline-block;
  margin: 4px;
  padding: 6px 12px;
  background-color: #f1faee;
  border-radius: 6px;
  color: #457b9d;
  font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
  .hamburger {
    display: block;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    height: 100%;
    width: 280px;
    transition: left 0.3s ease;
    z-index: 999;
  }

  .sidebar.active {
    left: 0;
  }

  .main-content {
    padding: 20px;
    margin-left: 0;
  }

  .main-content.sidebar-open {
    margin-left: 280px;
  }

  .queue-info {
    flex-direction: column;
    align-items: center;
  }
}
  </style>
</head>
<body>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<div class="sidebar">
  <h2>HOSPITAL</h2>
  <a href="add_patient_q.php" class="nav-link"> PATIENT TO QUEUE</a>
  <a href="queue_list.php" class="nav-link"> QUEUE HISTORY</a>
  <a href="queue_display_user.php" class="nav-link" target="_blank">📢 NOW SERVING</a>
</div>


<!-- Main content -->
<div class="main-content">
  <h1>Hospital Queue Status</h1>

  <?php foreach ($queues as $queueData): ?>
    <div class="department-box">
      <div class="department-header">
        <?= htmlspecialchars($queueData['department']['name']); ?>
      </div>

      <div class="queue-info">
        <div class="queue-item">
          <div>Current Number</div>
          <div class="current-queue">
            <?= $queueData['currentQueue']
              ? htmlspecialchars($queueData['currentQueue']['queue_num'])
              : 'None'; ?>
          </div>
        </div>

        <div class="queue-item">
          <div style="font-weight: bold;">Priority</div>
          <div><span style="color: black;">
            <?= $queueData['currentQueue']
              ? ucfirst($queueData['currentQueue']['priority'])
              : '—'; ?>
          </span></div>
        </div>

        <div class="queue-item">
          <div>Upcoming</div>
          <div class="queue-list">
            <?php if (count($queueData['upcomingQueues']) <= 0): ?>
              <span>No upcoming queues.</span>
            <?php else: ?>
              <?php foreach ($queueData['upcomingQueues'] as $q): ?>
                <span><?= htmlspecialchars($q['queue_num']); ?> (<?= ucfirst($q['priority']); ?>)</span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php if (count($queueData['extraQueues']) > 0): ?>
            <button class="btn-toggle" data-dept="<?= $queueData['department']['dept_id']; ?>">Show More</button>
            <div class="extra-queues queue-list" id="extra-<?= $queueData['department']['dept_id']; ?>" style="display: none;">
              <?php foreach ($queueData['extraQueues'] as $q): ?>
                <span><?= htmlspecialchars($q['queue_num']); ?> (<?= ucfirst($q['priority']); ?>)</span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Scripts -->
<script>
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    sidebar.classList.toggle('active');
    main.classList.toggle('sidebar-open');
  }

  document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
      const deptId = this.getAttribute('data-dept');
      const extraContainer = document.getElementById('extra-' + deptId);
      const isShown = extraContainer.style.display === 'block';
      extraContainer.style.display = isShown ? 'none' : 'block';
      this.textContent = isShown ? 'Show More' : 'Show Less';
    });
  });
</script>

</body>
</html>
