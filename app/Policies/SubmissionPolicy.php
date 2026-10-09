<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Melihat daftar submission pada suatu assignment:
     * - Admin boleh melihat semua.
     * - Dosen pengampu mata kuliah terkait.
     * - Mahasiswa TIDAK boleh melihat daftar submission orang lain.
     */
    public function viewAny(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $assignment->course && $assignment->course->lecturer_id === $user->id;
    }

    /**
     * Melihat detail submission (Penangkal IDOR utama):
     * - Mahasiswa pemilik submission ($submission->user_id === $user->id).
     * - Admin.
     * - Dosen pengampu mata kuliah terkait.
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($submission->user_id === $user->id) {
            return true;
        }

        $course = $submission->assignment?->course;
        if ($user->role === 'dosen' && $course && $course->lecturer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Mengumpulkan tugas:
     * - Mahasiswa yang terdaftar pada mata kuliah penugasan tersebut.
     * - Hanya jika belum pernah mengumpulkan (pengumpulan bersifat final/1 kali).
     */
    public function create(User $user, Assignment $assignment): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        $isEnrolled = $assignment->course->students()->where('users.id', $user->id)->exists();
        if (!$isEnrolled) {
            return false;
        }

        return !$assignment->submissions()->where('user_id', $user->id)->exists();
    }

    /**
     * Memperbarui submission:
     * - Pengumpulan tugas bersifat final; mahasiswa tidak diperbolehkan mengedit kembali.
     */
    public function update(User $user, Submission $submission): bool
    {
        return false;
    }

    /**
     * Menghapus submission:
     * - Admin atau mahasiswa pemilik submission.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return $user->role === 'admin' || $user->id === $submission->user_id;
    }
}
