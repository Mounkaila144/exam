<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherApprovedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $teacher)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ExamGuard] Votre compte a été activé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-approved',
            with: ['teacher' => $this->teacher],
        );
    }
}
