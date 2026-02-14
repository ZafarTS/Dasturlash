<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;
use Quiz\Lib\SessionManager;

$student = Auth::requireStudent();
$manager = new SessionManager($pdo);
$session = $manager->currentActiveSession();
if (!$session) {
    Response::json(['message' => 'No active session'], 404);
}

$stmt = $pdo->prepare(
    'INSERT INTO quiz_session_students (session_id, student_id, connection_state)
     VALUES (?, ?, "online")
     ON DUPLICATE KEY UPDATE connection_state = "online", joined_at = joined_at'
);
$stmt->execute([(int)$session['id'], $student['id']]);

Response::json(['session_id' => (int)$session['id'], 'status' => $session['status']]);
