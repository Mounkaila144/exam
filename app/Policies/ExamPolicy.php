<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function view(User $user, Exam $exam): bool
    {
        return $user->id === $exam->teacher_id;
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->id === $exam->teacher_id;
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->id === $exam->teacher_id;
    }

    public function monitor(User $user, Exam $exam): bool
    {
        return $user->id === $exam->teacher_id;
    }
}
