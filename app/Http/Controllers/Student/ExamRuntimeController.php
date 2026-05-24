<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamTimerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamRuntimeController extends Controller
{
    public function show(Request $request, ExamTimerService $timer): View
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        $submission = $assignment->submission()->firstOrCreate([], ['answers' => []]);

        $exam = $assignment->exam->load('sections.questions');

        // Strip autograde_config server-side before any rendering — never trust the model alone.
        $exam->sections->each(function ($section) {
            $section->questions->each(function ($q) {
                $q->makeHidden('autograde_config');
            });
        });

        return view('student.runtime', [
            'assignment' => $assignment,
            'exam' => $exam,
            'submission' => $submission,
            'remaining_seconds' => $timer->remainingFor($assignment),
            'security_settings' => array_merge(\App\Models\Exam::DEFAULT_SECURITY_SETTINGS, $exam->security_settings ?? []),
        ]);
    }
}
