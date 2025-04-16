<?php
require('lib/conn.php');

// Get department_id from URL or default to 1
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 6;

// Fetch department name
$deptName = "Unknown Department";
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE dept_id = :dept_id");
$deptStmt->execute(['dept_id' => $departmentId]);
if ($row = $deptStmt->fetch()) {
    $deptName = $row['name'];
}

// Get current queue (top prioritized and earliest number)
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
$currentStmt->execute(['dept_id' => $departmentId]);
$currentQueue = $currentStmt->fetch();

// Get upcoming queues (priority based)
$upcomingSql = "
SELECT * 
FROM queues 
WHERE status = 'waiting' 
AND department_id = :dept_id 
ORDER BY 
    CASE 
        WHEN priority IN ('emergency', 'PWD', 'Senior_Citizen', 'pregnant') THEN 0 
        ELSE 1 
    END,
    created_at ASC

";
$upcomingStmt = $conn->prepare($upcomingSql);
$upcomingStmt->execute(['dept_id' => $departmentId]);
$allUpcomingQueues = $upcomingStmt->fetchAll();

// Get extra queues beyond top 3 (for optional expand)
$extraStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'waiting' 
    AND department_id = :dept_id 
    ORDER BY FIELD(priority, 'emergency', 'pwd', 'Senior_Citizen', 'pregnant', 'regular'), 
             CAST(SUBSTRING(queue_num, 5) AS UNSIGNED) ASC 
    LIMIT 18446744073709551615 OFFSET 3
");
$extraStmt->execute(['dept_id' => $departmentId]);
$extraQueues = $extraStmt->fetchAll();

// Handle 'Next in Queue'
if (isset($_POST['next_in_queue'])) {
    $nextQueue = $allUpcomingQueues[0] ?? null;

    if ($nextQueue) {
        // Mark current queue as finished
        if ($currentQueue) {
            $conn->prepare("UPDATE queues SET status = 'finished' WHERE qid = :qid")
                 ->execute(['qid' => $currentQueue['qid']]);
        }

        // Promote next to in-progress
        $conn->prepare("UPDATE queues SET status = 'in-progress' WHERE qid = :qid")
             ->execute(['qid' => $nextQueue['qid']]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Queue - <?= htmlspecialchars($deptName) ?></title>
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

    .upcoming span, .extra span {
      display: inline-block;
      background-color: #f1faee;
      margin: 5px;
      padding: 8px 15px;
      border-radius: 8px;
      color: #457b9d;
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

    .toggle-extra {
      margin-top: 15px;
      color: #1d3557;
      cursor: pointer;
      font-size: 14px;
      display: inline-block;
    }
  </style>
  <script>
    // Auto-refresh every 10 seconds
    setTimeout(() => {
      window.location.reload();
    }, 10000);

    function toggleExtra() {
      const extra = document.getElementById("extra-queues");
      const toggle = document.getElementById("toggle-btn");
      if (extra.style.display === "none") {
        extra.style.display = "block";
        toggle.innerText = "Show less ▲";
      } else {
        extra.style.display = "none";
        toggle.innerText = "Show more ▼";
      }
    }
  </script>
</head>
<body>
  <div class="queue-box">
    <h1>Hospital Queue</h1>
    <h2>Department: <?= htmlspecialchars($deptName) ?></h2>

    <?php if ($currentQueue): ?>
      <button onclick="repeatAnnouncement()" class="next-button" style="background-color: #e76f51; margin-top: 10px;">🔊 Repeat Announcement</button>

      <div class="current">In-Progress</div>
      <div class="current-number"><?= str_pad($currentQueue['queue_num'], 3, '0', STR_PAD_LEFT); ?></div>
      <div class="details">
        Service: <?= htmlspecialchars($currentQueue['service_name']); ?> |
        Priority: <strong><?= ucfirst($currentQueue['priority']); ?></strong>
      </div>
    <?php else: ?>
      <div class="details">No queues are currently in progress.</div>
    <?php endif; ?>

    <div class="upcoming">
      <h3>Upcoming</h3>
      <?php foreach ($allUpcomingQueues as $q): ?>
        <span>
          <?= str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?>
          (<?= ucfirst($q['priority']); ?>)
        </span>
      <?php endforeach; ?>
    </div>

    <!-- Hidden extra queues -->
    <?php if (count($extraQueues) > 0): ?>
      <div id="extra-queues" class="extra" style="display:none;">
        <?php foreach ($extraQueues as $q): ?>
          <span>
            <?= str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?>
            (<?= ucfirst($q['priority']); ?>)
          </span>
        <?php endforeach; ?>
      </div>
      <div id="toggle-btn" class="toggle-extra" onclick="toggleExtra()">Show more ▼</div>
    <?php endif; ?>

    <?php if (count($allUpcomingQueues) > 0): ?>
      <form method="post">
        <button type="submit" name="next_in_queue" class="next-button">Next in Queue</button>
      </form>
    <?php else: ?>
      <div class="details">No upcoming queues.</div>
    <?php endif; ?>
  </div>
  <script>
  function announceQueue(queueNumber, departmentName) {
    const message = `Patient with the queue number ${queueNumber}, please proceed to the ${departmentName} department.`;

    // Stop any existing speech 
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(message);
    utterance.lang = 'en-US';
    utterance.rate = 0.9;

    // Function to handle selecting voice and speaking
    function setVoiceAndSpeak() {
      const voices = window.speechSynthesis.getVoices();

      let selectedVoice = voices.find(v => v.name.includes("Zira")) || 
                          voices.find(v => v.name.toLowerCase().includes("female")) ||
                          voices.find(v => v.lang === "en-US");

      if (selectedVoice) {
        utterance.voice = selectedVoice;
      }

      // Delay to ensure the message is spoken fully without cutting off
      setTimeout(() => {
        window.speechSynthesis.speak(utterance);
      }, 100);
    }

    // Check if voices are loaded and then execute
    if (speechSynthesis.getVoices().length === 0) {
      window.speechSynthesis.onvoiceschanged = setVoiceAndSpeak;
    } else {
      setVoiceAndSpeak();
    }
  }

  function initAnnouncement() {
    <?php if ($currentQueue): ?>
      const currentQueueNumber = "<?= $currentQueue['queue_num'] ?>";
      const departmentName = "<?= addslashes($deptName) ?>";
      const announcedKey = `announced_${currentQueueNumber}_<?= $departmentId ?>`;

      if (!localStorage.getItem(announcedKey)) {
        setTimeout(() => {
          announceQueue(currentQueueNumber, departmentName);
          localStorage.setItem(announcedKey, 'true');
        }, 1000); // Slight delay to ensure page load before speaking
      }
    <?php endif; ?>
  }
  function repeatAnnouncement() {
  const currentQueueNumber = "<?= $currentQueue['queue_num'] ?? '' ?>";
  const departmentName = "<?= addslashes($deptName) ?>";

  if (currentQueueNumber) {
    announceQueue(currentQueueNumber, departmentName);
  }
}

  // Wait for the page to load before running the announcement function
  window.addEventListener('load', () => {
    setTimeout(initAnnouncement, 500);  // Ensure the page is fully loaded
  });
</script>

</body>
</html>
