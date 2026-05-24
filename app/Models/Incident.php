<?php

namespace App\Models;

use App\Domain\Incident\IncidentSeverity;
use App\Domain\Incident\IncidentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'exam_assignment_id', 'type', 'severity', 'payload',
        'ip', 'user_agent', 'occurred_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'severity' => IncidentSeverity::class,
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ExamAssignment::class, 'exam_assignment_id');
    }

    public function typeLabel(): string
    {
        return IncidentType::tryFrom($this->type)?->label() ?? $this->type;
    }
}
