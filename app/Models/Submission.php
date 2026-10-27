<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory; // ⚠️ WAJIB ADA

    protected $fillable = [
        'assignment_id',
        'user_id',
        'content',
        'file_path',
        'original_filename',
        'submitted_at',
        'is_late',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late'      => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    // ─── Lifecycle: Hapus berkas fisik saat record dihapus ──────
    //
    // Menggunakan event model `deleting` melalui booted().
    //
    // ⚠️  KASUS DI MANA EVENT MODEL TIDAK TERPICU:
    //
    //   1. Query builder massal (mass delete):
    //      Submission::where('assignment_id', 5)->delete();
    //      → Laravel menjalankan DELETE SQL langsung tanpa memuat model.
    //      → Event deleting/deleted TIDAK difire.
    //
    //   2. cascadeOnDelete di migration (FK constraint):
    //      Jika Assignment dihapus dan tabel submissions punya
    //      ->cascadeOnDelete(), DB engine (MySQL/PostgreSQL) menghapus
    //      row submission secara langsung di level database.
    //      → Eloquent tidak terlibat sama sekali.
    //      → Event deleting/deleted TIDAK difire.
    //      → Berkas fisik menjadi orphan (yatim) di disk.
    //
    //   3. Raw SQL / DB::table():
    //      DB::table('submissions')->where(...)->delete();
    //      → Melewati Eloquent sepenuhnya, event tidak difire.
    //
    //   Solusi untuk kasus-kasus di atas:
    //   - Gunakan $assignment->submissions->each->delete() (loop Eloquent).
    //   - Atau buat scheduled command (artisan) untuk membersihkan
    //     berkas orphan secara periodik.
    //   - Atau ganti cascadeOnDelete() dengan restrictOnDelete() lalu
    //     tangani penghapusan submissions secara manual sebelum hapus
    //     assignment.
    //
    protected static function booted(): void
    {
        static::deleting(function (Submission $submission) {
            if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
                Storage::disk('local')->delete($submission->file_path);
            }
        });
    }
}
