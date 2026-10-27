<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    use HasFactory; // ⚠️ WAJIB ADA

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'allow_late',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Penanganan cascade penghapusan berkas fisik:
     * Saat Assignment dihapus, database cascadeOnDelete akan menghapus record submissions di DB,
     * sehingga event deleting() pada model Submission tidak otomatis terpicu.
     * Kita memicu penghapusan model dan membersihkan direktori penyimpanan privat secara eksplisit.
     */
    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment) {
            $assignment->submissions()->each(function (Submission $submission) {
                $submission->delete();
            });

            \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('submissions/' . $assignment->id);
        });
    }
}
