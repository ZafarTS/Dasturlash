<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\QuizEngine;
use Quiz\Lib\Response;

$student = Auth::requireStudent();
$input = read_json_body();
$engine = new QuizEngine($pdo, $config);

$response = $engine->submitAnswer((int)$input['session_id'], $student['id'], (string)$input['selected_option']);
Response::json($response);
