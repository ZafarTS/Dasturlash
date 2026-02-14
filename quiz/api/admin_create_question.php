<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\HtmlSanitizer;
use Quiz\Lib\Response;

Auth::requireAdmin();
$input = read_json_body();

$stmt = $pdo->prepare(
    'INSERT INTO quiz_questions
    (quiz_id, question_order, question_html, answer_a_html, answer_b_html, answer_c_html, answer_d_html, correct_option, time_limit_seconds)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    (int)$input['quiz_id'],
    (int)$input['question_order'],
    HtmlSanitizer::clean((string)$input['question_html']),
    HtmlSanitizer::clean((string)$input['answer_a_html']),
    HtmlSanitizer::clean((string)$input['answer_b_html']),
    HtmlSanitizer::clean((string)$input['answer_c_html']),
    HtmlSanitizer::clean((string)$input['answer_d_html']),
    strtoupper((string)$input['correct_option']),
    (int)$input['time_limit_seconds'],
]);

Response::json(['question_id' => (int)$pdo->lastInsertId()]);
