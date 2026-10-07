<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * -------------------------------------------------------------------------
     * Display a paginated list of courses with search & filter.
     * Menerapkan query-level scoping sesuai Sub-CPMK Minggu 7:
     * - Admin melihat seluruh mata kuliah.
     * - Dosen hanya melihat mata kuliah yang diampunya.
     * - Mahasiswa hanya melihat mata kuliah yang diikutinya.
     * -------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'           => 'nullable|string|max:255',
            'status'      => 'nullable|in:draft,active,archived',
            'lecturer_id' => 'nullable|integer|exists:users,id',
            'page'        => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();

        // Penyaringan di level query (Database Scoping) mencegah kebocoran daftar
        if (!$user) {
            $query = Course::where('status', 'active');
        } else {
            $query = (match ($user->role) {
                'admin'     => Course::query(),
                'dosen'     => $user->taughtCourses(),
                'mahasiswa' => $user->courses(),
                default     => abort(403, 'Peran tidak dikenali.'),
            });
        }

        // Eager load lecturer untuk mencegah N+1
        $query->with('lecturer');

        // Pencarian (Search) dengan parameter binding
        if (!empty($validated['q'])) {
            $search = $validated['q'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        // Filter status
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter lecturer
        if (!empty($validated['lecturer_id'])) {
            $query->where('lecturer_id', $validated['lecturer_id']);
        }

        $courses = $query->orderBy('id', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    /**
     * Form tambah mata kuliah baru (Hanya Admin).
     */
    public function create()
    {
        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.create', compact('lecturers'));
    }

    /**
     * Simpan mata kuliah baru.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $course = Course::create($request->validated());

        return redirect()
            ->route('courses.index')
            ->with('success', "Mata kuliah '{$course->name}' berhasil ditambahkan.");
    }

    /**
     * Tampilkan detail satu mata kuliah.
     */
    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load(['lecturer', 'students', 'assignments', 'materials']);

        // Data mahasiswa untuk dropdown enrollment (khusus admin/dosen)
        $availableStudents = collect();
        if (auth()->check() && (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))) {
            $enrolledIds = $course->students->pluck('id');
            $availableStudents = User::where('role', 'mahasiswa')
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->get();
        }

        return view('courses.show', compact('course', 'availableStudents'));
    }

    /**
     * Form edit mata kuliah.
     */
    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.edit', compact('course', 'lecturers'));
    }

    /**
     * Perbarui data mata kuliah.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Mata kuliah '{$course->name}' berhasil diperbarui.");
    }

    /**
     * Hapus mata kuliah (Hanya Admin).
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }

    /**
     * Daftarkan mahasiswa ke mata kuliah (Enrollment untuk UTS Demo).
     */
    public function enroll(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('enroll', $course);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $student = User::where('id', $validated['student_id'])->where('role', 'mahasiswa')->firstOrFail();

        if (!$course->students()->where('users.id', $student->id)->exists()) {
            $course->students()->attach($student->id, ['enrolled_at' => now()]);
        }

        return redirect()->route('courses.show', $course)
            ->with('success', "Mahasiswa {$student->name} ({$student->nim_nip}) berhasil didaftarkan ke mata kuliah.");
    }

    /**
     * Keluarkan mahasiswa dari mata kuliah (Unenrollment).
     */
    public function unenroll(Course $course, User $student): RedirectResponse
    {
        Gate::authorize('enroll', $course);

        $course->students()->detach($student->id);

        return redirect()->route('courses.show', $course)
            ->with('success', "Mahasiswa {$student->name} berhasil dikeluarkan dari mata kuliah.");
    }
}