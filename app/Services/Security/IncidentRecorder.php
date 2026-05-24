<?php

namespace App\Services\Security;

use App\Domain\Incident\IncidentSeverity;
use App\Domain\Incident\IncidentType;
use App\Events\IncidentRecorded;
use App\Models\ExamAssignment;
use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentRaisedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidentRecorder
{
    public function __construct(private readonly ExamLockService $lockService)
    {
    }

    public function record(ExamAssignment $assignment, IncidentType $type, array $payload, ?Request $request = null): Incident
    {
        $incident = DB::transaction(function () use ($assignment, $type, $payload, $request) {
            $incident = Incident::create([
                'exam_assignment_id' => $assignment->id,
                'type' => $type->value,
                'severity' => $type->severity()->value,
                'payload' => $payload,
                'ip' => $request?->ip(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
                'occurred_at' => now(),
                'created_at' => now(),
            ]);

            $exam = $assignment->exam;
            $lockOnFirst = (bool) $exam->security('lock_on_first_offense', true);
            $threshold = (int) $exam->security('lock_on_offense_count', 3);

            if ($type->severity() === IncidentSeverity::CRITICAL) {
                $criticalCount = Incident::where('exam_assignment_id', $assignment->id)
                    ->where('severity', IncidentSeverity::CRITICAL->value)
                    ->count();

                if ($lockOnFirst || $criticalCount >= $threshold) {
                    $this->lockService->lock($assignment, $type->value);
                }
            }

            return $incident;
        });

        event(new IncidentRecorded($assignment, $incident));

        $teacher = $assignment->exam->teacher;
        if ($teacher && $type->severity() !== IncidentSeverity::INFO) {
            $teacher->notify(new IncidentRaisedNotification($assignment, $incident));
        }

        return $incident;
    }
}
