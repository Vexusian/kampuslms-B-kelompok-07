<?php

namespace App\Events;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Nilai diberikan oleh dosen untuk submission mahasiswa.
 *
 * Dipicu saat dosen menyimpan nilai dan feedback untuk submission mahasiswa.
 * Membawa data submission/nilai.
 * Listener (SendGradeSubmittedNotification) akan mengirim notifikasi khusus
 * ke mahasiswa pemilik submission.
 */
class GradeSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Submission yang dinilai.
     */
    public Submission $submission;

    /**
     * Create a new event instance.
     *
     * @param Grade $grade Data nilai/feedback
     * @param Submission|null $submission Data submission (opsional jika sudah terhubung via relasi)
     */
    public function __construct(
        public Grade $grade,
        ?Submission $submission = null
    ) {
        $this->submission = $submission ?? $grade->submission;
    }
}
