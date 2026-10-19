<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Menampilkan daftar materi pada mata kuliah tertentu.
     * Hanya admin, dosen pengampu, dan mahasiswa terdaftar yang diizinkan.
     */
    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $materials = $course->materials()->latest()->paginate(15);
        return view('materials.index', compact('course', 'materials'));
    }

    /**
     * Form tambah materi baru untuk suatu mata kuliah.
     * Hanya admin atau dosen pengampu mata kuliah terkait.
     */
    public function create(Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    /**
     * Simpan materi baru.
     * Ketentuan:
     * - Unggah berkas fisik: PDF, PPTX, DOCX, maks 20 MB (20480 KB), disk privat ('local').
     * - Atau alternatif berupa tautan eksternal (external_url).
     */
    public function store(StoreMaterialRequest $request, Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:file,link'],
            'content' => ['nullable', 'string'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,pptx,docx',
                'max:20480', // Maks 20 MB (20480 KB)
            ],
            'external_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'file.file' => 'Berkas materi yang diunggah tidak valid.',
            'file.mimes' => 'Format berkas harus berupa PDF, PPTX, atau DOCX.',
            'file.max' => 'Ukuran berkas materi maksimal 20 MB.',
            'external_url.url' => 'Format tautan eksternal tidak valid.',
        ]);

        // Tentukan tipe materi: link atau file
        $type = $validated['type'] ?? null;
        if (! $type) {
            $type = ($request->filled('external_url') && ! $request->hasFile('file')) ? 'link' : 'file';
        }

        $storedPath = null;
        $originalName = null;
        $fileSize = null;
        $mimeType = null;
        $externalUrl = null;

        if ($type === 'link') {
            // Validasi tambahan jika tipe tautan dipilih
            if (! $request->filled('external_url')) {
                return back()->withErrors(['external_url' => 'Tautan eksternal wajib diisi untuk materi jenis tautan.'])->withInput();
            }
            $externalUrl = $validated['external_url'];
        } else {
            // Tipe file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $storedPath = $file->store('materials/' . $course->id, 'local');
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getClientMimeType() ?: $file->getMimeType();
            }
        }

        $material = $course->materials()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $type,
            'content' => $validated['content'] ?? null,
            'file_path' => $storedPath,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'external_url' => $externalUrl,
        ]);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.materials.show', $material)
            ->with('success', 'Materi perkuliahan berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail materi perkuliahan.
     * Dilindungi MaterialPolicy (hanya admin, dosen pengampu, atau mahasiswa terdaftar).
     */
    public function show(Material $material)
    {
        Gate::authorize('view', $material);

        $material->load('course.lecturer');
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
     * Jika ada berkas baru diunggah atau tipe diubah ke tautan, berkas lama dihapus dari disk privat.
     */
    public function update(UpdateMaterialRequest $request, Material $material)
    {
        Gate::authorize('update', $material);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:file,link'],
            'content' => ['nullable', 'string'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,pptx,docx',
                'max:20480',
            ],
            'external_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'file.file' => 'Berkas materi yang diunggah tidak valid.',
            'file.mimes' => 'Format berkas harus berupa PDF, PPTX, atau DOCX.',
            'file.max' => 'Ukuran berkas materi maksimal 20 MB.',
            'external_url.url' => 'Format tautan eksternal tidak valid.',
        ]);

        $type = $validated['type'] ?? $material->type ?? 'file';

        $storedPath = $material->file_path;
        $originalName = $material->original_name;
        $fileSize = $material->file_size;
        $mimeType = $material->mime_type;
        $externalUrl = $material->external_url;

        if ($type === 'link') {
            if (! $request->filled('external_url')) {
                return back()->withErrors(['external_url' => 'Tautan eksternal wajib diisi untuk materi jenis tautan.'])->withInput();
            }

            // Hapus berkas fisik lama jika sebelumnya adalah berkas
            if ($material->file_path && Storage::disk('local')->exists($material->file_path)) {
                Storage::disk('local')->delete($material->file_path);
            }

            $storedPath = null;
            $originalName = null;
            $fileSize = null;
            $mimeType = null;
            $externalUrl = $validated['external_url'];
        } else {
            // Tipe file
            if ($request->hasFile('file')) {
                // Hapus berkas fisik lama
                if ($material->file_path && Storage::disk('local')->exists($material->file_path)) {
                    Storage::disk('local')->delete($material->file_path);
                }

                $file = $request->file('file');
                $storedPath = $file->store('materials/' . $material->course_id, 'local');
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getClientMimeType() ?: $file->getMimeType();
            }

            $externalUrl = null;
        }

        $material->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $material->description,
            'type' => $type,
            'content' => $validated['content'] ?? null,
            'file_path' => $storedPath,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'external_url' => $externalUrl,
        ]);

        $role = auth()->user()?->role === 'mahasiswa' ? 'mahasiswa' : 'dosen';
        return redirect()->route($role . '.materials.show', $material)
            ->with('success', 'Materi perkuliahan berhasil diperbarui.');
    }

    /**
     * Hapus materi.
     * Berkas fisik otomatis dihapus melalui event deleting di model Material.
     */
    public function destroy(Material $material)
    {
        Gate::authorize('delete', $material);

        $course = $material->course;
        $material->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Materi berhasil dihapus.');
    }

    /**
     * Unduh berkas materi perkuliahan dari disk privat.
     * Dilindungi MaterialPolicy (hanya admin, dosen pengampu, atau mahasiswa terdaftar).
     */
    public function download(Material $material)
    {
        Gate::authorize('download', $material);

        if ($material->type !== 'file' || ! $material->file_path || ! Storage::disk('local')->exists($material->file_path)) {
            abort(404, 'Berkas materi tidak ditemukan pada penyimpanan privat.');
        }

        return Storage::disk('local')->download(
            $material->file_path,
            $material->original_name ?? basename($material->file_path)
        );
    }
}
