<?php

namespace App\Mail;

use App\Models\ExamAssignment;
use App\Services\Exam\AssignmentTokenGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExamAssignmentMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExamAssignment $assignment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ExamGuard] Accès à votre examen — '.$this->assignment->exam->title,
        );
    }

    public function content(): Content
    {
        $url = app(AssignmentTokenGenerator::class)->signedUrlFor($this->assignment);

        return new Content(
            view: 'emails.exam-assignment',
            with: [
                'assignment' => $this->assignment,
                'exam' => $this->assignment->exam,
                'url' => $url,
            ],
        );
    }
}
