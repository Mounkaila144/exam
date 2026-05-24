<?php

namespace App\Events;

use App\Models\ExamAssignment;
use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExamAssignment $assignment, public Incident $incident)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('exam.'.$this->assignment->exam_id.'.monitor')];
    }

    public function broadcastWith(): array
    {
        return [
            'assignmentId' => $this->assignment->id,
            'studentName' => $this->assignment->student_name,
            'type' => $this->incident->type,
            'severity' => $this->incident->severity->value,
            'occurredAt' => $this->incident->occurred_at?->toIso8601String(),
            'summary' => $this->incident->typeLabel(),
        ];
    }
}
