<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Listener: Mengambil daftar mahasiswa yang mengambil mata kuliah terkait
 * dan mengirim notifikasi tugas baru.
 *
 * - ShouldQueue: response HTTP dosen tidak terhambat proses pengiriman notifikasi massal.
 * - Eager-load relasi course.students untuk mencegah N+1 query problem.
 * - Retry 3x dengan backoff eksponensial (10s, 30s, 60s).
 */
class SendAssignmentPublishedNotification implements ShouldQueue
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
     * Tangani event: ambil mahasiswa terdaftar lalu kirim notifikasi database.
     *
     * @param AssignmentPublished $event
     * @return void
     */
    public function handle(AssignmentPublished $event): void
    {
        // Eager-load mahasiswa yang terdaftar di mata kuliah terkait
        $assignment = $event->assignment->loadMissing('course.students');

        $students = $assignment->course?->students;

        if (! $students || $students->isEmpty()) {
            Log::info("AssignmentPublished: Tidak ada mahasiswa terdaftar di mata kuliah #{$assignment->course_id}.");
            return;
        }

        // Kirim notifikasi secara massal — efisien dan atomic
        Notification::send($students, new NewAssignmentNotification($assignment));

        Log::info("AssignmentPublished: Notifikasi terkirim ke {$students->count()} mahasiswa untuk tugas #{$assignment->id}.");
    }

    /**
     * Dipanggil setelah semua percobaan gagal.
     */
    public function failed(AssignmentPublished $event, \Throwable $exception): void
    {
        Log::error('SendAssignmentPublishedNotification GAGAL PERMANEN.', [
            'assignment_id' => $event->assignment->id,
            'course_id'     => $event->assignment->course_id,
            'error'         => $exception->getMessage(),
        ]);
    }
}
