<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar tugas untuk mata kuliah tertentu.
     */
    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $assignments = $course->assignments()->latest()->paginate(15);
        return view('assignments.index', compact('course', 'assignments'));
    }

    /**
     * Form tambah penugasan baru.
     */
    public function create(Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    /**
     * Simpan penugasan baru.
     */
    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_at' => 'required|date',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['instructions'] = $validated['instructions'] ?? $validated['description'] ?? '-';
        $validated['status'] = $validated['status'] ?? 'published';

        $assignment = $course->assignments()->create($validated);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.assignments.show', $assignment)->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Menampilkan detail tugas dengan proteksi Policy.
     */
    public function show(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'submissions.student']);
        return view('assignments.show', compact('assignment'));
    }

    /**
     * Form edit tugas.
     */
    public function edit(Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Perbarui tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_at' => 'required|date',
        ]);

        if (isset($validated['description']) && !isset($validated['instructions'])) {
            $validated['instructions'] = $validated['description'];
        }

        $assignment->update($validated);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.assignments.show', $assignment)->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Hapus tugas.
     */
    public function destroy(Assignment $assignment)
    {
        Gate::authorize('delete', $assignment);

        $course = $assignment->course;
        $assignment->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Tugas berhasil dihapus.');
    }
}
