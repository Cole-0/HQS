<?php
require('lib/conn.php');

// Get department_id from URL or default to 1
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 14;

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
        WHEN 'senior' THEN 3
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
    WHEN priority = 'emergency' THEN 0
    WHEN priority IN ('PWD', 'Senior_Citizen', 'pregnant') THEN 1
    ELSE 2
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
    ORDER BY FIELD(priority, 'emergency', 'pwd', 'senior', 'pregnant', 'regular'), 
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
            $conn->prepare("UPDATE queues SET status = 'completed' WHERE qid = :qid")
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
    .announce-button {
  margin-top: 15px;
  padding: 10px 20px;
  background-color: #4CAF50;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}

.announce-button:hover {
  background-color: #3e8e41;
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
      <button onclick="announceCurrentQueue()" class="announce-button">
        Repeat Announcement
      </button>
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
// Global state for this tab
const tabState = {
  id: Math.random().toString(36).substring(2, 15),
  isSpeaking: false,
  lastAnnounceTime: 0
};

// Announcement queue system
const announcementSystem = {
  // Add announcement to queue
  addAnnouncement: function(queueNumber, departmentName) {
    const now = Date.now();
    const message = `Patient with queue number ${queueNumber}, please proceed to the ${departmentName} department.`;
    
    // Throttle rapid clicks (minimum 500ms between adds)
    if (now - tabState.lastAnnounceTime < 500) {
      console.log('Too fast - throttling');
      return;
    }
    tabState.lastAnnounceTime = now;

    const announcement = {
      message: message,
      timestamp: now,
      tabId: tabState.id
    };

    // Get or initialize queue
    let queue = [];
    try {
      queue = JSON.parse(localStorage.getItem('announcementQueue') || '[]');
    } catch (e) {
      console.error('Queue parse error:', e);
    }

    queue.push(announcement);
    localStorage.setItem('announcementQueue', JSON.stringify(queue));
    
    // Start processing if not already running
    this.processQueue();
  },

  // Process the announcement queue
  processQueue: function() {
    // Check if another tab is already processing
    const currentLock = localStorage.getItem('announcementLock');
    if (currentLock && currentLock !== tabState.id) {
      setTimeout(() => this.processQueue(), 300);
      return;
    }

    // Get queue safely
    let queue = [];
    try {
      queue = JSON.parse(localStorage.getItem('announcementQueue') || '[]');
    } catch (e) {
      console.error('Queue parse error:', e);
      return;
    }

    if (queue.length === 0) {
      localStorage.removeItem('announcementLock');
      return;
    }

    // Take lock before processing
    localStorage.setItem('announcementLock', tabState.id);
    const nextAnnouncement = queue.shift();
    localStorage.setItem('announcementQueue', JSON.stringify(queue));

    this.speakAnnouncement(nextAnnouncement.message);
  },

  // Speak an announcement
  speakAnnouncement: function(message) {
    // Cancel any existing speech in this tab
    if (tabState.isSpeaking) {
      window.speechSynthesis.cancel();
    }

    tabState.isSpeaking = true;
    
    const utterance = new SpeechSynthesisUtterance(message);
    utterance.lang = 'en-US';
    utterance.rate = 0.9;

    // Voice selection
    const voices = window.speechSynthesis.getVoices();
    const selectedVoice = voices.find(v => v.name.includes("Zira")) || 
                         voices.find(v => v.name.toLowerCase().includes("female")) ||
                         voices.find(v => v.lang === "en-US");
    
    if (selectedVoice) {
      utterance.voice = selectedVoice;
    }

    // Handle end of speech
    utterance.onend = () => {
      tabState.isSpeaking = false;
      localStorage.removeItem('announcementLock');
      setTimeout(() => this.processQueue(), 300);
    };

    // Handle errors
    utterance.onerror = (event) => {
      console.error('Announcement error:', event);
      tabState.isSpeaking = false;
      localStorage.removeItem('announcementLock');
      setTimeout(() => this.processQueue(), 300);
    };

    window.speechSynthesis.speak(utterance);
  },

  // Initialize the system
  init: function() {
    // Clean up if tab closes
    window.addEventListener('beforeunload', () => {
      if (tabState.isSpeaking) {
        localStorage.removeItem('announcementLock');
      }
    });

    // Start processing any existing queue
    setTimeout(() => this.processQueue(), 1000);
  }
};

// Initialize when voices are loaded
function initializeAnnouncements() {
  if (window.speechSynthesis.getVoices().length === 0) {
    window.speechSynthesis.onvoiceschanged = initializeAnnouncements;
    return;
  }
  
  announcementSystem.init();
  
  <?php if (isset($currentQueue) && $currentQueue): ?>
    // Auto-announce on page load 
    if (window.location.pathname.includes('queue_sw.php')) {
      const announcedKey = `announced_${<?= $currentQueue['queue_num'] ?>}_<?= $departmentId ?>`;
      if (!localStorage.getItem(announcedKey)) {
        setTimeout(() => {
          announcementSystem.addAnnouncement(
            "<?= $currentQueue['queue_num'] ?>", 
            "<?= addslashes($deptName) ?>"
          );
          localStorage.setItem(announcedKey, 'true');
        }, 1500);
      }
    }
  <?php endif; ?>
}

// Button click handler
function announceCurrentQueue() {
  <?php if (isset($currentQueue) && $currentQueue): ?>
    announcementSystem.addAnnouncement(
      "<?= $currentQueue['queue_num'] ?>", 
      "<?= addslashes($deptName) ?>"
    );
  <?php else: ?>
    alert("No current queue to announce!");
  <?php endif; ?>
}

// Start initialization
window.addEventListener('load', () => {
  initializeAnnouncements();
});
</script>
</body>
</html>
