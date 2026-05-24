<?php

namespace App\Http\Controllers\Student;

use App\Domain\Exam\SubmissionStatus;
use App\Events\StudentJoined;
use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ExamEntryController extends Controller
{
    public function show(Request $request): View
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        return view('student.entry', [
            'assignment' => $assignment,
            'exam' => $assignment->exam,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        if (! $assignment->opened_at) {
            $assignment->update([
                'opened_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);

            $assignment->submission()->firstOrCreate([], [
                'answers' => [],
                'status' => SubmissionStatus::IN_PROGRESS->value,
            ]);

            event(new StudentJoined($assignment));
        }

        return redirect()->to(URL::signedRoute('student.exam.run', ['token' => $assignment->access_token]));
    }
}
