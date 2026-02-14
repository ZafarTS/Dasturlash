<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

Auth::requireAdmin();
$sessionId = (int)($_GET['session_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT student_id, total_score, correct_count, wrong_count
     FROM quiz_session_scores
     WHERE session_id = ?
     ORDER BY total_score DESC, updated_at ASC'
);
$stmt->execute([$sessionId]);
$rows = $stmt->fetchAll();

Response::json(['rankings' => $rows]);
