let quizId = null;
let sessionId = null;
let currentOrder = 0;

async function api(url, method = 'GET', body) {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
    credentials: 'same-origin',
  });
  return res.json();
}

document.getElementById('createQuizBtn').onclick = async () => {
  const data = await api('../api/admin_create_quiz.php', 'POST', {
    title: document.getElementById('quizTitle').value,
    description: document.getElementById('quizDesc').value,
    semester_code: document.getElementById('semesterCode').value,
  });
  quizId = data.quiz_id;
};

document.getElementById('addQuestionBtn').onclick = async () => {
  await api('../api/admin_create_question.php', 'POST', {
    quiz_id: quizId,
    question_order: Number(document.getElementById('qOrder').value),
    question_html: document.getElementById('qHtml').value,
    answer_a_html: document.getElementById('aHtml').value,
    answer_b_html: document.getElementById('bHtml').value,
    answer_c_html: document.getElementById('cHtml').value,
    answer_d_html: document.getElementById('dHtml').value,
    correct_option: document.getElementById('correctOption').value,
    time_limit_seconds: Number(document.getElementById('timeLimit').value),
  });
};

document.getElementById('startSessionBtn').onclick = async () => {
  const data = await api('../api/admin_start_session.php', 'POST', { quiz_id: quizId });
  sessionId = data.session_id;
  document.getElementById('sessionId').textContent = sessionId;
};

document.getElementById('openNextBtn').onclick = async () => {
  currentOrder += 1;
  await api('../api/admin_open_question.php', 'POST', { session_id: sessionId, question_order: currentOrder });
  document.getElementById('liveQNo').textContent = currentOrder;
  loadQuestion();
  refreshRanking();
};

document.getElementById('pauseBtn').onclick = () => api('../api/admin_pause_resume.php', 'POST', { session_id: sessionId, action: 'pause' });
document.getElementById('resumeBtn').onclick = () => api('../api/admin_pause_resume.php', 'POST', { session_id: sessionId, action: 'resume' });
document.getElementById('finishBtn').onclick = () => api('../api/admin_finish_session.php', 'POST', { session_id: sessionId });

async function loadQuestion() {
  const q = await api(`../api/admin_question_payload.php?session_id=${sessionId}`);
  document.getElementById('questionHtml').innerHTML = q.question_html || '';
  document.getElementById('answersHtml').innerHTML = `
    <div class="card">A: ${q.answer_a_html}</div>
    <div class="card">B: ${q.answer_b_html}</div>
    <div class="card">C: ${q.answer_c_html}</div>
    <div class="card">D: ${q.answer_d_html}</div>`;

  const tick = () => {
    const diff = Math.max(0, (q.closes_at_ms || 0) - Date.now());
    document.getElementById('liveTimer').textContent = (diff / 1000).toFixed(1) + 's';
    if (diff > 0) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}

async function refreshRanking() {
  if (!sessionId) return;
  const data = await api(`../api/admin_leaderboard.php?session_id=${sessionId}`);
  const rows = (data.rankings || []).map((r, i) => `<tr><td>${i + 1}</td><td>${r.student_id}</td><td>${r.total_score}</td></tr>`).join('');
  document.getElementById('rankingTable').innerHTML = `<tr><th>#</th><th>Student</th><th>Score</th></tr>${rows}`;
  setTimeout(refreshRanking, 1000);
}
