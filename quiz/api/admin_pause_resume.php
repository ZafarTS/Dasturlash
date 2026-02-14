<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

Auth::requireAdmin();
$input = read_json_body();
$status = ($input['action'] ?? '') === 'pause' ? 'paused' : 'running';

$stmt = $pdo->prepare('UPDATE quiz_sessions SET status = ? WHERE id = ?');
$stmt->execute([$status, (int)$input['session_id']]);

Response::json(['status' => $status]);
