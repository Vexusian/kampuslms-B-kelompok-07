<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: Tugas Anda telah dinilai oleh dosen.
 *
 * Saluran  : database
 * Penerima : mahasiswa pemilik submission
 * Payload  : TIDAK mengandung nilai (score) atau isi feedback demi privasi.
 *            Mahasiswa harus membuka halaman detail submission untuk melihat nilai.
 */
class GradeReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Grade $grade
    ) {}

    /**
     * Saluran pengiriman notifikasi.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload yang disimpan di tabel `notifications.data`.
     * Sengaja TIDAK menyertakan score & feedback agar tidak bocor
     * melalui endpoint notifikasi. Mahasiswa wajib buka detail submission.
     */
    public function toArray(object $notifiable): array
    {
        $submission = $this->grade->submission;
        $assignment = $submission->assignment;

        return [
            'type'             => 'grade_received',
            'submission_id'    => $submission->id,
            'assignment_id'    => $assignment->id,
            'assignment_title' => $assignment->title,
            'course_name'      => $assignment->course->name,
            'graded_at'        => $this->grade->graded_at?->toIso8601String(),
            'message'          => "Tugas \"{$assignment->title}\" telah dinilai. Lihat hasilnya di halaman detail.",
        ];
    }
}
