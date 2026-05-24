<?php

namespace App\Services\Security;

use App\Domain\Incident\IncidentSeverity;
use App\Domain\Incident\IncidentType;
use App\Events\ExamUnlockedByTeacher;
use App\Events\StudentLocked;
use App\Events\StudentLockedForStudent;
use App\Models\ExamAssignment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExamLockService
{
    public function lock(ExamAssignment $assignment, string $reason): bool
    {
        if ($assignment->locked) {
            return false;
        }

        DB::transaction(function () use ($assignment, $reason) {
            $assignment->update([
                'locked' => true,
                'locked_reason' => $reason,
                'locked_at' => now(),
            ]);
        });

        event(new StudentLocked($assignment->fresh(), $reason));
        event(new StudentLockedForStudent($assignment->fresh(), $reason));

        return true;
    }

    public function unlock(ExamAssignment $assignment, User $teacher, ?string $comment = null): bool
    {
        if (! $assignment->locked) {
            return false;
        }

        DB::transaction(function () use ($assignment, $teacher, $comment) {
            $assignment->update([
                'locked' => false,
                'locked_reason' => null,
                'locked_at' => null,
            ]);

            Incident::create([
                'exam_assignment_id' => $assignment->id,
                'type' => IncidentType::UNLOCKED_BY_TEACHER->value,
                'severity' => IncidentSeverity::INFO->value,
                'payload' => [
                    'actor_id' => $teacher->id,
                    'comment' => $comment,
                ],
                'occurred_at' => now(),
                'created_at' => now(),
            ]);
        });

        event(new ExamUnlockedByTeacher($assignment->fresh(), $teacher));

        return true;
    }
}
