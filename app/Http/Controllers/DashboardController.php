<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard terpersonalisasi sesuai peran pengguna (Admin, Dosen, Mahasiswa).
     * Menerapkan pembatasan data agar mahasiswa hanya melihat mata kuliah/tugas miliknya,
     * dan dosen hanya melihat mata kuliah yang diampunya.
     */
    public function index()
    {
        $user = auth()->user();

        // Pengguna belum terautentikasi (wajib login terlebih dahulu)
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Dashboard Mahasiswa (Khusus mata kuliah dan tugas yang diikuti)
        if ($user->role === 'mahasiswa') {
            $enrolledCoursesQuery = $user->courses();
            $totalCourses = $enrolledCoursesQuery->count();
            $activeCourses = (clone $enrolledCoursesQuery)->where('status', 'active')->count();
            $draftCourses = 0;
            $totalLecturers = (clone $enrolledCoursesQuery)->distinct('lecturer_id')->count('lecturer_id');
            $totalStudents = 0;

            $enrolledCourseIds = $user->courses()->pluck('courses.id');
            $totalMaterials = Material::whereIn('course_id', $enrolledCourseIds)->count();
            $totalAssignments = Assignment::whereIn('course_id', $enrolledCourseIds)->count();
            $totalSubmissions = $user->submissions()->count();

            $recentCourses = $user->courses()->with('lecturer')->latest()->take(5)->get();
            $recentAssignments = Assignment::whereIn('course_id', $enrolledCourseIds)
                ->with(['course', 'submissions' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                ->orderBy('due_at', 'asc')
                ->take(5)
                ->get();

            return view('dashboard', compact(
                'user',
                'totalCourses',
                'activeCourses',
                'draftCourses',
                'totalLecturers',
                'totalStudents',
                'totalMaterials',
                'totalAssignments',
                'totalSubmissions',
                'recentCourses',
                'recentAssignments'
            ));
        }

        // 3. Dashboard Dosen (Khusus mata kuliah yang diampu)
        if ($user->role === 'dosen') {
            $taughtCoursesQuery = $user->taughtCourses();
            $totalCourses = $taughtCoursesQuery->count();
            $activeCourses = (clone $taughtCoursesQuery)->where('status', 'active')->count();
            $draftCourses = (clone $taughtCoursesQuery)->where('status', 'draft')->count();
            $totalLecturers = 1;

            $taughtCourseIds = $user->taughtCourses()->pluck('id');
            $totalStudents = User::whereHas('courses', function ($q) use ($taughtCourseIds) {
                $q->whereIn('courses.id', $taughtCourseIds);
            })->count();

            $totalMaterials = Material::whereIn('course_id', $taughtCourseIds)->count();
            $totalAssignments = Assignment::whereIn('course_id', $taughtCourseIds)->count();
            $totalSubmissions = Submission::whereHas('assignment', function ($q) use ($taughtCourseIds) {
                $q->whereIn('course_id', $taughtCourseIds);
            })->count();

            $recentCourses = $user->taughtCourses()->with('lecturer')->latest()->take(5)->get();
            $recentAssignments = Assignment::whereIn('course_id', $taughtCourseIds)
                ->with('course')
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard', compact(
                'user',
                'totalCourses',
                'activeCourses',
                'draftCourses',
                'totalLecturers',
                'totalStudents',
                'totalMaterials',
                'totalAssignments',
                'totalSubmissions',
                'recentCourses',
                'recentAssignments'
            ));
        }

        // 4. Dashboard Admin (Akses Global Seluruh LMS)
        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'active')->count();
        $draftCourses = Course::where('status', 'draft')->count();
        $totalLecturers = User::where('role', 'dosen')->count();
        $totalStudents = User::where('role', 'mahasiswa')->count();
        $totalMaterials = Material::count();
        $totalAssignments = Assignment::count();
        $totalSubmissions = Submission::count();

        $recentCourses = Course::with('lecturer')
            ->latest()
            ->take(5)
            ->get();
        $recentAssignments = Assignment::with('course')->latest()->take(5)->get();

        return view('dashboard', compact(
            'user',
            'totalCourses',
            'activeCourses',
            'draftCourses',
            'totalLecturers',
            'totalStudents',
            'totalMaterials',
            'totalAssignments',
            'totalSubmissions',
            'recentCourses',
            'recentAssignments'
        ));
    }
}
