<?php

namespace App\Console\Commands;

use App\Domain\Exam\ExamStatus;
use App\Models\Exam;
use Illuminate\Console\Command;

class CloseExpiredExamsCommand extends Command
{
    protected $signature = 'exam:close-expired';

    protected $description = 'Close exams whose closes_at deadline has passed.';

    public function handle(): int
    {
        $count = Exam::where('status', ExamStatus::PUBLISHED->value)
            ->whereNotNull('closes_at')
            ->where('closes_at', '<', now())
            ->update(['status' => ExamStatus::CLOSED->value]);

        $this->info("{$count} examen(s) fermés.");

        return self::SUCCESS;
    }
}
