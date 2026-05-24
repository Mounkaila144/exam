<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\View\View;

class ExamMonitorController extends Controller
{
    public function show(Exam $exam): View
    {
        $this->authorize('monitor', $exam);

        $assignments = $exam->assignments()
            ->withCount('incidents')
            ->orderBy('student_name')
            ->get();

        $incidents = $exam->assignments()
            ->with('incidents')
            ->get()
            ->flatMap(fn ($a) => $a->incidents->map(fn ($i) => [
                'id' => $i->id,
                'assignmentId' => $a->id,
                'studentName' => $a->student_name,
                'type' => $i->type,
                'typeLabel' => $i->typeLabel(),
                'severity' => $i->severity->value,
                'occurredAt' => $i->occurred_at?->toIso8601String(),
            ]))
            ->sortByDesc('occurredAt')
            ->take(50)
            ->values();

        return view('teacher.exams.monitor', compact('exam', 'assignments', 'incidents'));
    }
}
