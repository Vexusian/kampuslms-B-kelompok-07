<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory; // ⚠️ WAJIB ADA

    protected $fillable = [
        'course_id',
        'title',
        'content',
    ];
    
    protected function casts(): array
    {
        return [];
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
