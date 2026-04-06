<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'nom', 'email', 'matricule', 'groupe',
        'answers_vf', 'answers_qcm', 'score_vf', 'score_qcm', 'score_p1',
        'open_answers',
        'note_p2', 'note_p3', 'note_p4', 'note_total',
        'appreciation', 'detail_notes',
        'status', 'graded_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_vf' => 'array',
            'answers_qcm' => 'array',
            'open_answers' => 'array',
            'detail_notes' => 'array',
            'score_vf' => 'decimal:2',
            'score_qcm' => 'decimal:2',
            'score_p1' => 'decimal:2',
            'note_p2' => 'decimal:2',
            'note_p3' => 'decimal:2',
            'note_p4' => 'decimal:2',
            'note_total' => 'decimal:2',
            'graded_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded' || $this->status === 'sent';
    }
}
