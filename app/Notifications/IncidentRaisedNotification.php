<?php

namespace App\Notifications;

use App\Domain\Incident\IncidentSeverity;
use App\Models\ExamAssignment;
use App\Models\Incident;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class IncidentRaisedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExamAssignment $assignment, public Incident $incident)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [WebPushChannel::class];

        if ($this->incident->severity !== IncidentSeverity::INFO && $this->shouldEmail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->assignment->exam;
        $url = route('teacher.exams.monitor', $exam);

        return (new MailMessage)
            ->subject("[ExamGuard] Incident {$this->incident->type} — {$this->assignment->student_name} — {$exam->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Incident détecté pour {$this->assignment->student_name} ({$this->assignment->student_email}).")
            ->line("Type : {$this->incident->typeLabel()}")
            ->line("Horodatage : ".$this->incident->occurred_at?->format('d/m/Y H:i:s'))
            ->action('Voir le dashboard live', $url);
    }

    public function toWebPush(object $notifiable): array
    {
        $exam = $this->assignment->exam;

        return [
            'title' => "[ExamGuard] {$this->incident->typeLabel()}",
            'body' => "{$this->assignment->student_name} — {$exam->title}",
            'url' => route('teacher.exams.monitor', $exam),
        ];
    }

    /**
     * Coalesce floods: max 1 mail per (assignment) per minute.
     */
    private function shouldEmail(): bool
    {
        $key = 'incident-mail-throttle:'.$this->assignment->id;

        if (Cache::add($key, 1, now()->addMinute())) {
            return true;
        }

        return false;
    }
}
