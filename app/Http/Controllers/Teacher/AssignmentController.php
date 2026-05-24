<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Services\Security\ExamLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(private readonly ExamLockService $lockService)
    {
    }

    public function unlock(Request $request, ExamAssignment $assignment): JsonResponse
    {
        $this->authorize('unlock', $assignment);

        $comment = $request->string('comment')->toString() ?: null;

        $this->lockService->unlock($assignment, $request->user(), $comment);

        return response()->json(['ok' => true]);
    }
}
