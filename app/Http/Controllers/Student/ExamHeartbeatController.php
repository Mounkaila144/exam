<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Services\Exam\ExamTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamHeartbeatController extends Controller
{
    public function show(Request $request, ExamTimerService $timer): JsonResponse
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        return response()->json([
            'ok' => true,
            'data' => [
                'remaining_seconds' => $timer->remainingFor($assignment),
            ],
        ]);
    }
}
