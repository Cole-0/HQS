<?php
session_start();
require('lib/conn.php');

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$departmentId = $_SESSION['dept_id'] ?? null;

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

    .nav-link i.icon {
      margin-right: 10px;
      font-size: 20px;
      width: 25px;
      text-align: center;
    }

    .nav-link span {
      flex: 1;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      transition: margin-left 0.3s ease;
      margin-top: 20px;
    }

    h1 {
      font-family: Arial;
      font-weight: 900;
      color: #1d3557;
      text-align: center;
      margin-bottom: 30px;
      margin-top: 0;
      padding-top: 20px;
    }

    .grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }

    .card {
      background: #fff;
      padding: 30px;
      width: 250px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      text-align: center;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.3s ease;
      margin-bottom: 20px;
    }

    .card:hover {
      transform: translateY(-5px);
      background-color: #f1faee;
    }

    .card h2 {
      margin: 15px 0 0 0;
      font-size: 22px;
      color: #457b9d;
    }

    .card i {
      font-size: 40px;
      color: #457b9d;
      margin-bottom: 15px;
      display: block;
    }

    .fab {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: #1d3557;
      color: white;
      font-size: 24px;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      border: none;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: background-color 0.3s ease;
    }

    .fab:hover {
      background-color: #e63946;
    }

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
        margin-top: 60px;
      }

      .main-content.sidebar-open {
        margin-left: 280px;
      }

      .card {
        width: calc(50% - 20px);
      }
    }

    .user-info {
      text-align: right;
      margin-bottom: 10px;
      padding: 8px 15px;
      background-color: #e63946;
      color: white;
      border-radius: 5px;
      display: inline-block;
      float: right;
      font-weight: bold;
    }
  </style>
</head>
<body>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<div class="sidebar">
  <h2>HOSPITAL</h2>
  <a href="add_patient_q.php?role=<?php echo htmlspecialchars($role); ?>" class="nav-link">
    <i class="fas fa-user-plus icon"></i>
    <span>PATIENT TO QUEUE</span>
  </a>
  <a href="queue_list.php" class="nav-link">
    <i class="fas fa-list-alt icon"></i>
    <span>QUEUE HISTORY</span>
  </a>
  <a href="mainpage.php" class="nav-link">
    <i class="fas fa-stream icon"></i>
    <span>DEPARTMENT QUEUE</span>
  </a>
  <?php if ($role === 'Admin'): ?>
    <a href="register.php" class="nav-link">
      <i class="fas fa-user-cog icon"></i>
      <span>ADD USER</span>
    </a>
  <?php endif; ?>
  <a href="queue_display_user.php" class="nav-link" target="_blank">
    <i class="fas fa-bullhorn icon"></i>
    <span>NOW SERVING</span>
  </a>
  <a href="logout.php" class="nav-link" style="margin-top: auto;">
    <i class="fas fa-sign-out-alt icon"></i>
    <span>LOGOUT</span>
  </a>
</div>

<!-- Main content -->
<div class="main-content">
  <div class="user-info">
    Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
  </div>
  
  <h1>Select a Department</h1>
  <div class="grid">
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 1): ?>
      <a href="queue_bil.php">
        <div class="card">
          <i class="fas fa-hospital"></i>
          <h2>Billing</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 2): ?>
      <a href="queue_phar.php">
        <div class="card">
          <i class="fas fa-pills"></i>
          <h2>Pharmacy</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 3): ?>
      <a href="queue_med.php">
        <div class="card">
          <i class="fas fa-stethoscope"></i>
          <h2>Medical</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 4): ?>
      <a href="queue_ult.php">
        <div class="card">
          <i class="fas fa-syringe"></i>
          <h2>Ultrasound</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 5): ?>
      <a href="queue_xray.php">
        <div class="card">
          <i class="fas fa-x-ray"></i>
          <h2>X-Ray</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 6): ?>
      <a href="queue_rehab.php">
        <div class="card">
          <i class="fas fa-wheelchair"></i>
          <h2>Rehabilitation</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 7): ?>
      <a href="queue_dia.php">
        <div class="card">
          <i class="fas fa-heartbeat"></i>
          <h2>Dialysis</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 8): ?>
      <a href="queue_lab.php">
        <div class="card">
          <i class="fas fa-flask"></i>
          <h2>Laboratory</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information'): ?>
      <a href="queue_er.php">
        <div class="card">
          <i class="fas fa-ambulance"></i>
          <h2>Emergency Room</h2>
        </div>
      </a>
      <a href="queue_sw.php">
        <div class="card">
          <i class="fas fa-user-friends"></i>
          <h2>Social Worker</h2>
        </div>
      </a>
    <?php endif; ?>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    sidebar.classList.toggle('active');
    main.classList.toggle('sidebar-open');
  }
</script>

</body>
</html>