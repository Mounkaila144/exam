<?php

namespace App\Jobs;

use App\Mail\ExamAssignmentMailable;
use App\Models\ExamAssignment;
use App\Notifications\AssignmentEmailFailedNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAssignmentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $assignmentId)
    {
    }

    public function handle(): void
    {
        $assignment = ExamAssignment::with('exam')->findOrFail($this->assignmentId);

        Mail::to($assignment->student_email, $assignment->student_name)
            ->send(new ExamAssignmentMailable($assignment));
    }

    public function failed(Throwable $e): void
    {
        Log::error('send_assignment_email.failed', [
            'assignment_id' => $this->assignmentId,
            'exception' => $e->getMessage(),
        ]);

        $admin = User::admins()->first();
        if ($admin) {
            $admin->notify(new AssignmentEmailFailedNotification($this->assignmentId, $e->getMessage()));
        }
    }
}
