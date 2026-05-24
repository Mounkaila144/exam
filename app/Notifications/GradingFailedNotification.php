<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradingFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public int $examId, public string $reason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[ExamGuard] Correction Claude échouée')
            ->line("Examen #{$this->examId}")
            ->line($this->reason);
    }
}
