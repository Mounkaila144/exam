<?php

namespace App\Domain\Grading;

class ScoreCalculator
{
    public static function total(?float $auto, ?float $manual): ?float
    {
        if ($auto === null && $manual === null) {
            return null;
        }

        return (float) ($auto ?? 0) + (float) ($manual ?? 0);
    }
}
