<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

Auth::requireAdmin();
$input = read_json_body();
$sessionId = (int)$input['session_id'];

$pdo->beginTransaction();
try {
    $meta = $pdo->prepare(
        'SELECT s.id, t.semester_code
         FROM quiz_sessions s
         JOIN quiz_templates t ON t.id = s.quiz_id
         WHERE s.id = ? LIMIT 1'
    );
    $meta->execute([$sessionId]);
    $session = $meta->fetch();
    if (!$session) {
        throw new RuntimeException('Session not found', 404);
    }

    $scores = $pdo->prepare('SELECT student_id, total_score FROM quiz_session_scores WHERE session_id = ?');
    $scores->execute([$sessionId]);

    $upsert = $pdo->prepare(
        'INSERT INTO quiz_semester_scores (semester_code, student_id, cumulative_score, session_count)
         VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
            cumulative_score = cumulative_score + VALUES(cumulative_score),
            session_count = session_count + 1'
    );

    foreach ($scores->fetchAll() as $row) {
        $upsert->execute([$session['semester_code'], $row['student_id'], $row['total_score']]);
    }

    $pdo->prepare('UPDATE quiz_sessions SET status = "finished", finished_at = NOW() WHERE id = ?')->execute([$sessionId]);
    $pdo->commit();

    Response::json(['finished' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
