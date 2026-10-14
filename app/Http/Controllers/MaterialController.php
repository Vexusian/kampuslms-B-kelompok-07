<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar materi pada mata kuliah tertentu.
     */
    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $materials = $course->materials()->latest()->paginate(15);
        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Form tambah materi baru untuk suatu mata kuliah.
     */
    public function create(Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    /**
     * Simpan materi baru.
     */
    public function store(StoreMaterialRequest $request, Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        $material = $course->materials()->create($request->validated());

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.materials.show', $material)->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail materi.
     */
    public function show(Material $material)
    {
        Gate::authorize('view', $material);

        return view('materials.show', compact('material'));
    }

    /**
     * Form edit materi.
     */
    public function edit(Material $material)
    {
        Gate::authorize('update', $material);

        return view('materials.edit', compact('material'));
    }

    /**
     * Perbarui materi.
     */
    public function update(UpdateMaterialRequest $request, Material $material)
    {
        Gate::authorize('update', $material);

        $material->update($request->validated());

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }

    /**
     * Hapus materi.
     */
    public function destroy(Material $material)
    {
        Gate::authorize('delete', $material);

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi berhasil dihapus.');
    }
}
