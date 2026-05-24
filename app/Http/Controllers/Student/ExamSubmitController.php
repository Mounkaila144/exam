<?php

namespace App\Http\Controllers\Student;

use App\Domain\Exam\SubmissionStatus;
use App\Events\StudentSubmitted;
use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamTimerService;
use App\Services\Grading\AutoGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamSubmitController extends Controller
{
    public function store(Request $request, ExamTimerService $timer, AutoGradingService $autoGrader): RedirectResponse
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        if ($assignment->submitted_at) {
            return redirect()->route('student.exam.submitted', ['token' => $assignment->access_token]);
        }

        if ($timer->isExpired($assignment)) {
            // Force submit even if expired — we save what we have.
        }

        DB::transaction(function () use ($assignment, $autoGrader) {
            $assignment->update(['submitted_at' => now()]);
            $submission = $assignment->submission()->lockForUpdate()->first();
            $submission->update(['status' => SubmissionStatus::SUBMITTED->value]);
            $autoGrader->grade($submission->fresh());
        });

        event(new StudentSubmitted($assignment->fresh()));

        return redirect()->route('student.exam.submitted', ['token' => $assignment->access_token]);
    }

    public function submitted(Request $request): View
    {
        return view('student.submitted', [
            'assignment' => $request->attributes->get('examAssignment'),
        ]);
    }
}
