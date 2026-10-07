<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Semua pengguna yang terautentikasi dapat melihat daftar,
     * namun hasil query dibatasi di level database (query scoping).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Akses detail satu mata kuliah:
     * - Admin boleh melihat semua.
     * - Dosen hanya boleh melihat mata kuliah yang diampunya.
     * - Mahasiswa hanya boleh melihat mata kuliah yang diikutinya (enrolled).
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
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
     * Hanya admin yang berhak membuat mata kuliah baru.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Memperbarui mata kuliah:
     * - Admin boleh mengubah mata kuliah apa pun.
     * - Dosen pengampu boleh mengedit mata kuliah miliknya sendiri.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Hanya admin yang berhak menghapus mata kuliah.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Kelola enrollment mahasiswa:
     * - Admin untuk semua mata kuliah.
     * - Dosen untuk mata kuliah yang diampunya.
     */
    public function enroll(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }
}
