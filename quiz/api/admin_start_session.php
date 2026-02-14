<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;
use Quiz\Lib\SessionManager;

$admin = Auth::requireAdmin();
$input = read_json_body();
$manager = new SessionManager($pdo);
$sessionId = $manager->createSession((int)$input['quiz_id'], $admin['id']);

Response::json(['session_id' => $sessionId]);
