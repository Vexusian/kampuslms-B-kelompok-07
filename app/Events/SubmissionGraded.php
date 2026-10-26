<?php

namespace App\Events;

use App\Models\Grade;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Dosen memberi/memperbarui nilai pada submission mahasiswa.
 *
 * Dipicu di SubmissionController::grade() setelah grade berhasil disimpan.
 * Listener akan mengirim notifikasi ke mahasiswa pemilik submission.
 */
class SubmissionGraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Grade $grade
    ) {}
}
