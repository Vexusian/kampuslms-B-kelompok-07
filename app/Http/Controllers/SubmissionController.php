<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar pengumpulan tugas untuk sebuah penugasan.
     */
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny', [Submission::class, $assignment]);

        $submissions = $assignment->submissions()->with(['student', 'grade'])->latest('submitted_at')->paginate(15);
        return view('submissions.index', compact('assignment', 'submissions'));
    }

    /**
     * Mengumpulkan tugas (mahasiswa terdaftar).
     *
     * Ketentuan:
     * - Berkas disimpan di disk privat ('local' -> storage/app/private).
     * - Nama berkas fisik diacak oleh sistem (hash); nama asli disimpan di original_filename.
     * - Validasi berkas: wajib ada, mimes pdf/doc/docx, maksimal 5 MB (5120 KB).
     * - Perhitungan is_late dihitung di server terhadap due_at.
     * - Terapkan updateOrCreate dengan composite key (assignment_id, user_id).
     * - Berkas lama dihapus dari storage jika melakukan pengumpulan ulang (re-submit).
     */
    public function store(Request $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        $user = $request->user();

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'content' => ['nullable', 'string'],
        ], [
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.mimes' => 'Format berkas harus berupa PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $storedPath = null;
        $originalFilename = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $storedPath = $file->store('submissions/' . $assignment->id, 'local');
        }

        // Hitung status keterlambatan di sisi server berdasarkan due_at
        $isLate = $assignment->due_at ? now()->greaterThan($assignment->due_at) : false;

        // Cari submission lama jika ada, lalu hapus berkas fisik lamanya agar tidak menjadi berkas yatim (orphan)
        $existing = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        // Terapkan updateOrCreate sesuai unique composite (assignment_id, user_id)
        $submission = Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'content' => $validated['content'] ?? null,
                'file_path' => $storedPath,
                'original_filename' => $originalFilename,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ]
        );

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Tugas berhasil dikumpulkan' . ($existing ? ' ulang.' : '.'));
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
     * Mengunduh berkas pengumpulan tugas dari disk privat.
     * Menggunakan Gate::authorize() untuk mengecek Policy sebelum streaming file.
     * (Laravel 12: $this->authorize() tidak tersedia di Controller).
     */
    public function download(Submission $submission)
    {
        Gate::authorize('download', $submission);

        if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
            abort(404, 'Berkas submission tidak ditemukan pada disk penyimpanan privat.');
        }

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_filename ?? basename($submission->file_path)
        );
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
