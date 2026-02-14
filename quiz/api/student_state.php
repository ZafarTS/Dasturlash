<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

$student = Auth::requireStudent();
$sessionId = (int)($_GET['session_id'] ?? 0);

$sessionStmt = $pdo->prepare('SELECT status, current_question_order FROM quiz_sessions WHERE id = ?');
$sessionStmt->execute([$sessionId]);
$session = $sessionStmt->fetch();

if (!$session) {
    Response::json(['error' => 'Session not found'], 404);
}

$qStmt = $pdo->prepare(
    'SELECT sq.closes_at_ms, sq.status
     FROM quiz_session_questions sq
     WHERE sq.session_id = ? AND sq.question_order = ? LIMIT 1'
);
$qStmt->execute([$sessionId, (int)$session['current_question_order']]);
$question = $qStmt->fetch() ?: ['status' => 'pending', 'closes_at_ms' => null];

$ansStmt = $pdo->prepare(
    'SELECT selected_option, score_awarded
     FROM quiz_answers qa
     JOIN quiz_sessions s ON s.id = qa.session_id
     WHERE qa.session_id = ? AND qa.student_id = ? AND qa.question_id = (
        SELECT question_id FROM quiz_session_questions WHERE session_id = ? AND question_order = s.current_question_order LIMIT 1
     ) LIMIT 1'
);
$ansStmt->execute([$sessionId, $student['id'], $sessionId]);
$answer = $ansStmt->fetch();

Response::json([
    'session_status' => $session['status'],
    'question_number' => (int)$session['current_question_order'],
    'question_state' => $question['status'],
    'closes_at_ms' => $question['closes_at_ms'] ? (int)$question['closes_at_ms'] : null,
    'already_answered' => (bool)$answer,
    'selected_option' => $answer['selected_option'] ?? null,
]);
