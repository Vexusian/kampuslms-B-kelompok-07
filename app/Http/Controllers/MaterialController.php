<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar materi pada mata kuliah tertentu.
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

        $materials = $course->materials()->latest()->paginate(15);
        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Form tambah materi baru untuk suatu mata kuliah.
     */
    public function create(Course $course)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda bukan pengampu mata kuliah ini.');
        }

        return view('materials.create', compact('course'));
    }

    /**
     * Simpan materi baru.
     */
    public function store(Request $request, Course $course)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda bukan pengampu mata kuliah ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $material = $course->materials()->create($validated);

        return redirect()->route('materials.show', $material)->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail materi (Route Model Binding dengan proteksi IDOR sementara).
     */
    public function show(Material $material)
    {
        $user = auth()->user();
        if ($user) {
            $isAdmin = $user->role === 'admin';
            $isLecturer = $user->role === 'dosen' && $material->course->lecturer_id === $user->id;
            $isEnrolled = $user->role === 'mahasiswa' && $material->course->students()->where('users.id', $user->id)->exists();

            abort_unless($isAdmin || $isLecturer || $isEnrolled, 403, 'Akses ditolak: Anda tidak berhak mengakses materi ini.');
        }

        return view('materials.show', compact('material'));
    }

    /**
     * Form edit materi.
     */
    public function edit(Material $material)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk mengedit materi ini.');
        }

        return view('materials.edit', compact('material'));
    }

    /**
     * Perbarui materi.
     */
    public function update(Request $request, Material $material)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk mengedit materi ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }

    /**
     * Hapus materi.
     */
    public function destroy(Material $material)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless($user->role === 'admin' || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id), 403, 'Akses ditolak: Anda tidak memiliki izin untuk menghapus materi ini.');
        }

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi berhasil dihapus.');
    }
}
