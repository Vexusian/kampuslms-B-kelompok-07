<x-layout title="Tambah Tugas - {{ $course->name }}">
    <div class="mb-4">
        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Mata Kuliah
        </a>
    </div>

    <h1>Buat Penugasan Baru</h1>

    <div style="background: #f8fafc; padding: 1.75rem; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 800px;">
        <div style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Mata Kuliah:</p>
            <strong style="color: #0f172a; font-size: 1.1rem;">{{ $course->code }} - {{ $course->name }}</strong>
        </div>

        <form action="{{ route('dosen.courses.assignments.store', $course->id) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="title">Judul Penugasan <span style="color: #ef4444;">*</span></label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="{{ old('title') }}" 
                       class="form-control" 
                       placeholder="Misal: Tugas 1 - Analisis Normalisasi ERD" 
                       required>
                @error('title')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="due_at">Batas Waktu Pengumpulan (Deadline) <span style="color: #ef4444;">*</span></label>
                <input type="datetime-local" 
                       id="due_at" 
                       name="due_at" 
                       value="{{ old('due_at') }}" 
                       class="form-control" 
                       required>
                @error('due_at')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Instruksi & Kriteria Tugas</label>
                <textarea id="description" 
                          name="description" 
                          rows="8" 
                          class="form-control" 
                          placeholder="Tuliskan petunjuk pengerjaan tugas, format berkas, rubrik penilaian, atau ketentuan pengumpulan...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Buat & Terbitkan Tugas
                </button>
                <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layout>
