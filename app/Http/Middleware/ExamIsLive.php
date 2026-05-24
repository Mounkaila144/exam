<?php

namespace App\Http\Middleware;

use App\Domain\Exam\ExamStatus;
use App\Models\ExamAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExamIsLive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        abort_if(! $assignment, 404);

        $exam = $assignment->exam;

        if ($exam->status !== ExamStatus::PUBLISHED) {
            return $this->respond($request, 'exam-not-available', 423, 'exam_not_available');
        }

        $now = now();

        if ($exam->opens_at && $now->lt($exam->opens_at)) {
            return $this->respond($request, 'exam-not-available', 423, 'exam_not_open_yet');
        }

        if ($exam->closes_at && $now->gt($exam->closes_at)) {
            return $this->respond($request, 'exam-not-available', 423, 'exam_closed');
        }

        if ($assignment->locked) {
            return $this->respond($request, 'exam-locked', 423, 'exam_locked', ['assignment' => $assignment]);
        }

        if ($assignment->submitted_at) {
            return $this->respond($request, 'exam-already-submitted', 423, 'exam_already_submitted');
        }

        return $next($request);
    }

    private function respond(Request $request, string $view, int $status, string $errorCode, array $viewData = []): Response
    {
        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json(['ok' => false, 'error' => $errorCode], $status);
        }

        return response()->view('student.'.str_replace('-', '_', $view), $viewData, 200);
    }
}
