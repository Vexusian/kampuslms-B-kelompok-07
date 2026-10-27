<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi: Tugas baru telah dipublikasikan di mata kuliah.
 *
 * Saluran  : database
 * Penerima : mahasiswa yang terdaftar di mata kuliah terkait
 */
class NewAssignmentNotification extends Notification implements ShouldQueue
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
     * @param Assignment $assignment
     */
    public function __construct(
        public Assignment $assignment
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
        $this->assignment->loadMissing('course');

        return [
            'type'             => 'new_assignment',
            'assignment_id'    => $this->assignment->id,
            'assignment_title' => $this->assignment->title,
            'course_id'        => $this->assignment->course_id,
            'course_name'      => $this->assignment->course?->name ?? 'Mata Kuliah',
            'due_at'           => $this->assignment->due_at?->toIso8601String(),
            'message'          => "Tugas baru \"{$this->assignment->title}\" telah dipublikasikan di {$this->assignment->course?->name}.",
        ];
    }
    { 
       return [
           'type' => 'new_assignment',
           'assignment_id' => $this->assignment->id,
           'assignment_title' => $this->assignment->title,
];
    }
    public function failed(\Throwable $exception): void
{
    Log::error('Pengiriman notifikasi tugas baru gagal.', [
        'assignment_id' => $this->assignment->id,
        'error' => $exception->getMessage(),
    ]);
}
}
