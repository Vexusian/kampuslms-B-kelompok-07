<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi: Nilai Diberikan untuk Submission Mahasiswa.
 *
 * Saluran  : database
 * Penerima : mahasiswa pemilik submission
 * Payload  : Disimpan ke database via channel database.
 *            TIDAK mengekspos skor mentah atau detail umpan balik pada payload data
 *            demi menjaga privasi penilaian dan mencegah kebocoran data.
 *            Mahasiswa diarahkan untuk membuka halaman detail submission.
 */
class GradeSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;

public function backoff(): array
{
    return [10, 30, 60];
}

    /**
     * Create a new notification instance.
     *
     * @param Grade $grade
     */
    public function __construct(
        public Grade $grade
    ) {}

    /**
     * Saluran pengiriman notifikasi (database).
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload yang disimpan di tabel `notifications.data`.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->grade->loadMissing('submission.assignment.course');

        $submission = $this->grade->submission;
        $assignment = $submission?->assignment;

        return [
            'type'             => 'grade_submitted',
            'submission_id'    => $this->grade->submission_id,
            'assignment_id'    => $assignment?->id,
            'assignment_title' => $assignment?->title ?? 'Tugas',
            'course_name'      => $assignment?->course?->name ?? 'Mata Kuliah',
            'graded_at'        => $this->grade->graded_at?->toIso8601String(),
            'message'          => "Tugas \"{$assignment?->title}\" telah dinilai. Silakan periksa hasil penilaian di halaman detail.",
        ];
    }
    
    public function failed(\Throwable $exception): void
{
    Log::error('Pengiriman notifikasi nilai gagal.', [
        'grade_id' => $this->grade->id,
        'submission_id' => $this->grade->submission_id,
        'error' => $exception->getMessage(),
    ]);
}
}
