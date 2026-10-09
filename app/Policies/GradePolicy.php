<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Melihat nilai:
     * - Admin.
     * - Dosen pengampu mata kuliah terkait.
     * - Mahasiswa pemilik submission.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($grade->submission && $grade->submission->user_id === $user->id) {
            return true;
        }

        $course = $grade->submission?->assignment?->course;
        if ($user->role === 'dosen' && $course && $course->lecturer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Memberi nilai:
     * - Sesuai Tabel 3 Spesifikasi: HANYA Dosen pengampu mata kuliah terkait (Admin = —).
     */
    public function create(User $user, Submission $submission): bool
    {
        $course = $submission->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }

    /**
     * Mengubah nilai:
     * - Sesuai Tabel 3 Spesifikasi: HANYA Dosen pengampu mata kuliah terkait (Admin = —).
     */
    public function update(User $user, Grade $grade): bool
    {
        $course = $grade->submission?->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }

    /**
     * Menghapus nilai:
     * - Sesuai Tabel 3 Spesifikasi: HANYA Dosen pengampu mata kuliah terkait (Admin = —).
     */
    public function delete(User $user, Grade $grade): bool
    {
        $course = $grade->submission?->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }
}
