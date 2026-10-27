<?php

namespace App\Http\Controllers\Api;

use App\Events\GradeSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Display a single submission.
     * Accessible by: Admin, Dosen pengampu mata kuliah, atau Mahasiswa pemilik submission.
     */
    public function show(Request $request, Submission $submission): SubmissionResource
    {
        $user = $request->user();

        $courseLecturerId = $submission->assignment->course->lecturer_id;
        $isOwner = $submission->user_id === $user->id;
        $isLecturer = $user->role === 'dosen' && $courseLecturerId === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isAdmin && ! $isLecturer && ! $isOwner) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $submission->load(['student', 'assignment.course', 'grade.grader']);

        return new SubmissionResource($submission);
    }

    /**
     * Upsert grade for a submission (Dosen pemilik only).
     */
    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();

        $courseLecturerId = $submission->assignment->course->lecturer_id;
        if ($user->role !== 'dosen' || $courseLecturerId !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $gradeExists = Grade::where('submission_id', $submission->id)->exists();

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $user->id,
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        $grade->load('grader');

        GradeSubmitted::dispatch($grade);

        $statusCode = $gradeExists ? 200 : 201;

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode($statusCode);
    }

    /**
     * Download submission file dari disk privat.
     */
    public function download(Submission $submission)
    {
        \Illuminate\Support\Facades\Gate::authorize('download', $submission);

        if (! $submission->file_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($submission->file_path)) {
            abort(404, 'Berkas submission tidak ditemukan pada penyimpanan privat.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_filename ?? basename($submission->file_path)
        );
    }
}
