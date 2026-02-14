<?php
require __DIR__ . '/../lib/bootstrap.php';

use Quiz\Lib\Database;
use Quiz\Lib\Response;

$config = require __DIR__ . '/../config/config.php';
$pdo = Database::pdo();

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

set_exception_handler(function (Throwable $e): void {
    $code = $e->getCode();
    $status = (is_int($code) && $code >= 400 && $code < 600) ? $code : 500;
    Response::json(['error' => $e->getMessage()], $status);
});
