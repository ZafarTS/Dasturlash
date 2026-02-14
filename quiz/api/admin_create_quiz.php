<?php
require __DIR__ . '/common.php';

use Quiz\Lib\Auth;
use Quiz\Lib\Response;

$admin = Auth::requireAdmin();
$input = read_json_body();

$stmt = $pdo->prepare('INSERT INTO quiz_templates (title, description, semester_code, created_by, is_published) VALUES (?, ?, ?, ?, 0)');
$stmt->execute([
    trim((string)($input['title'] ?? 'Untitled quiz')),
    $input['description'] ?? null,
    trim((string)($input['semester_code'] ?? 'N/A')),
    $admin['id'],
]);

Response::json(['quiz_id' => (int)$pdo->lastInsertId()]);
