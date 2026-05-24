<?php

namespace App\Jobs;

use App\Domain\Exam\SubmissionStatus;
use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkGradeEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $examId)
    {
    }

    public function handle(): void
    {
        $exam = Exam::with('assignments.submission')->findOrFail($this->examId);

        $count = 0;
        foreach ($exam->assignments as $assignment) {
            if ($assignment->submission && $assignment->submission->status->value === SubmissionStatus::GRADED->value) {
                SendGradeEmailJob::dispatch($assignment->submission->id);
                $count++;
            }
        }
    }
}
