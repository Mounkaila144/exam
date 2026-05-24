<?php

namespace App\Services\Exam;

use App\Models\ExamAssignment;

class ExamTimerService
{
    public function remainingFor(ExamAssignment $assignment): int
    {
        if (! $assignment->opened_at) {
            return $assignment->exam->duration_minutes * 60;
        }

        $deadline = $assignment->opened_at->copy()->addMinutes($assignment->exam->duration_minutes);

        if ($assignment->exam->closes_at && $assignment->exam->closes_at->lt($deadline)) {
            $deadline = $assignment->exam->closes_at;
        }

        return max(0, $deadline->diffInSeconds(now(), false) * -1);
    }

    public function isExpired(ExamAssignment $assignment): bool
    {
        return $this->remainingFor($assignment) <= 0;
    }
}
