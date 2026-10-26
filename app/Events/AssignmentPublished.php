<?php

namespace App\Events;

use App\Models\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Tugas baru dipublikasikan oleh dosen.
 *
 * Dipicu di AssignmentController::store() setelah assignment berhasil disimpan.
 * Listener akan mengirim notifikasi ke seluruh mahasiswa yang terdaftar
 * di mata kuliah terkait.
 */
class AssignmentPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Assignment $assignment
    ) {}
}
