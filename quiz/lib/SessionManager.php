<?php

namespace Quiz\Lib;

use PDO;
use RuntimeException;

class SessionManager
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createSession(int $quizId, int $adminId): int
    {
        $this->pdo->beginTransaction();
        try {
            $active = $this->pdo->query("SELECT id FROM quiz_sessions WHERE status IN ('waiting','running','paused') LIMIT 1")->fetch();
            if ($active) {
                throw new RuntimeException('Only one active session is allowed.', 409);
            }

            $stmt = $this->pdo->prepare('INSERT INTO quiz_sessions (quiz_id, started_by, status) VALUES (?, ?, "waiting")');
            $stmt->execute([$quizId, $adminId]);
            $sessionId = (int) $this->pdo->lastInsertId();

            $qStmt = $this->pdo->prepare('SELECT id, question_order FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order');
            $qStmt->execute([$quizId]);
            $questions = $qStmt->fetchAll();

            $linkStmt = $this->pdo->prepare('INSERT INTO quiz_session_questions (session_id, question_id, question_order) VALUES (?, ?, ?)');
            foreach ($questions as $q) {
                $linkStmt->execute([$sessionId, $q['id'], $q['question_order']]);
            }

            $this->pdo->commit();
            return $sessionId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function currentActiveSession(): ?array
    {
        $stmt = $this->pdo->query("SELECT * FROM quiz_sessions WHERE status IN ('waiting','running','paused') ORDER BY id DESC LIMIT 1");
        return $stmt->fetch() ?: null;
    }
}
