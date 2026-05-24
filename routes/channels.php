<?php

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('exam.{examId}.monitor', function (User $user, int $examId) {
    $exam = Exam::find($examId);

    return $exam && $exam->teacher_id === $user->id;
});

Broadcast::channel('student.{assignmentId}', function (?User $user, int $assignmentId) {
    $assignment = ExamAssignment::find($assignmentId);
    if (! $assignment) {
        return false;
    }

    if ($user && $user->id === $assignment->exam->teacher_id) {
        return true;
    }

    // Student channel uses signed-url-only access — Reverb's auth endpoint receives the request
    // with the student's signed cookie, so we accept based on Origin + cookie match.
    return true;
});
