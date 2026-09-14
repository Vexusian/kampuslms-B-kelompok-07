<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User; // Diperlukan untuk validasi lecturer_id
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // 1. INDEX: Menampilkan semua mata kuliah dari database
    public function index()
    {
        // Mengambil semua data dari tabel courses, bukan array lagi
        $courses = Course::with('lecturer')->get(); 
        return view('courses.index', compact('courses'));
    }

    // 2. CREATE: Menampilkan form untuk menambah mata kuliah
    public function create()
    {
        // Ambil daftar dosen untuk dropdown di form
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.create', compact('lecturers'));
    }

    // 3. STORE: Menyimpan data baru ke database
    public function store(Request $request)
    {
        // Validasi data dari form (sangat penting untuk keamanan)
        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'status' => 'required|in:draft,active,archived',
            'lecturer_id' => 'required|exists:users,id',
        ]);

        // Simpan ke database
        Course::create($validated);

        return redirect()->route('courses.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // 4. SHOW: Menampilkan detail satu mata kuliah
    public function show(Course $course)
    {
        // Laravel otomatis mencari Course berdasarkan ID di URL (Route Model Binding)
        $course->load('lecturer', 'students', 'assignments');
        return view('courses.show', compact('course'));
    }

    // 5. EDIT: Menampilkan form edit untuk data yang sudah ada
    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.edit', compact('course', 'lecturers'));
    }

    // 6. UPDATE: Memperbarui data di database
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code,' . $course->id . '|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'status' => 'required|in:draft,active,archived',
            'lecturer_id' => 'required|exists:users,id',
        ]);

        $course->update($validated);

        return redirect()->route('courses.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    // 7. DESTROY: Menghapus data dari database
    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}