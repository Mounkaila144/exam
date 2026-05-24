<?php

namespace App\Events;

use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamUnlockedByTeacher implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExamAssignment $assignment, public User $teacher)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.'.$this->assignment->id),
            new PrivateChannel('exam.'.$this->assignment->exam_id.'.monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ExamUnlocked';
    }

    public function broadcastWith(): array
    {
        return [
            'assignmentId' => $this->assignment->id,
            'unlockedBy' => $this->teacher->name,
            'unlockedAt' => now()->toIso8601String(),
        ];
    }
}
