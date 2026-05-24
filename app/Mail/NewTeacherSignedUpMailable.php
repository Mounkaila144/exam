<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTeacherSignedUpMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $teacher)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ExamGuard] Nouvelle inscription professeur : '.$this->teacher->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-teacher-signed-up',
            with: ['teacher' => $this->teacher],
        );
    }
}
