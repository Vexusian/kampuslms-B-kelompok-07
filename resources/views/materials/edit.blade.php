<x-layout title="Edit Materi - {{ $material->title }}">
    <div class="mb-4">
        <a href="{{ route('dosen.materials.show', $material->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Materi
        </a>
    </div>

    <h1>Edit Materi Perkuliahan</h1>

    <div style="background: #f8fafc; padding: 1.75rem; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 800px;">
        <div style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Mata Kuliah:</p>
            <strong style="color: #0f172a; font-size: 1.1rem;">{{ $material->course->code }} - {{ $material->course->name }}</strong>
        </div>

        <form action="{{ route('dosen.materials.update', $material->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Judul Materi <span style="color: #ef4444;">*</span></label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="{{ old('title', $material->title) }}" 
                       class="form-control" 
                       required>
                @error('title')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="content">Isi / Uraian Materi</label>
                <textarea id="content" 
                          name="content" 
                          rows="8" 
                          class="form-control">{{ old('content', $material->content) }}</textarea>
                @error('content')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Perbarui Materi
                </button>
                <a href="{{ route('dosen.materials.show', $material->id) }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layout>
