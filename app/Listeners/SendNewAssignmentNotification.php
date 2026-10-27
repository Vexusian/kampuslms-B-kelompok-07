<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Listener: Kirim notifikasi tugas baru ke seluruh mahasiswa di mata kuliah.
 *
 * - ShouldQueue: agar response HTTP dosen tidak tertahan (bisa puluhan mahasiswa).
 * - Eager-load course.students untuk menghindari N+1.
 * - Retry 3× dengan backoff eksponensial (10s, 30s, 60s).
 */
class SendNewAssignmentNotification implements ShouldQueue
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
     * Tangani event: kirim notifikasi massal ke mahasiswa.
     *
     * Notification::send() lebih efisien daripada loop $student->notify()
     * karena menghindari overhead inisiasi per-user.
     */
    public function handle(AssignmentPublished $event): void
    {
        // Eager-load untuk menghindari N+1 query
        $assignment = $event->assignment->loadMissing('course.students');

        $students = $assignment->course->students;

        if ($students->isEmpty()) {
            Log::info("AssignmentPublished: Tidak ada mahasiswa terdaftar di course #{$assignment->course_id}.");
            return;
        }

        // Kirim notifikasi massal — satu query INSERT per batch
        Notification::send($students, new NewAssignmentNotification($assignment));

        Log::info("AssignmentPublished: Notifikasi terkirim ke {$students->count()} mahasiswa untuk assignment #{$assignment->id}.");
    }

    /**
     * Dipanggil setelah semua percobaan gagal.
     * Mencatat ke log agar bisa dipantau/di-alert.
     */
    public function failed(AssignmentPublished $event, \Throwable $exception): void
    {
        Log::error('SendNewAssignmentNotification GAGAL PERMANEN.', [
            'assignment_id' => $event->assignment->id,
            'course_id'     => $event->assignment->course_id,
            'error'         => $exception->getMessage(),
        ]);
    }
}
