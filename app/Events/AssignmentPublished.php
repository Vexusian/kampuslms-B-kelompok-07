<?php

namespace App\Events;

use App\Models\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Tugas baru dipublikasikan oleh dosen.
 *
 * Dipicu saat dosen menyimpan tugas baru (AssignmentController::store & Api\AssignmentController::store).
 * Listener (SendAssignmentPublishedNotification) akan mengambil daftar mahasiswa yang
 * mengambil mata kuliah terkait dan mengirim notifikasi NewAssignmentNotification.
 */
class AssignmentPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Assignment $assignment
     */
    public function __construct(
        public Assignment $assignment
    ) {}
}
