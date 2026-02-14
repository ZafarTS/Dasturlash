let sessionId = null;
let selected = false;
const statusEl = document.getElementById('status');

async function api(url, method = 'GET', body) {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
    credentials: 'same-origin'
  });
  return res.json();
}

function lockAnswers(state) {
  document.querySelectorAll('.answer-card').forEach(btn => (btn.disabled = state));
}

document.getElementById('joinBtn').addEventListener('click', async () => {
  const data = await api('../api/student_join.php', 'POST', {});
  if (data.session_id) {
    sessionId = data.session_id;
    statusEl.textContent = 'Connected. Waiting for start...';
    startRealtime();
    pollState();
  }
});

async function pollState() {
  if (!sessionId) return;
  const st = await api(`../api/student_state.php?session_id=${sessionId}`);
  document.getElementById('questionNo').textContent = st.question_number || 0;

  if (st.question_state === 'open') {
    if (!st.already_answered) {
      selected = false;
      lockAnswers(false);
    }
    renderTimer(st.closes_at_ms);
  } else {
    lockAnswers(true);
    document.getElementById('timer').textContent = '--';
  }

  if (st.already_answered) {
    selected = true;
    lockAnswers(true);
    document.querySelectorAll('.answer-card').forEach(el => {
      el.classList.toggle('selected', el.dataset.option === st.selected_option);
    });
  }

  setTimeout(pollState, 1000);
}

function renderTimer(closeMs) {
  const diff = Math.max(0, closeMs - Date.now());
  document.getElementById('timer').textContent = (diff / 1000).toFixed(1) + 's';
}

for (const btn of document.querySelectorAll('.answer-card')) {
  btn.addEventListener('click', async () => {
    if (!sessionId || selected) return;
    selected = true;
    lockAnswers(true);
    btn.classList.add('selected');
    await api('../api/student_submit_answer.php', 'POST', {
      session_id: sessionId,
      selected_option: btn.dataset.option,
    });
  });
}

function startRealtime() {
  const ws = new WebSocket(`ws://${location.hostname}:8090`);
  ws.addEventListener('open', () => {
    ws.send(JSON.stringify({ type: 'auth', session_id: sessionId, student_id: window.CURRENT_STUDENT_ID || 0 }));
    setInterval(() => {
      ws.send(JSON.stringify({ type: 'ping', sent_at_ms: Date.now() }));
    }, 5000);
  });
  ws.addEventListener('message', async (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'pong') {
      await api('../api/student_ping.php', 'POST', {
        session_id: sessionId,
        ping_ms: data.rtt_ms,
      });
    }
  });
}
