<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    /**
     * Check if user is the lecturer owner of the assignment or admin.
     */
    protected function authorizeLecturerOwner(User $user, Assignment $assignment): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && ($assignment->course->lecturer_id === $user->id || $assignment->created_by === $user->id)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
    }

    /**
     * Store a newly created assignment (Dosen only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'dosen' && $user->role !== 'admin') {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'due_at' => 'required|date',
            'max_score' => 'nullable|integer|min:1|max:1000',
            'allow_late' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($user->role === 'dosen' && $course->lecturer_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $validated['created_by'] = $user->id;
        $validated['max_score'] = $validated['max_score'] ?? 100;
        $validated['allow_late'] = $validated['allow_late'] ?? true;
        $validated['status'] = $validated['status'] ?? 'draft';

        $assignment = Assignment::create($validated);
        $assignment->load(['course', 'creator']);

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an assignment (Dosen pemilik only).
     */
    public function update(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorizeLecturerOwner($request->user(), $assignment);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'instructions' => 'sometimes|required|string',
            'due_at' => 'sometimes|required|date',
            'max_score' => 'nullable|integer|min:1|max:1000',
            'allow_late' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published',
        ]);

        $assignment->update($validated);
        $assignment->load(['course', 'creator']);

        return new AssignmentResource($assignment);
    }

    /**
     * Delete an assignment (Dosen pemilik only).
     */
    public function destroy(Request $request, Assignment $assignment): Response
    {
        $this->authorizeLecturerOwner($request->user(), $assignment);

        $assignment->delete();

        return response()->noContent();
    }

    /**
     * List all submissions for an assignment (Dosen pemilik only).
     */
    public function submissions(Request $request, Assignment $assignment): AnonymousResourceCollection
    {
        $this->authorizeLecturerOwner($request->user(), $assignment);

        $submissions = $assignment->submissions()
            ->with(['student', 'grade.grader'])
            ->latest('submitted_at')
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    /**
     * Submit an assignment (Mahasiswa terdaftar only).
     */
    public function storeSubmission(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        // Must be enrolled student
        $isEnrolled = $assignment->course->students()->where('user_id', $user->id)->exists();
        if (! $isEnrolled) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $request->validate([
            'content' => 'required_without:file|nullable|string',
            'file' => 'required_without:content|nullable|file|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions');
        }

        $existing = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->update([
                'content' => $request->content ?? $existing->content,
                'file_path' => $filePath ?? $existing->file_path,
                'submitted_at' => now(),
            ]);
            $submission = $existing;
            $statusCode = 200;
        } else {
            $submission = Submission::create([
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'content' => $request->content,
                'file_path' => $filePath,
                'submitted_at' => now(),
            ]);
            $statusCode = 201;
        }

        $submission->load(['student', 'assignment', 'grade']);

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode($statusCode);
    }
}
