<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'student_email', 'student_name',
        'student_matricule', 'student_group', 'access_token',
        'opened_at', 'locked', 'locked_reason', 'locked_at',
        'submitted_at', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'locked' => 'boolean',
            'locked_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->submitted_at !== null => 'soumis',
            $this->locked => 'verrouillé',
            $this->opened_at !== null => 'en cours',
            default => 'en attente',
        };
    }
}
