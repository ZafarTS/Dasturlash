<?php
require_once __DIR__ . '/db.php';

session_start();

function find_user_by_user_id(string $userId): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function authenticate(string $userId, string $password): bool
{
    $user = find_user_by_user_id($userId);
    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'user_id' => $user['user_id'],
        'role' => $user['role'],
    ];

    return true;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    $user = find_user_by_user_id($_SESSION['user']['user_id']);
    return $user ?: null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /index.php');
        exit;
    }
}

function require_role(string $role): void
{
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
