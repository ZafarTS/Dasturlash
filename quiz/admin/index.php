<?php require __DIR__ . '/../lib/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Quiz Panel</title>
  <link rel="stylesheet" href="../assets/css/main.css" />
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <h2>Quiz Admin</h2>
    <button class="btn" onclick="toggleTheme()">Theme</button>
    <button class="btn" id="createQuizBtn">Create Quiz</button>
    <button class="btn" id="addQuestionBtn">Add Question</button>
    <button class="btn" id="startSessionBtn">Start Session</button>
    <button class="btn" id="openNextBtn">Open Next</button>
    <button class="btn" id="pauseBtn">Pause</button>
    <button class="btn" id="resumeBtn">Resume</button>
    <button class="btn" id="finishBtn">Finish</button>
  </aside>
  <main class="content">
    <section class="card">
      <h3>Quiz Builder (HTML editor fields)</h3>
      <input id="quizTitle" placeholder="Quiz title" />
      <input id="semesterCode" placeholder="Semester code" />
      <textarea id="quizDesc" placeholder="Description"></textarea>
      <hr />
      <input id="qOrder" type="number" placeholder="Question order" />
      <textarea id="qHtml" placeholder="Question HTML"></textarea>
      <textarea id="aHtml" placeholder="Answer A HTML"></textarea>
      <textarea id="bHtml" placeholder="Answer B HTML"></textarea>
      <textarea id="cHtml" placeholder="Answer C HTML"></textarea>
      <textarea id="dHtml" placeholder="Answer D HTML"></textarea>
      <input id="correctOption" placeholder="Correct option (A/B/C/D)" />
      <input id="timeLimit" type="number" placeholder="Time limit seconds" />
    </section>

    <section class="card" id="fullscreenArea">
      <h2>Live Screen</h2>
      <div>Session: <span id="sessionId">-</span></div>
      <div>Question #: <span id="liveQNo">-</span></div>
      <div class="timer" id="liveTimer">--</div>
      <div id="questionHtml"></div>
      <div id="answersHtml"></div>
    </section>

    <section class="card">
      <h3>Live Ranking</h3>
      <table id="rankingTable"></table>
    </section>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
