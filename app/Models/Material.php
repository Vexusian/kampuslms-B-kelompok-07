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
        'description',
        'type',
        'content',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'external_url',
    ];
    
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Format ukuran berkas ke satuan yang mudah dibaca pengguna (KB, MB, dll).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->file_size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Ikon FontAwesome yang sesuai dengan ekstensi berkas.
     */
    public function getFileIconAttribute(): string
    {
        $ext = strtolower(pathinfo($this->original_name ?? $this->file_path ?? '', PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'fa-file-pdf',
            'docx', 'doc' => 'fa-file-word',
            'pptx', 'ppt' => 'fa-file-powerpoint',
            default => 'fa-file-alt',
        };
    }

    /**
     * Lifecycle: Hapus berkas fisik pada disk privat saat record dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Material $material) {
            if ($material->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($material->file_path);
            }
        });
    }
}
