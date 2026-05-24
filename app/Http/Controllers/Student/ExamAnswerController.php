<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAnswersRequest;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExamAnswerController extends Controller
{
    public function store(SaveAnswersRequest $request, ExamTimerService $timer): JsonResponse
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        if ($timer->isExpired($assignment)) {
            return response()->json(['ok' => false, 'error' => 'exam_expired'], 409);
        }

        DB::transaction(function () use ($assignment, $request) {
            $submission = $assignment->submission()->lockForUpdate()->first();
            $answers = $submission->answers ?? [];
            $answers[(string) $request->integer('question_id')] = $request->input('value');
            $submission->update(['answers' => $answers]);
        });

        return response()->json(['ok' => true]);
    }
}
