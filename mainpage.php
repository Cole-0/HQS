<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Departments</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f4f8;
      padding: 40px;
      margin: 0;
    }

    h1 {
      text-align: center;
      color: #1d3557;
      margin-bottom: 40px;
    }

    .grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
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
    }

    .card:hover {
      transform: translateY(-5px);
      background-color: #f1faee;
    }

    .card h2 {
      margin: 0;
      font-size: 22px;
      color: #457b9d;
    }

    a {
      text-decoration: none;
    }
  </style>
</head>
<body>
  <h1>Select a Department</h1>
  <div class="grid">
    <a href="queue_bil.php"><div class="card"><h2>Billing</h2></div></a>
    <a href="queue_phar.php"><div class="card"><h2>Pharmacy</h2></div></a>
    <a href="queue_med.php"><div class="card"><h2>Medical</h2></div></a>
    <a href="queue_ult.php"><div class="card"><h2>Ultrasound</h2></div></a>
    <a href="queue_xray.php"><div class="card"><h2>X-Ray</h2></div></a>
    <a href="queue_rehab.php"><div class="card"><h2>Rehabilitation</h2></div></a>
    <a href="queue_dia.php"><div class="card"><h2>Dialysis</h2></div></a>
    <a href="queue_lab.php"><div class="card"><h2>Laboratory</h2></div></a>
  </div>
</body>
</html>
