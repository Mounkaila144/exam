<?php

namespace App\Events;

use App\Models\ExamAssignment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExamAssignment $assignment)
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
            'occurredAt' => now()->toIso8601String(),
        ];
    }
}
