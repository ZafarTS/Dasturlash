<?php

namespace Quiz\Lib;

use RuntimeException;

class Auth
{
    /**
     * Integrates with main LMS session. Replace keys based on existing auth schema.
     */
    public static function user(): array
    {
        if (empty($_SESSION['user_id'])) {
            throw new RuntimeException('Unauthenticated', 401);
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? 'student',
            'full_name' => $_SESSION['full_name'] ?? 'Student',
        ];
    }

    public static function requireAdmin(): array
    {
        $user = self::user();
        if (!in_array($user['role'], ['admin', 'instructor'], true)) {
            throw new RuntimeException('Forbidden', 403);
        }
        return $user;
    }

    public static function requireStudent(): array
    {
        $user = self::user();
        if ($user['role'] !== 'student') {
            throw new RuntimeException('Forbidden', 403);
        }
        return $user;
    }
}
