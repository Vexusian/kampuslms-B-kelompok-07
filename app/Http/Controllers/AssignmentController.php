<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar tugas untuk mata kuliah tertentu.
     */
    public function index(Course $course)
    {
        $user = auth()->user();
        if ($user) {
            $isAdmin = $user->role === 'admin';
            $isLecturer = $user->role === 'dosen' && $course->lecturer_id === $user->id;
            $isEnrolled = $user->role === 'mahasiswa' && $course->students()->where('users.id', $user->id)->exists();

            abort_unless($isAdmin || $isLecturer || $isEnrolled, 403, 'Akses ditolak: Anda tidak terdaftar pada mata kuliah ini.');
        }

        $assignments = $course->assignments()->latest()->paginate(15);
        return view('assignments.index', compact('course', 'assignments'));
    }

    /**
     * Form tambah penugasan baru.
     */
    public function create(Course $course)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda bukan pengampu mata kuliah ini.');
        }

        return view('assignments.create', compact('course'));
    }

    /**
     * Simpan penugasan baru.
     */
    public function store(Request $request, Course $course)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda bukan pengampu mata kuliah ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'required|date',
        ]);

        $assignment = $course->assignments()->create($validated);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.assignments.show', $assignment)->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Menampilkan detail tugas (Route Model Binding dengan proteksi IDOR sementara).
     */
    public function show(Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            $isAdmin = $user->role === 'admin';
            $isLecturer = $user->role === 'dosen' && $assignment->course->lecturer_id === $user->id;
            $isEnrolled = $user->role === 'mahasiswa' && $assignment->course->students()->where('users.id', $user->id)->exists();

            abort_unless($isAdmin || $isLecturer || $isEnrolled, 403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat tugas ini.');
        }

        $assignment->load(['course', 'submissions.student']);
        return view('assignments.show', compact('assignment'));
    }

    /**
     * Form edit tugas.
     */
    public function edit(Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk mengedit tugas ini.');
        }

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Perbarui tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk mengedit tugas ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'required|date',
        ]);

        $assignment->update($validated);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.assignments.show', $assignment)->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Hapus tugas.
     */
    public function destroy(Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk menghapus tugas ini.');
        }

        $course = $assignment->course;
        $assignment->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Tugas berhasil dihapus.');
    }
}
