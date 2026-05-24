<?php

namespace App\Models;

use App\Domain\Exam\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_section_id', 'order', 'type', 'prompt', 'points',
        'bareme_text', 'autograde_config', 'choices',
    ];

    protected $hidden = ['autograde_config'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'type' => QuestionType::class,
            'points' => 'decimal:2',
            'autograde_config' => 'array',
            'choices' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'question_id');
    }

    public function exam(): HasOneThrough
    {
        return $this->hasOneThrough(Exam::class, ExamSection::class, 'id', 'id', 'exam_section_id', 'exam_id');
    }
}
