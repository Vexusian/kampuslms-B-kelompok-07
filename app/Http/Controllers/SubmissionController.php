<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar pengumpulan tugas untuk sebuah penugasan.
     */
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny', [Submission::class, $assignment]);

        $submissions = $assignment->submissions()->with(['student', 'grade'])->latest()->paginate(15);
        return view('submissions.index', compact('assignment', 'submissions'));
    }

    /**
     * Mengumpulkan tugas (khusus mahasiswa yang terdaftar di mata kuliah terkait).
     */
    public function store(Request $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        $validated = $request->validate([
            'content' => 'required|string',
            'file_path' => 'nullable|string',
        ]);

        $user = auth()->user();

        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, [
                'submitted_at' => now(),
            ])
        );

        $role = $user->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.submissions.show', $submission)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    /**
     * Menampilkan detail satu submission (Titik rawan IDOR utama).
     * Dilindungi secara ketat oleh SubmissionPolicy (hanya pemilik submission, dosen pengampu, atau admin).
     */
    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $submission->load(['assignment.course', 'student', 'grade']);
        return view('submissions.show', compact('submission'));
    }

    /**
     * Penilaian tugas oleh Dosen Pengampu atau Admin.
     */
    public function grade(Request $request, Submission $submission)
    {
        Gate::authorize('create', [Grade::class, $submission]);

        $validated = $request->validate([
            'score' => 'required|numeric|between:0,100',
            'feedback' => 'nullable|string',
        ]);

        $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => auth()->id(),
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        return back()->with('success', 'Nilai dan umpan balik berhasil disimpan.');
    }
}
