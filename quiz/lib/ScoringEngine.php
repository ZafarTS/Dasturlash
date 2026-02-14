<?php

namespace Quiz\Lib;

class ScoringEngine
{
    public static function calculate(
        bool $isCorrect,
        int $receivedAtMs,
        int $opensAtMs,
        int $questionTimeMs,
        int $latencyCompensationMs,
        int $maxScore
    ): array {
        if (!$isCorrect) {
            return ['score' => 0, 'effective_time_ms' => $questionTimeMs];
        }

        $rawElapsed = max(0, $receivedAtMs - $opensAtMs);
        $effective = max(0, min($questionTimeMs, $rawElapsed - $latencyCompensationMs));
        $score = (int) round($maxScore * (1 - ($effective / max(1, $questionTimeMs))));
        return ['score' => max(0, $score), 'effective_time_ms' => $effective];
    }
}
