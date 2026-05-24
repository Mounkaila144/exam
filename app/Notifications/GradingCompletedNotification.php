<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradingCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Exam $exam, public int $countGraded, public int $costCents)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[ExamGuard] Correction Claude terminée — {$this->exam->title}")
            ->line("{$this->countGraded} copies notées.")
            ->line('Coût estimé : $'.number_format($this->costCents / 100, 2))
            ->action('Voir la correction', route('teacher.exams.grading', $this->exam));
    }
}
