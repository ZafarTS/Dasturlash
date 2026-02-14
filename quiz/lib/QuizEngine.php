<?php

namespace Quiz\Lib;

use PDO;
use RuntimeException;

class QuizEngine
{
    public function __construct(private PDO $pdo, private array $config)
    {
    }

    public function openQuestion(int $sessionId, int $questionOrder): array
    {
        $this->pdo->beginTransaction();
        try {
            $nowMs = (int) floor(microtime(true) * 1000);
            $this->pdo->prepare('UPDATE quiz_session_questions SET status = "closed" WHERE session_id = ? AND status = "open"')
                ->execute([$sessionId]);

            $stmt = $this->pdo->prepare(
                'SELECT sq.id, sq.question_id, q.time_limit_seconds
                 FROM quiz_session_questions sq
                 JOIN quiz_questions q ON q.id = sq.question_id
                 WHERE sq.session_id = ? AND sq.question_order = ? LIMIT 1'
            );
            $stmt->execute([$sessionId, $questionOrder]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Question not found', 404);
            }

            $closeMs = $nowMs + ((int) $row['time_limit_seconds'] * 1000);
            $up = $this->pdo->prepare('UPDATE quiz_session_questions SET status="open", opens_at_ms=?, closes_at_ms=? WHERE id=?');
            $up->execute([$nowMs, $closeMs, $row['id']]);

            $this->pdo->prepare('UPDATE quiz_sessions SET status="running", current_question_order=?, started_at=IFNULL(started_at, NOW()) WHERE id=?')
                ->execute([$questionOrder, $sessionId]);

            $this->pdo->commit();
            return ['question_order' => $questionOrder, 'opens_at_ms' => $nowMs, 'closes_at_ms' => $closeMs];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function submitAnswer(int $sessionId, int $studentId, string $option): array
    {
        $nowMs = (int) floor(microtime(true) * 1000);

        $qStmt = $this->pdo->prepare(
            'SELECT sq.question_id, sq.opens_at_ms, sq.closes_at_ms, q.correct_option, q.time_limit_seconds
             FROM quiz_sessions s
             JOIN quiz_session_questions sq ON sq.session_id = s.id AND sq.question_order = s.current_question_order
             JOIN quiz_questions q ON q.id = sq.question_id
             WHERE s.id = ? AND s.status = "running" AND sq.status = "open" LIMIT 1'
        );
        $qStmt->execute([$sessionId]);
        $active = $qStmt->fetch();
        if (!$active) {
            throw new RuntimeException('No active question', 409);
        }

        if ($nowMs > (int) $active['closes_at_ms']) {
            throw new RuntimeException('Time is over', 409);
        }

        $latency = $this->fetchLatencyCompensation($sessionId, $studentId);
        $isCorrect = strtoupper($option) === $active['correct_option'];

        $calc = ScoringEngine::calculate(
            $isCorrect,
            $nowMs,
            (int) $active['opens_at_ms'],
            ((int) $active['time_limit_seconds']) * 1000,
            $latency,
            (int) $this->config['quiz']['max_score']
        );

        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO quiz_answers (session_id, question_id, student_id, selected_option, submitted_at_ms, latency_compensation_ms, effective_time_ms, is_correct, score_awarded)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $sessionId,
                $active['question_id'],
                $studentId,
                strtoupper($option),
                $nowMs,
                $latency,
                $calc['effective_time_ms'],
                $isCorrect ? 1 : 0,
                $calc['score'],
            ]);

            $upsert = $this->pdo->prepare(
                'INSERT INTO quiz_session_scores (session_id, student_id, total_score, correct_count, wrong_count, unanswered_count)
                 VALUES (?, ?, ?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE
                    total_score = total_score + VALUES(total_score),
                    correct_count = correct_count + VALUES(correct_count),
                    wrong_count = wrong_count + VALUES(wrong_count)'
            );
            $upsert->execute([
                $sessionId,
                $studentId,
                $calc['score'],
                $isCorrect ? 1 : 0,
                $isCorrect ? 0 : 1,
            ]);

            $this->pdo->commit();
            return ['accepted' => true, 'score_awarded' => $calc['score']];
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            if ((int) $e->getCode() === 23000) {
                throw new RuntimeException('Answer already submitted', 409);
            }
            throw $e;
        }
    }

    private function fetchLatencyCompensation(int $sessionId, int $studentId): int
    {
        $stmt = $this->pdo->prepare('SELECT last_ping_ms FROM quiz_session_students WHERE session_id = ? AND student_id = ? LIMIT 1');
        $stmt->execute([$sessionId, $studentId]);
        $row = $stmt->fetch();
        $pingMs = (int) ($row['last_ping_ms'] ?? 0);

        $cap = (int) $this->config['quiz']['latency_compensation_cap_ms'];
        return min($cap, (int) floor($pingMs / 2));
    }
}
