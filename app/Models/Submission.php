<?php

namespace App\Models;

use App\Domain\Exam\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'exam_assignment_id', 'answers', 'auto_score', 'manual_score',
        'total_score', 'status', 'graded_at', 'sent_at',
        'claude_raw_response', 'claude_grade_details',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'auto_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'status' => SubmissionStatus::class,
            'graded_at' => 'datetime',
            'sent_at' => 'datetime',
            'claude_grade_details' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ExamAssignment::class, 'exam_assignment_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', 'submission');
    }
}
