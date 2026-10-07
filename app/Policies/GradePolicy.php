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
     * - Admin atau dosen pengampu mata kuliah terkait.
     */
    public function create(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $submission->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }

    /**
     * Mengubah nilai:
     * - Admin atau dosen pengampu mata kuliah terkait.
     */
    public function update(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $grade->submission?->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }

    /**
     * Menghapus nilai:
     * - Admin atau dosen pengampu mata kuliah terkait.
     */
    public function delete(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $grade->submission?->assignment?->course;
        return $user->role === 'dosen' && $course && $course->lecturer_id === $user->id;
    }
}
