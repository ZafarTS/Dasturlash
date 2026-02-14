<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

$student = Auth::requireStudent();
$input = read_json_body();
$sessionId = (int)$input['session_id'];
$measuredPingMs = max(0, (int)($input['ping_ms'] ?? 0));

$stmt = $pdo->prepare(
    'UPDATE quiz_session_students
     SET last_ping_ms = ?, avg_latency_ms = IF(avg_latency_ms = 0, ?, FLOOR((avg_latency_ms + ?) / 2))
     WHERE session_id = ? AND student_id = ?'
);
$stmt->execute([$measuredPingMs, $measuredPingMs, $measuredPingMs, $sessionId, $student['id']]);

Response::json(['saved' => true]);
