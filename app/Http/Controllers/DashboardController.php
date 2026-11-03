<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard terpersonalisasi sesuai peran pengguna (Admin, Dosen, Mahasiswa).
     * Menerapkan caching pada agregasi metrik statistik dashboard (Minggu 11 BUILD 6)
     * dengan invalidasi otomatis saat data sumber berubah.
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
            $stats = Cache::remember("dashboard.stats.mahasiswa.{$user->id}", now()->addMinutes(30), function () use ($user) {
                $enrolledCoursesQuery = $user->courses();
                $enrolledCourseIds = (clone $enrolledCoursesQuery)->pluck('courses.id');

                return [
                    'totalCourses'     => (clone $enrolledCoursesQuery)->count(),
                    'activeCourses'    => (clone $enrolledCoursesQuery)->where('status', 'active')->count(),
                    'draftCourses'     => 0,
                    'totalLecturers'   => (clone $enrolledCoursesQuery)->distinct('lecturer_id')->count('lecturer_id'),
                    'totalStudents'    => 0,
                    'totalMaterials'   => Material::whereIn('course_id', $enrolledCourseIds)->count(),
                    'totalAssignments' => Assignment::whereIn('course_id', $enrolledCourseIds)->count(),
                    'totalSubmissions' => $user->submissions()->count(),
                ];
            });

            $enrolledCourseIds = $user->courses()->pluck('courses.id');
            $recentCourses = $user->courses()->with('lecturer')->latest()->take(5)->get();
            $recentAssignments = Assignment::whereIn('course_id', $enrolledCourseIds)
                ->with(['course', 'submissions' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                ->orderBy('due_at', 'asc')
                ->take(5)
                ->get();

            return view('dashboard', array_merge([
                'user'              => $user,
                'recentCourses'     => $recentCourses,
                'recentAssignments' => $recentAssignments,
            ], $stats));
        }

        // 3. Dashboard Dosen (Khusus mata kuliah yang diampu)
        if ($user->role === 'dosen') {
            $stats = Cache::remember("dashboard.stats.dosen.{$user->id}", now()->addMinutes(30), function () use ($user) {
                $taughtCoursesQuery = $user->taughtCourses();
                $taughtCourseIds = (clone $taughtCoursesQuery)->pluck('id');

                return [
                    'totalCourses'     => (clone $taughtCoursesQuery)->count(),
                    'activeCourses'    => (clone $taughtCoursesQuery)->where('status', 'active')->count(),
                    'draftCourses'     => (clone $taughtCoursesQuery)->where('status', 'draft')->count(),
                    'totalLecturers'   => 1,
                    'totalStudents'    => User::whereHas('courses', function ($q) use ($taughtCourseIds) {
                        $q->whereIn('courses.id', $taughtCourseIds);
                    })->count(),
                    'totalMaterials'   => Material::whereIn('course_id', $taughtCourseIds)->count(),
                    'totalAssignments' => Assignment::whereIn('course_id', $taughtCourseIds)->count(),
                    'totalSubmissions' => Submission::whereHas('assignment', function ($q) use ($taughtCourseIds) {
                        $q->whereIn('course_id', $taughtCourseIds);
                    })->count(),
                ];
            });

            $taughtCourseIds = $user->taughtCourses()->pluck('id');
            $recentCourses = $user->taughtCourses()->with('lecturer')->latest()->take(5)->get();
            $recentAssignments = Assignment::whereIn('course_id', $taughtCourseIds)
                ->with('course')
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard', array_merge([
                'user'              => $user,
                'recentCourses'     => $recentCourses,
                'recentAssignments' => $recentAssignments,
            ], $stats));
        }

        // 4. Dashboard Admin (Akses Global Seluruh LMS)
        $stats = Cache::remember('dashboard.stats.admin', now()->addMinutes(30), function () {
            return [
                'totalCourses'     => Course::count(),
                'activeCourses'    => Course::where('status', 'active')->count(),
                'draftCourses'     => Course::where('status', 'draft')->count(),
                'totalLecturers'   => User::where('role', 'dosen')->count(),
                'totalStudents'    => User::where('role', 'mahasiswa')->count(),
                'totalMaterials'   => Material::count(),
                'totalAssignments' => Assignment::count(),
                'totalSubmissions' => Submission::count(),
            ];
        });

        $recentCourses = Course::with('lecturer')
            ->latest()
            ->take(5)
            ->get();
        $recentAssignments = Assignment::with('course')->latest()->take(5)->get();

        return view('dashboard', array_merge([
            'user'              => $user,
            'recentCourses'     => $recentCourses,
            'recentAssignments' => $recentAssignments,
        ], $stats));
    }
}
