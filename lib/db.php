<?php
$config = require __DIR__ . '/../config/config.php';

function get_db_connection(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo === null) {
        $db = $config['db'];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);

        try {
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    return $pdo;
}
