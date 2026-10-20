<x-layout title="Tambah Materi - {{ $course->name }}">
    <div class="mb-4">
        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Mata Kuliah
        </a>
    </div>

    <h1>Tambah Materi Perkuliahan</h1>

    <div style="background: #f8fafc; padding: 1.75rem; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 800px;">
        <div style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Mata Kuliah:</p>
            <strong style="color: #0f172a; font-size: 1.1rem;">{{ $course->code }} - {{ $course->name }}</strong>
        </div>

        <form action="{{ route('dosen.courses.materials.store', $course->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Judul Materi --}}
            <div class="form-group mb-4">
                <label for="title" style="font-weight: 600; color: #1e293b;">Judul Materi <span style="color: #ef4444;">*</span></label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="{{ old('title') }}" 
                       class="form-control" 
                       placeholder="Misal: Pertemuan 1 - Pengantar Basis Data Relasional" 
                       required>
                @error('title')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Deskripsi Singkat --}}
            <div class="form-group mb-4">
                <label for="description" style="font-weight: 600; color: #1e293b;">Deskripsi / Ringkasan Materi</label>
                <input type="text" 
                       id="description" 
                       name="description" 
                       value="{{ old('description') }}" 
                       class="form-control" 
                       placeholder="Ringkasan singkat topik pembahasan materi ini (opsional)">
                @error('description')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Pilihan Jenis Materi --}}
            <div class="form-group mb-4">
                <label style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">
                    Bentuk Materi <span style="color: #ef4444;">*</span>
                </label>
                @php
                    $selectedType = old('type', 'file');
                @endphp
                <div style="display: flex; gap: 1.5rem; background: #ffffff; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="type" value="file" id="type_file" {{ $selectedType === 'file' ? 'checked' : '' }} onchange="toggleMaterialType('file')">
                        <i class="fas fa-file-upload" style="color: #2563eb;"></i> Unggah Berkas Fisik (PDF / PPTX / DOCX)
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="type" value="link" id="type_link" {{ $selectedType === 'link' ? 'checked' : '' }} onchange="toggleMaterialType('link')">
                        <i class="fas fa-link" style="color: #059669;"></i> Tautan Eksternal (URL)
                    </label>
                </div>
                @error('type')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Bagian Unggah Berkas --}}
            <div id="file_section" class="form-group mb-4" style="background: #ffffff; border: 1px dashed #94a3b8; border-radius: 8px; padding: 1.25rem; {{ $selectedType === 'link' ? 'display: none;' : '' }}">
                <label for="file" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-cloud-upload-alt" style="color: #2563eb;"></i> Pilih Berkas Materi
                </label>
                <input type="file" 
                       id="file" 
                       name="file" 
                       class="form-control" 
                       accept=".pdf,.pptx,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       style="background: #f8fafc;">
                <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.5rem; line-height: 1.5;">
                    <i class="fas fa-shield-alt" style="color: #059669;"></i> <strong>Privasi & Keamanan:</strong> Berkas disimpan secara privat di disk penyimpanan server dan hanya dapat diunduh oleh dosen pengampu dan mahasiswa terdaftar.<br>
                    <i class="fas fa-info-circle"></i> Format yang didukung: <strong>PDF, PPTX, DOCX</strong>. Maksimal: <strong>20 MB</strong>.
                </div>
                @error('file')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Bagian Tautan Eksternal --}}
            <div id="link_section" class="form-group mb-4" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem; {{ $selectedType === 'link' ? '' : 'display: none;' }}">
                <label for="external_url" style="font-weight: 600; color: #1e293b;">
                    <i class="fas fa-external-link-alt" style="color: #059669;"></i> Alamat Tautan Eksternal (URL)
                </label>
                <input type="url" 
                       id="external_url" 
                       name="external_url" 
                       value="{{ old('external_url') }}" 
                       class="form-control" 
                       placeholder="https://drive.google.com/... atau https://youtu.be/...">
                <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.5rem;">
                    Gunakan tautan ini jika materi disimpan di Google Drive, YouTube, atau portal pembelajaran luar kampus.
                </div>
                @error('external_url')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Uraian Materi / Catatan Tambahan --}}
            <div class="form-group mb-4">
                <label for="content" style="font-weight: 600; color: #1e293b;">Uraian Materi / Catatan Kuliah</label>
                <textarea id="content" 
                          name="content" 
                          rows="6" 
                          class="form-control" 
                          placeholder="Tuliskan catatan, silabus pertemuan, instruksi belajar mandiri, atau rangkuman materi perkuliahan...">{{ old('content') }}</textarea>
                @error('content')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Materi
                </button>
                <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>

    <script>
        function toggleMaterialType(type) {
            const fileSection = document.getElementById('file_section');
            const linkSection = document.getElementById('link_section');
            if (type === 'file') {
                fileSection.style.display = 'block';
                linkSection.style.display = 'none';
            } else {
                fileSection.style.display = 'none';
                linkSection.style.display = 'block';
            }
        }
    </script>
</x-layout>
