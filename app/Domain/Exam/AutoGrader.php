<?php

namespace App\Domain\Exam;

/**
 * Pure domain logic for auto-grading VF and QCM questions.
 * No Laravel dependencies — unit-testable in isolation.
 */
class AutoGrader
{
    public static function gradeVf(?string $studentAnswer, array $config, float $points): float
    {
        $correct = $config['correct'] ?? null;
        $penalty = (float) ($config['penalty'] ?? 0);

        if ($studentAnswer === null || $studentAnswer === '') {
            return 0.0;
        }

        if ($studentAnswer === $correct) {
            return $points;
        }

        return $penalty;
    }

    public static function gradeQcm(?string $studentAnswer, array $config, float $points): float
    {
        if ($studentAnswer === null || $studentAnswer === '') {
            return 0.0;
        }

        return $studentAnswer === ($config['correct'] ?? null) ? $points : 0.0;
    }
}
