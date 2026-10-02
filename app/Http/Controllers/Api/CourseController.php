<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * Check if user is authorized to access course.
     */
    protected function authorizeCourseAccess(User $user, Course $course): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && $course->lecturer_id === $user->id) {
            return;
        }

        if ($user->role === 'mahasiswa' && $course->students()->where('user_id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
    }

    /**
     * Display a listing of courses scoped by role.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = match ($user->role) {
            'admin' => Course::query(),
            'dosen' => Course::where('lecturer_id', $user->id),
            'mahasiswa' => $user->courses(),
            default => abort(403, 'Anda tidak memiliki akses ke sumber daya ini.'),
        };

        $courses = $query->with('lecturer')
            ->withCount(['materials', 'assignments', 'students'])
            ->paginate(15);

        return CourseResource::collection($courses);
    }

    /**
     * Display the specified course detail.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $course->load('lecturer')
            ->loadCount(['materials', 'assignments', 'students']);

        return new CourseResource($course);
    }

    /**
     * Display materials for the specified course.
     */
    public function materials(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $materials = $course->materials()
            ->latest()
            ->paginate(15);

        return MaterialResource::collection($materials);
    }

    /**
     * Display assignments for the specified course.
     */
    public function assignments(Request $request, Course $course): AnonymousResourceCollection
    {
        $user = $request->user();
        $this->authorizeCourseAccess($user, $course);

        $query = $course->assignments()->with('creator')->withCount('submissions');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif ($user->role === 'mahasiswa') {
            $query->where('status', 'published');
        }

        $assignments = $query->latest('due_at')->paginate(15);

        return AssignmentResource::collection($assignments);
    }
}
