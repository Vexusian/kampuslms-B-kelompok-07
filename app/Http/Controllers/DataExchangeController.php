<?php

namespace App\Http\Controllers;

use App\Events\GradeSubmitted;
use App\Models\Course;
use App\Models\Grade;
use App\Models\ImportLog;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExchangeController extends Controller
{
    public function exportStudents(): StreamedResponse
    {
        $students = User::where('role', 'mahasiswa')->orderBy('name')->get();
        $filename = 'data_mahasiswa_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Nama', 'Email', 'NIM', 'Tanggal Terdaftar']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->name,
                    $student->email,
                    $student->nim_nip ?? '',
                    $student->created_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function studentTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Nama', 'Email', 'NIM', 'Password']);
            fputcsv($handle, ['Ahmad Dahlan', 'ahmad@kampus.ac.id', '20261001', 'password123']);
            fputcsv($handle, ['Siti Aminah', 'siti@kampus.ac.id', '20261002', 'password123']);
            fclose($handle);
        }, 'template_import_mahasiswa.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Gagal membuka berkas CSV.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'Berkas CSV kosong.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $columns = array_map(fn ($col) => strtolower(trim($col)), $header);

        $nameIdx = array_search('nama', $columns) !== false ? array_search('nama', $columns) : array_search('name', $columns);
        $emailIdx = array_search('email', $columns);
        $nimIdx = array_search('nim', $columns) !== false ? array_search('nim', $columns) : array_search('nim_nip', $columns);
        $passIdx = array_search('password', $columns);

        if ($nameIdx === false || $emailIdx === false) {
            fclose($handle);

            return back()->with('error', 'Format CSV tidak valid. Kolom "Nama" dan "Email" wajib ada.');
        }

        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row, fn ($val) => trim($val) !== ''))) {
                continue;
            }

            $totalRows++;
            $name = trim($row[$nameIdx] ?? '');
            $email = trim($row[$emailIdx] ?? '');
            $nim = $nimIdx !== false ? trim($row[$nimIdx] ?? '') : null;
            $password = $passIdx !== false ? trim($row[$passIdx] ?? '') : '';

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

        fclose($handle);

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

    public function exportGrades(Course $course): StreamedResponse
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && ($user->role !== 'dosen' || $course->lecturer_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengekspor nilai kelas ini.');
        }

        $assignments = $course->assignments()->with('submissions.grade')->orderBy('id')->get();
        $students = $course->students()->orderBy('name')->get();
        $filename = 'nilai_'.$course->code.'_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($assignments, $students) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID Tugas', 'Judul Tugas', 'NIM', 'Nama Mahasiswa', 'Email Mahasiswa', 'Nilai', 'Feedback']);

            foreach ($assignments as $assignment) {
                $submissions = $assignment->submissions->keyBy('user_id');

                foreach ($students as $student) {
                    $submission = $submissions->get($student->id);
                    $grade = $submission?->grade;

                    fputcsv($handle, [
                        $assignment->id,
                        $assignment->title,
                        $student->nim_nip ?? '',
                        $student->name,
                        $student->email,
                        $grade ? $grade->score : '',
                        $grade ? ($grade->feedback ?? '') : '',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function gradesTemplate(Course $course): StreamedResponse
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && ($user->role !== 'dosen' || $course->lecturer_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $assignments = $course->assignments()->orderBy('id')->get();
        $students = $course->students()->orderBy('name')->get();

        return response()->streamDownload(function () use ($assignments, $students) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID Tugas', 'Judul Tugas', 'NIM', 'Nama Mahasiswa', 'Email Mahasiswa', 'Nilai', 'Feedback']);

            if ($assignments->isNotEmpty() && $students->isNotEmpty()) {
                foreach ($assignments as $assignment) {
                    foreach ($students as $student) {
                        fputcsv($handle, [
                            $assignment->id,
                            $assignment->title,
                            $student->nim_nip ?? '',
                            $student->name,
                            $student->email,
                            '',
                            '',
                        ]);
                    }
                }
            } else {
                fputcsv($handle, [1, 'Tugas 1', '20261001', 'Ahmad Dahlan', 'ahmad@kampus.ac.id', '85', 'Kerja bagus']);
            }

            fclose($handle);
        }, 'template_nilai_'.$course->code.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importGrades(Request $request, Course $course): RedirectResponse
    {
        $user = auth()->user();
        if ($user->role !== 'dosen' || $course->lecturer_id !== $user->id) {
            abort(403, 'Hanya dosen pengampu mata kuliah ini yang berhak mengimpor nilai.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Gagal membuka berkas CSV.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'Berkas CSV kosong.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $columns = array_map(fn ($col) => strtolower(trim($col)), $header);

        $assignmentIdx = array_search('id tugas', $columns) !== false ? array_search('id tugas', $columns) : array_search('assignment_id', $columns);
        $emailIdx = array_search('email mahasiswa', $columns) !== false ? array_search('email mahasiswa', $columns) : array_search('email', $columns);
        $nimIdx = array_search('nim', $columns) !== false ? array_search('nim', $columns) : array_search('nim_nip', $columns);
        $scoreIdx = array_search('nilai', $columns) !== false ? array_search('nilai', $columns) : array_search('score', $columns);
        $feedbackIdx = array_search('feedback', $columns) !== false ? array_search('feedback', $columns) : array_search('catatan', $columns);

        if ($assignmentIdx === false || ($emailIdx === false && $nimIdx === false) || $scoreIdx === false) {
            fclose($handle);

            return back()->with('error', 'Format CSV tidak valid. Wajib ada kolom ID Tugas, Nilai, dan Email/NIM.');
        }

        $courseAssignments = $course->assignments()->get()->keyBy('id');
        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row, fn ($val) => trim($val) !== ''))) {
                continue;
            }

            $totalRows++;
            $assignmentId = trim($row[$assignmentIdx] ?? '');
            $email = $emailIdx !== false ? trim($row[$emailIdx] ?? '') : '';
            $nim = $nimIdx !== false ? trim($row[$nimIdx] ?? '') : '';
            $scoreVal = trim($row[$scoreIdx] ?? '');
            $feedback = $feedbackIdx !== false ? trim($row[$feedbackIdx] ?? '') : null;

            if ($scoreVal === '') {
                continue;
            }

            if (! is_numeric($scoreVal) || (int) $scoreVal < 0) {
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

        fclose($handle);

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
