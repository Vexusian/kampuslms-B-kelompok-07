<?php

namespace App\Listeners;

use App\Events\GradeSubmitted;
use App\Notifications\GradeSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Mengirim notifikasi khusus ke mahasiswa pemilik submission saat nilai diberikan.
 *
 * - ShouldQueue: response HTTP penilaian dosen tidak terhambat proses antrean notifikasi.
 * - Eager-load relasi submission.student dan submission.assignment.course untuk mencegah N+1.
 * - Retry 3x dengan backoff eksponensial (10s, 30s, 60s).
 */
class SendGradeSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Jumlah percobaan ulang sebelum job dianggap gagal.
     */
    public int $tries = 3;

    /**
     * Backoff eksponensial (dalam detik) antar percobaan.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Tangani event: kirim notifikasi database khusus ke mahasiswa pemilik submission.
     *
     * @param GradeSubmitted $event
     * @return void
     */
    public function handle(GradeSubmitted $event): void
    {
        // Eager-load relasi untuk menghindari N+1 query problem
        $grade = $event->grade->loadMissing('submission.student', 'submission.assignment.course');

        $student = $grade->submission?->student ?? $event->submission?->student;

        if (! $student) {
            Log::warning("GradeSubmitted: Mahasiswa tidak ditemukan untuk submission #{$grade->submission_id}.");
            return;
        }

        // Kirim notifikasi khusus hanya ke mahasiswa pemilik submission
        $student->notify(new GradeSubmittedNotification($grade));

        Log::info("GradeSubmitted: Notifikasi penilaian terkirim ke mahasiswa #{$student->id} untuk submission #{$grade->submission_id}.");
    }

    /**
     * Dipanggil setelah semua percobaan gagal.
     */
    public function failed(GradeSubmitted $event, \Throwable $exception): void
    {
        Log::error('SendGradeSubmittedNotification GAGAL PERMANEN.', [
            'grade_id'      => $event->grade->id,
            'submission_id' => $event->grade->submission_id,
            'error'         => $exception->getMessage(),
        ]);
    }
}
