<?php

namespace App\Domain\Exam;

enum SubmissionStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case AUTO_GRADED = 'auto_graded';
    case GRADED = 'graded';
    case SENT = 'sent';
}
