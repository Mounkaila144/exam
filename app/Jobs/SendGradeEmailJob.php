<?php

namespace App\Jobs;

use App\Domain\Exam\SubmissionStatus;
use App\Mail\GradeMailable;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendGradeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $submissionId)
    {
    }

    public function handle(): void
    {
        $submission = Submission::with('assignment.exam')->findOrFail($this->submissionId);
        $assignment = $submission->assignment;

        Mail::to($assignment->student_email, $assignment->student_name)
            ->send(new GradeMailable($submission));

        $submission->update([
            'status' => SubmissionStatus::SENT->value,
            'sent_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('grade_email.failed', [
            'submission_id' => $this->submissionId,
            'exception' => $e->getMessage(),
        ]);
    }
}
