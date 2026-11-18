<?php

namespace App\Http\Controllers;

use App\Events\GradeSubmitted;
use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Requests\StoreSubmissionRequest;
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

        $assignment->loadMissing('course');

        $submissions = $assignment->submissions()->with(['student', 'grade'])->latest('submitted_at')->paginate(15);

        // Hitung total mahasiswa terdaftar langsung di database (tanpa load seluruh model ke RAM)
        $enrolledCount = $assignment->course->students()->count();

        // Filter mahasiswa yang belum mengumpulkan langsung di level database (whereDoesntHave)
        $unsubmittedStudents = $assignment->course->students()
            ->whereDoesntHave('submissions', function ($query) use ($assignment) {
                $query->where('assignment_id', $assignment->id);
            })
            ->orderBy('name')
            ->get();

        return view('submissions.index', compact('assignment', 'submissions', 'enrolledCount', 'unsubmittedStudents'));
    }

    /**
     * Mengumpulkan tugas (mahasiswa terdaftar).
     *
     * Ketentuan:
     * - Berkas disimpan di disk privat ('local' -> storage/app/private).
     * - Nama berkas fisik diacak oleh sistem (hash); nama asli disimpan di original_filename.
     * - Validasi berkas: wajib ada, mimes pdf/doc/docx, maksimal 5 MB (5120 KB).
     * - Perhitungan is_late dihitung di server terhadap due_at.
     * - Tolak jika allow_late bernilai false dan sudah melewati due_at.
     * - Pengumpulan bersifat final (sekali kumpul) dijamin oleh SubmissionPolicy dan composite key (assignment_id, user_id).
     */
    public function store(StoreSubmissionRequest $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        $user = $request->user();
        $validated = $request->validated();

        // Hitung status keterlambatan di sisi server berdasarkan due_at
        $isLate = $assignment->due_at ? now()->greaterThan($assignment->due_at) : false;

        // Tolak jika batas waktu berakhir dan pengumpulan terlambat dilarang (sebelum berkas disimpan)
        if (! $assignment->allow_late && $isLate) {
            return back()->withErrors(['file' => 'Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.'])->withInput();
        }

        $storedPath = null;
        $originalFilename = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $storedPath = $file->store('submissions/' . $assignment->id, 'local');
        }

        // Simpan submission baru (pengumpulan bersifat final, dijamin oleh SubmissionPolicy)
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'content' => $validated['content'] ?? null,
            'file_path' => $storedPath,
            'original_filename' => $originalFilename,
            'submitted_at' => now(),
            'is_late' => $isLate,
        ]);

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Tugas berhasil dikumpulkan.');
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
    public function grade(GradeSubmissionRequest $request, Submission $submission)
    {
        Gate::authorize('create', [Grade::class, $submission]);

        $validated = $request->validated();

        $grade = $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => auth()->id(),
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        // Dispatch event → Listener akan mengirim notifikasi ke mahasiswa via queue
        GradeSubmitted::dispatch($grade);

        return back()->with('success', 'Nilai dan umpan balik berhasil disimpan.');
    }
}
