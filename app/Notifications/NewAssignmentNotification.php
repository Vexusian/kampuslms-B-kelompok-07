<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: Tugas baru telah dipublikasikan di mata kuliah Anda.
 *
 * Saluran  : database
 * Penerima : mahasiswa yang terdaftar di mata kuliah
 * Payload  : TIDAK mengandung data sensitif (tanpa nilai/feedback)
 */
class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Assignment $assignment
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
     * Hanya informasi non-sensitif yang diperlukan untuk tampilan UI.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'new_assignment',
            'assignment_id'   => $this->assignment->id,
            'assignment_title'=> $this->assignment->title,
            'course_id'       => $this->assignment->course_id,
            'course_name'     => $this->assignment->course->name,
            'due_at'          => $this->assignment->due_at?->toIso8601String(),
            'message'         => "Tugas baru \"{$this->assignment->title}\" telah dipublikasikan di {$this->assignment->course->name}.",
        ];
    }
}
