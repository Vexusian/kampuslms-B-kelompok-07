<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'status',
        'lecturer_id',
    ];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Penanganan cascade penghapusan berkas fisik:
     * Saat Course dihapus, database cascadeOnDelete akan menghapus record di DB,
     * sehingga event deleting() pada Material tidak otomatis terpicu di level DB.
     * Kita memicu penghapusan model dan pembersihan direktori berkas secara eksplisit.
     */
    protected static function booted(): void
    {
        static::deleting(function (Course $course) {
            $course->materials()->each(function (Material $material) {
                $material->delete();
            });

            \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('materials/' . $course->id);
        });
    }
}
