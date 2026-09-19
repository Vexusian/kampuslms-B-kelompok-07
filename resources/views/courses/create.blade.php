<x-layout title="Tambah Mata Kuliah">
    <h1>Tambah Mata Kuliah Baru</h1>

    <form action="{{ route('courses.store') }}" method="POST" style="max-width: 600px;">
        @csrf

        <div class="form-group">
            <label for="code">Kode Mata Kuliah <span style="color: #dc2626;">*</span></label>
            <input type="text" id="code" name="code" class="form-control" 
                   value="{{ old('code') }}" placeholder="Contoh: SI2514024" required>
            @error('code') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Mata Kuliah <span style="color: #dc2626;">*</span></label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="{{ old('name') }}" placeholder="Contoh: Pemrograman Web" required>
            @error('name') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="sks">SKS <span style="color: #dc2626;">*</span></label>
            <input type="number" id="sks" name="sks" class="form-control" 
                   value="{{ old('sks') }}" min="1" max="6" required>
            @error('sks') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="status">Status <span style="color: #dc2626;">*</span></label>
            <select id="status" name="status" class="form-control" required>
                <option value="">-- Pilih Status --</option>
                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            @error('status') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="lecturer_id">Dosen Pengampu <span style="color: #dc2626;">*</span></label>
            <select id="lecturer_id" name="lecturer_id" class="form-control" required>
                <option value="">-- Pilih Dosen --</option>
                @foreach($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" {{ old('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                        {{ $lecturer->name }}
                    </option>
                @endforeach
            </select>
            @error('lecturer_id') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" class="form-control" rows="4" 
                      placeholder="Deskripsi singkat mata kuliah (opsional)">{{ old('description') }}</textarea>
            @error('description') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Simpan
            </button>
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</x-layout>