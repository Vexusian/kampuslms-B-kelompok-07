<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Menampilkan seluruh mata kuliah.
    public function index()
    {
        $courses = Course::with('lecturer')->get();

        return view('cours  es.index', [
            'courses' => $courses,
        ]);
    }

    // Menampilkan form tambah mata kuliah.
    public function create()
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', [
            'lecturers' => $lecturers,
        ]);
    }

    // Menyimpan mata kuliah baru ke database.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // Menampilkan detail satu mata kuliah.
    public function show(Course $course)
    {
        $course->load('lecturer');

        return view('courses.show', [
            'course' => $course,
        ]);
    }

    // Menampilkan form edit.
    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', [
            'course' => $course,
            'lecturers' => $lecturers,
        ]);
    }

    // Mengubah data mata kuliah.
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        $course->update($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    // Menghapus mata kuliah.
    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}