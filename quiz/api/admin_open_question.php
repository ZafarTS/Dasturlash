<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\QuizEngine;
use Quiz\Lib\Response;

Auth::requireAdmin();
$input = read_json_body();
$engine = new QuizEngine($pdo, $config);

$response = $engine->openQuestion((int)$input['session_id'], (int)$input['question_order']);
Response::json($response);
