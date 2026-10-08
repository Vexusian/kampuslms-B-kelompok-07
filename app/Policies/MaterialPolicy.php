<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Melihat detail materi:
     * - Admin boleh melihat semua.
     * - Dosen pengampu mata kuliah terkait.
     * - Mahasiswa yang terdaftar pada mata kuliah materi tersebut.
     */
    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $material->course;
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
     * Membuat materi:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Memperbarui materi:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function update(User $user, Material $material): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $material->course && $material->course->lecturer_id === $user->id);
    }

    /**
     * Menghapus materi:
     * - Admin atau dosen pengampu dari course terkait.
     */
    public function delete(User $user, Material $material): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $material->course && $material->course->lecturer_id === $user->id);
    }
}
