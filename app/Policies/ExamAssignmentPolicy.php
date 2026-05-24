<?php

namespace App\Policies;

use App\Models\ExamAssignment;
use App\Models\User;

class ExamAssignmentPolicy
{
    public function manage(User $user, ExamAssignment $assignment): bool
    {
        return $user->id === $assignment->exam->teacher_id;
    }

    public function unlock(User $user, ExamAssignment $assignment): bool
    {
        return $user->id === $assignment->exam->teacher_id;
    }
}
