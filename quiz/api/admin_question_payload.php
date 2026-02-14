<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

Auth::requireAdmin();
$sessionId = (int)($_GET['session_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.current_question_order, q.question_html, q.answer_a_html, q.answer_b_html, q.answer_c_html, q.answer_d_html, sq.closes_at_ms
     FROM quiz_sessions s
     JOIN quiz_session_questions sq ON sq.session_id = s.id AND sq.question_order = s.current_question_order
     JOIN quiz_questions q ON q.id = sq.question_id
     WHERE s.id = ? LIMIT 1'
);
$stmt->execute([$sessionId]);
$row = $stmt->fetch();
if (!$row) {
    Response::json(['error' => 'No active question'], 404);
}
Response::json($row);
