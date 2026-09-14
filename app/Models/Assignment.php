<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'allow_late',
        'status',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function grades(): HasManyThrough
    {
        return $this->hasManyThrough(
            Grade::class,
            Submission::class
        );
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'integer',
            'allow_late' => 'boolean',
        ];
    }
}