<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard umum KampusLMS.
     */
    public function index()
    {
        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'active')->count();
        $draftCourses = Course::where('status', 'draft')->count();
        $totalLecturers = User::where('role', 'dosen')->count();
        $totalStudents = User::where('role', 'mahasiswa')->count();
        $totalMaterials = Material::count();
        $totalAssignments = Assignment::count();

        // 5 mata kuliah terbaru beserta relasi dosen pengampu
        $recentCourses = Course::with('lecturer')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalCourses',
            'activeCourses',
            'draftCourses',
            'totalLecturers',
            'totalStudents',
            'totalMaterials',
            'totalAssignments',
            'recentCourses'
        ));
    }
}
