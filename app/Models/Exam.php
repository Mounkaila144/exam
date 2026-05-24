<?php

namespace App\Models;

use App\Domain\Exam\ExamStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'title', 'description', 'duration_minutes',
        'opens_at', 'closes_at', 'status', 'security_settings',
        'claude_prompt_template',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'status' => ExamStatus::class,
            'security_settings' => 'array',
        ];
    }

    public const DEFAULT_SECURITY_SETTINGS = [
        'enforce_fullscreen' => true,
        'lock_on_first_offense' => true,
        'lock_on_offense_count' => 3,
        'block_copy_paste' => true,
        'block_right_click' => true,
        'block_devtools_shortcuts' => true,
        'detect_devtools_open' => true,
        'lock_on_ip_change' => false,
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('order');
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, ExamSection::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function scopeForTeacher(Builder $query, User $teacher): Builder
    {
        return $query->where('teacher_id', $teacher->id);
    }

    public function isDraft(): bool
    {
        return $this->status === ExamStatus::DRAFT;
    }

    public function security(string $key, $default = null)
    {
        return data_get(
            array_merge(self::DEFAULT_SECURITY_SETTINGS, $this->security_settings ?? []),
            $key,
            $default
        );
    }
}
