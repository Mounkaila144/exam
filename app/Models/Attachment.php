<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'question_id',
        'filename', 'mime_type', 'size_bytes', 'storage_path',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
