<?php

namespace App\Listeners;

use App\Events\SubmissionGraded;
use App\Notifications\GradeReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Kirim notifikasi ke mahasiswa bahwa submission-nya telah dinilai.
 *
 * - ShouldQueue: menjaga konsistensi arsitektur dan response time dosen.
 * - Eager-load submission.student & submission.assignment.course
 *   untuk menghindari N+1 di dalam Notification::toArray().
 * - Retry 3× dengan backoff eksponensial (10s, 30s, 60s).
 */
class SendGradeNotification implements ShouldQueue
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
     * Tangani event: kirim notifikasi ke mahasiswa pemilik submission.
     */
    public function handle(SubmissionGraded $event): void
    {
        // Eager-load relasi untuk menghindari N+1 di Notification::toArray()
        $grade = $event->grade->loadMissing('submission.student', 'submission.assignment.course');

        $student = $grade->submission->student;

        if (! $student) {
            Log::warning("SubmissionGraded: Student tidak ditemukan untuk submission #{$grade->submission_id}.");
            return;
        }

        $student->notify(new GradeReceivedNotification($grade));

        Log::info("SubmissionGraded: Notifikasi terkirim ke mahasiswa #{$student->id} untuk submission #{$grade->submission_id}.");
    }

    /**
     * Dipanggil setelah semua percobaan gagal.
     * Mencatat ke log agar bisa dipantau/di-alert.
     */
    public function failed(SubmissionGraded $event, \Throwable $exception): void
    {
        Log::error('SendGradeNotification GAGAL PERMANEN.', [
            'grade_id'      => $event->grade->id,
            'submission_id' => $event->grade->submission_id,
            'error'         => $exception->getMessage(),
        ]);
    }
}
