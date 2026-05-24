<?php

namespace App\Services\Exam;

use App\Models\ExamAssignment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AssignmentTokenGenerator
{
    public function makeToken(): string
    {
        return Str::random(48);
    }

    public function signedUrlFor(ExamAssignment $assignment): string
    {
        $expires = $assignment->exam->closes_at
            ? $assignment->exam->closes_at->copy()->addHour()
            : now()->addDay();

        return URL::signedRoute('student.exam.show', ['token' => $assignment->access_token], $expires);
    }
}
