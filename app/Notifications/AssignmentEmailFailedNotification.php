<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentEmailFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public int $assignmentId, public string $reason)
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
            ->subject('[ExamGuard] Envoi de lien étudiant échoué')
            ->line("Assignment #{$this->assignmentId}")
            ->line($this->reason);
    }
}
