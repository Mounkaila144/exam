<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->assignment->exam->teacher_id;
    }

    public function grade(User $user, Submission $submission): bool
    {
        return $user->id === $submission->assignment->exam->teacher_id;
    }
}
