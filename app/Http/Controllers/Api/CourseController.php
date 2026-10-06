<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

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
     * Display a listing of courses scoped by role (returns view).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = match ($user->role) {
            'admin' => Course::query(),
            'dosen' => Course::where('lecturer_id', $user->id),
            'mahasiswa' => $user->courses(),
            default => abort(403, 'Anda tidak memiliki akses ke sumber daya ini.'),
        };

        $courses = $query->with('lecturer')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    /**
     * Display the specified course detail (returns view).
     */
    public function show(Request $request, Course $course)
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $course->load('lecturer', 'students', 'assignments');

        return view('courses.show', compact('course'));
    }

    /**
     * Display materials for the specified course (returns view).
     */
    public function materials(Request $request, Course $course)
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $materials = $course->materials()->latest()->paginate(15);

        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Display assignments for the specified course (returns view).
     */
    public function assignments(Request $request, Course $course)
    {
        $user = $request->user();
        $this->authorizeCourseAccess($user, $course);

        $query = $course->assignments()->with('creator')->withCount('submissions');

        // Mahasiswa hanya lihat yang published
        if ($user->role === 'mahasiswa') {
            $query->where('status', 'published');
        }

        $assignments = $query->latest('due_at')->paginate(15);

        return view('assignments.index', compact('course', 'assignments'));
    }
}
