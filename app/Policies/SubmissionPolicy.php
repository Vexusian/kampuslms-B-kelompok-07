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
     * - Hanya mahasiswa yang terdaftar pada mata kuliah penugasan tersebut.
     * - Pengumpulan bersifat final (sekali kumpul). Mahasiswa yang sudah
     *   mengumpulkan tidak diizinkan mengumpulkan ulang.
     * - Constraint unique composite (assignment_id, user_id) di database
     *   menjamin hanya ada satu submission per mahasiswa per assignment.
     */
    public function create(User $user, Assignment $assignment): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        // Mahasiswa tidak diizinkan mengumpulkan ulang jika sudah mengumpulkan
        if ($assignment->submissions()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return $assignment->course->students()->where('users.id', $user->id)->exists();
    }

    /**
     * Mengunduh berkas submission:
     * - Mahasiswa pemilik submission.
     * - Dosen pengampu mata kuliah terkait.
     * - Admin.
     *
     * Logic identik dengan view(), didedikasi agar Gate::authorize('download', ...)
     * dapat dipanggil secara eksplisit di endpoint download.
     */
    public function download(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    /**
     * Memperbarui submission:
     * - Pengumpulan bersifat final. PATCH/PUT langsung ke submission tidak diizinkan.
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
