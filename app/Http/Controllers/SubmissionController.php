<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar pengumpulan tugas untuk sebuah penugasan.
     */
    public function index(Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            abort_unless(
                $user->role === 'admin' || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id),
                403,
                'Akses ditolak: Anda bukan pengampu penugasan ini.'
            );
        }

        $submissions = $assignment->submissions()->with(['student', 'grade'])->latest()->paginate(15);
        return view('submissions.index', compact('assignment', 'submissions'));
    }

    /**
     * Mengumpulkan tugas (khusus mahasiswa yang terdaftar di mata kuliah terkait).
     */
    public function store(Request $request, Assignment $assignment)
    {
        $user = auth()->user();
        if ($user) {
            $isEnrolled = $assignment->course->students()->where('users.id', $user->id)->exists();
            abort_unless(
                $user->role === 'mahasiswa' && $isEnrolled,
                403,
                'Akses ditolak: Anda tidak terdaftar pada mata kuliah ini.'
            );
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'file_path' => 'nullable|string',
        ]);

        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => $user ? $user->id : 1],
            array_merge($validated, [
                'submitted_at' => now(),
            ])
        );

        return redirect()->route('submissions.show', $submission)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    /**
     * Menampilkan detail satu submission (Titik rawan IDOR utama).
     * Dilindungi dengan pemeriksaan kepemilikan data sementara (Temporary IDOR Protection).
     */
    public function show(Submission $submission)
    {
        $user = auth()->user();
        if ($user) {
            // Pemilik submission, admin, atau dosen pengampu mata kuliah terkait
            abort_unless(
                $submission->user_id === $user->id
                    || $user->role === 'admin'
                    || $submission->assignment->course->lecturer_id === $user->id,
                403,
                'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
            );
        }

        $submission->load(['assignment.course', 'student', 'grade']);
        return view('submissions.show', compact('submission'));
    }
}
