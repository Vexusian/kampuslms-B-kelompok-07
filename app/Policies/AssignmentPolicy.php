<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Melihat detail penugasan:
     * - Admin boleh melihat semua.
     * - Dosen pengampu mata kuliah terkait.
     * - Mahasiswa yang terdaftar pada mata kuliah penugasan tersebut.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;
        if (!$course) {
            return false;
        }

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Membuat tugas:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Memperbarui tugas:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $assignment->course && $assignment->course->lecturer_id === $user->id);
    }

    /**
     * Menghapus tugas:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $assignment->course && $assignment->course->lecturer_id === $user->id);
    }
}
