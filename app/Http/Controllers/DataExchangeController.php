<?php

namespace App\Http\Controllers;

use App\Events\GradeSubmitted;
use App\Models\Course;
use App\Models\Grade;
use App\Models\ImportLog;
use App\Models\Submission;
use App\Models\User;
use App\Services\SimpleSpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExchangeController extends Controller
{
    /**
     * Ekspor data mahasiswa (CSV atau XLSX).
     */
    public function exportStudents(Request $request): StreamedResponse
    {
        $format = strtolower($request->query('format', 'csv'));
        $students = User::where('role', 'mahasiswa')->orderBy('name')->get();
        $filename = 'data_mahasiswa_'.date('Ymd_His').'.'.$format;

        $headers = ['Nama', 'Email', 'NIM', 'Tanggal Terdaftar'];
        $rows = [];

        foreach ($students as $student) {
            $rows[] = [
                $student->name,
                $student->email,
                $student->nim_nip ?? '',
                $student->created_at?->format('Y-m-d H:i') ?? '',
            ];
        }

        if ($format === 'xlsx') {
            return SimpleSpreadsheetService::streamXlsx($headers, $rows, $filename);
        }

        return SimpleSpreadsheetService::streamCsv($headers, $rows, $filename);
    }

    /**
     * Unduh template impor data mahasiswa (CSV atau XLSX).
     */
    public function studentTemplate(Request $request): StreamedResponse
    {
        $format = strtolower($request->query('format', 'csv'));
        $filename = 'template_import_mahasiswa.'.$format;

        $headers = ['Nama', 'Email', 'NIM', 'Password'];
        $rows = [
            ['Ahmad Dahlan', 'ahmad@kampus.ac.id', '20261001', 'password123'],
            ['Siti Aminah', 'siti@kampus.ac.id', '20261002', 'password123'],
        ];

        if ($format === 'xlsx') {
            return SimpleSpreadsheetService::streamXlsx($headers, $rows, $filename);
        }

        return SimpleSpreadsheetService::streamCsv($headers, $rows, $filename);
    }

    /**
     * Impor data mahasiswa dari berkas CSV atau XLSX.
     */
    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:2048'],
        ]);

        $file = $request->file('file');
        $rawRows = SimpleSpreadsheetService::readSpreadsheet(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        if (empty($rawRows)) {
            return back()->with('error', 'Berkas impor kosong atau tidak dapat dibaca.');
        }

        $header = array_shift($rawRows);
        $columns = array_map(fn ($col) => strtolower(trim((string) $col)), $header);

        $nameIdx = array_search('nama', $columns) !== false ? array_search('nama', $columns) : array_search('name', $columns);
        $emailIdx = array_search('email', $columns);
        $nimIdx = array_search('nim', $columns) !== false ? array_search('nim', $columns) : array_search('nim_nip', $columns);
        $passIdx = array_search('password', $columns);

        if ($nameIdx === false || $emailIdx === false) {
            return back()->with('error', 'Format berkas tidak valid. Kolom "Nama" dan "Email" wajib ada.');
        }

        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($rawRows as $row) {
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $totalRows++;
            $name = trim((string) ($row[$nameIdx] ?? ''));
            $email = trim((string) ($row[$emailIdx] ?? ''));
            $nim = $nimIdx !== false ? trim((string) ($row[$nimIdx] ?? '')) : null;
            $password = $passIdx !== false ? trim((string) ($row[$passIdx] ?? '')) : '';

            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Nama atau format email tidak valid ({$email})";

                continue;
            }

            try {
                $user = User::withTrashed()->where('email', $email)->first();
                if ($user) {
                    if ($user->role !== 'mahasiswa') {
                        $errorCount++;
                        $errors[] = "Baris #{$totalRows}: Email {$email} terdaftar sebagai {$user->role} dan tidak dapat diubah.";

                        continue;
                    }
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->name = $name;
                    if ($nim) {
                        $user->nim_nip = $nim;
                    }
                    if ($password !== '') {
                        $user->password = Hash::make($password);
                    }
                    $user->save();
                } else {
                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'nim_nip' => $nim ?: null,
                        'role' => 'mahasiswa',
                        'password' => Hash::make($password !== '' ? $password : 'password123'),
                    ]);
                }
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: {$e->getMessage()}";
            }
        }

        ImportLog::create([
            'user_id' => auth()->id(),
            'type' => 'students',
            'filename' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'status' => $errorCount === 0 ? 'completed' : ($successCount > 0 ? 'partial' : 'failed'),
            'notes' => ! empty($errors) ? implode('; ', array_slice($errors, 0, 5)) : null,
        ]);

        $message = "Impor data mahasiswa selesai. Berhasil: {$successCount}, Gagal: {$errorCount}.";

        return back()->with($errorCount === 0 ? 'success' : 'warning', $message);
    }

    /**
     * Ekspor rekapitulasi nilai mata kuliah (CSV atau XLSX).
     */
    public function exportGrades(Course $course, Request $request): StreamedResponse
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && ($user->role !== 'dosen' || $course->lecturer_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengekspor nilai kelas ini.');
        }

        $format = strtolower($request->query('format', 'csv'));
        $assignments = $course->assignments()->with('submissions.grade')->orderBy('id')->get();
        $students = $course->students()->orderBy('name')->get();
        $filename = 'nilai_'.$course->code.'_'.date('Ymd_His').'.'.$format;

        // Hitung nilai akhir per mahasiswa
        $studentFinalScores = [];
        foreach ($students as $student) {
            $totalPercent = 0;
            $assignmentCount = $assignments->count();

            if ($assignmentCount > 0) {
                foreach ($assignments as $assignment) {
                    $submission = $assignment->submissions->firstWhere('user_id', $student->id);
                    $score = $submission?->grade?->score ?? 0;
                    $maxScore = $assignment->max_score > 0 ? $assignment->max_score : 100;
                    $totalPercent += ($score / $maxScore) * 100;
                }
                $finalScore = round($totalPercent / $assignmentCount, 2);
            } else {
                $finalScore = 0;
            }

            $studentFinalScores[$student->id] = [
                'final_score' => $finalScore,
                'grade_letter' => SimpleSpreadsheetService::getGradeLetter($finalScore),
            ];
        }

        $headers = ['ID Tugas', 'Judul Tugas', 'NIM', 'Nama Mahasiswa', 'Email Mahasiswa', 'Nilai', 'Feedback', 'Nilai Akhir MK', 'Huruf Mutu'];
        $rows = [];

        foreach ($assignments as $assignment) {
            $submissions = $assignment->submissions->keyBy('user_id');

            foreach ($students as $student) {
                $submission = $submissions->get($student->id);
                $grade = $submission?->grade;
                $summary = $studentFinalScores[$student->id] ?? ['final_score' => 0, 'grade_letter' => 'E'];

                $rows[] = [
                    $assignment->id,
                    $assignment->title,
                    $student->nim_nip ?? '',
                    $student->name,
                    $student->email,
                    $grade ? $grade->score : '',
                    $grade ? ($grade->feedback ?? '') : '',
                    $summary['final_score'],
                    $summary['grade_letter'],
                ];
            }
        }

        if ($format === 'xlsx') {
            return SimpleSpreadsheetService::streamXlsx($headers, $rows, $filename);
        }

        return SimpleSpreadsheetService::streamCsv($headers, $rows, $filename);
    }

    /**
     * Unduh template impor nilai (CSV atau XLSX).
     */
    public function gradesTemplate(Course $course, Request $request): StreamedResponse
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && ($user->role !== 'dosen' || $course->lecturer_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $format = strtolower($request->query('format', 'csv'));
        $assignments = $course->assignments()->orderBy('id')->get();
        $students = $course->students()->orderBy('name')->get();
        $filename = 'template_nilai_'.$course->code.'.'.$format;

        $headers = ['ID Tugas', 'Judul Tugas', 'NIM', 'Nama Mahasiswa', 'Email Mahasiswa', 'Nilai', 'Feedback'];
        $rows = [];

        if ($assignments->isNotEmpty() && $students->isNotEmpty()) {
            foreach ($assignments as $assignment) {
                foreach ($students as $student) {
                    $rows[] = [
                        $assignment->id,
                        $assignment->title,
                        $student->nim_nip ?? '',
                        $student->name,
                        $student->email,
                        '',
                        '',
                    ];
                }
            }
        } else {
            $rows[] = [1, 'Tugas 1', '20261001', 'Ahmad Dahlan', 'ahmad@kampus.ac.id', '85', 'Kerja bagus'];
        }

        if ($format === 'xlsx') {
            return SimpleSpreadsheetService::streamXlsx($headers, $rows, $filename);
        }

        return SimpleSpreadsheetService::streamCsv($headers, $rows, $filename);
    }

    /**
     * Impor nilai mata kuliah dari berkas CSV atau XLSX.
     */
    public function importGrades(Request $request, Course $course): RedirectResponse
    {
        $user = auth()->user();
        if ($user->role !== 'dosen' || $course->lecturer_id !== $user->id) {
            abort(403, 'Hanya dosen pengampu mata kuliah ini yang berhak mengimpor nilai.');
        }

        // Cek penguncian nilai pada mata kuliah yang diarsipkan
        if ($course->status === 'archived') {
            return back()->with('error', 'Mata kuliah telah diarsipkan. Pengubahan nilai dikunci.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:2048'],
        ]);

        $file = $request->file('file');
        $rawRows = SimpleSpreadsheetService::readSpreadsheet(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        if (empty($rawRows)) {
            return back()->with('error', 'Berkas impor kosong atau tidak dapat dibaca.');
        }

        $header = array_shift($rawRows);
        $columns = array_map(fn ($col) => strtolower(trim((string) $col)), $header);

        $assignmentIdx = array_search('id tugas', $columns) !== false ? array_search('id tugas', $columns) : array_search('assignment_id', $columns);
        $emailIdx = array_search('email mahasiswa', $columns) !== false ? array_search('email mahasiswa', $columns) : array_search('email', $columns);
        $nimIdx = array_search('nim', $columns) !== false ? array_search('nim', $columns) : array_search('nim_nip', $columns);
        $scoreIdx = array_search('nilai', $columns) !== false ? array_search('nilai', $columns) : array_search('score', $columns);
        $feedbackIdx = array_search('feedback', $columns) !== false ? array_search('feedback', $columns) : array_search('catatan', $columns);

        if ($assignmentIdx === false || ($emailIdx === false && $nimIdx === false) || $scoreIdx === false) {
            return back()->with('error', 'Format berkas tidak valid. Wajib ada kolom ID Tugas, Nilai, dan Email/NIM.');
        }

        $courseAssignments = $course->assignments()->get()->keyBy('id');
        $enrolledStudentIds = $course->students()->pluck('users.id')->flip();

        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($rawRows as $row) {
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $totalRows++;
            $assignmentId = trim((string) ($row[$assignmentIdx] ?? ''));
            $email = $emailIdx !== false ? trim((string) ($row[$emailIdx] ?? '')) : '';
            $nim = $nimIdx !== false ? trim((string) ($row[$nimIdx] ?? '')) : '';
            $scoreVal = trim((string) ($row[$scoreIdx] ?? ''));
            $feedback = $feedbackIdx !== false ? trim((string) ($row[$feedbackIdx] ?? '')) : null;

            if ($scoreVal === '') {
                continue;
            }

            if (! is_numeric($scoreVal) || (float) $scoreVal < 0) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Nilai '{$scoreVal}' tidak valid";

                continue;
            }

            $assignment = $courseAssignments->get($assignmentId);
            if (! $assignment) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: ID Tugas '{$assignmentId}' tidak valid pada mata kuliah ini";

                continue;
            }

            if ((float) $scoreVal > $assignment->max_score) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Nilai '{$scoreVal}' melebihi skor maksimal tugas (Maks: {$assignment->max_score})";

                continue;
            }

            $studentQuery = User::where('role', 'mahasiswa');
            if ($email !== '') {
                $studentQuery->where('email', $email);
            } elseif ($nim !== '') {
                $studentQuery->where('nim_nip', $nim);
            } else {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Email atau NIM harus diisi";

                continue;
            }

            $student = $studentQuery->first();
            if (! $student) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Mahasiswa tidak ditemukan ({$email} / {$nim})";

                continue;
            }

            if (! isset($enrolledStudentIds[$student->id])) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: Mahasiswa ({$student->name}) tidak terdaftar pada mata kuliah ini";

                continue;
            }

            try {
                DB::transaction(function () use ($assignment, $student, $scoreVal, $feedback, $user) {
                    $submission = Submission::firstOrCreate(
                        [
                            'assignment_id' => $assignment->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'submitted_at' => now(),
                            'content' => 'Pengumpulan dicatat via impor nilai.',
                        ]
                    );

                    $grade = Grade::updateOrCreate(
                        ['submission_id' => $submission->id],
                        [
                            'graded_by' => $user->id,
                            'score' => (int) $scoreVal,
                            'feedback' => $feedback ?: null,
                            'graded_at' => now(),
                        ]
                    );

                    GradeSubmitted::dispatch($grade);
                });

                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $errors[] = "Baris #{$totalRows}: {$e->getMessage()}";
            }
        }

        ImportLog::create([
            'user_id' => $user->id,
            'type' => 'grades',
            'filename' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'status' => $errorCount === 0 ? 'completed' : ($successCount > 0 ? 'partial' : 'failed'),
            'notes' => ! empty($errors) ? implode('; ', array_slice($errors, 0, 5)) : null,
        ]);

        $message = "Impor nilai selesai. Berhasil: {$successCount}, Gagal: {$errorCount}.";

        return back()->with($errorCount === 0 ? 'success' : 'warning', $message);
    }
}
