<?php require __DIR__ . '/../lib/bootstrap.php'; $studentId=(int)($_SESSION['user_id'] ?? 0); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Quiz</title>
  <link rel="stylesheet" href="../assets/css/main.css" />
</head>
<body>
  <div class="container">
    <button class="btn" onclick="toggleTheme()">Theme</button>
    <div class="card">
      <h2>TESTGA ULANISH</h2>
      <button id="joinBtn" class="btn">Join Waiting Room</button>
      <p id="status"></p>
    </div>
    <div class="card">
      <div>Question: <span id="questionNo">0</span></div>
      <div class="timer" id="timer">--</div>
      <div class="answer-grid">
        <button class="answer-card" data-option="A">A</button>
        <button class="answer-card" data-option="B">B</button>
        <button class="answer-card" data-option="C">C</button>
        <button class="answer-card" data-option="D">D</button>
      </div>
    </div>
  </div>
  <script src="../assets/js/theme.js"></script>
  <script>window.CURRENT_STUDENT_ID = <?= $studentId ?>;</script>
  <script src="../assets/js/student.js"></script>
</body>
</html>
