<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Upsert grade for a submission (Dosen pemilik only).
     */
    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();

        $courseLecturerId = $submission->assignment->course->lecturer_id;
        if ($user->role !== 'admin' && $courseLecturerId !== $user->id) {
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

        $statusCode = $gradeExists ? 200 : 201;

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode($statusCode);
    }
}
