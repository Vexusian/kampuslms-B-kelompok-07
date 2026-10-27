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

        <form action="{{ route('dosen.materials.update', $material->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Judul Materi --}}
            <div class="form-group mb-4">
                <label for="title" style="font-weight: 600; color: #1e293b;">Judul Materi <span style="color: #ef4444;">*</span></label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="{{ old('title', $material->title) }}" 
                       class="form-control" 
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
                       value="{{ old('description', $material->description) }}" 
                       class="form-control" 
                       placeholder="Ringkasan singkat topik pembahasan materi ini">
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
                    $selectedType = old('type', $material->type ?? 'file');
                @endphp
                <div style="display: flex; gap: 1.5rem; background: #ffffff; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="type" value="file" id="type_file" {{ $selectedType === 'file' ? 'checked' : '' }} onchange="toggleMaterialType('file')">
                        <i class="fas fa-file-upload" style="color: #2563eb;"></i> Berkas Fisik (PDF / PPTX / DOCX)
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
                @if ($material->file_path)
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas {{ $material->file_icon }}" style="font-size: 1.5rem; color: #2563eb;"></i>
                            <div>
                                <strong style="color: #1e3a8a; font-size: 0.95rem;">{{ $material->original_name ?? basename($material->file_path) }}</strong>
                                <span style="color: #64748b; font-size: 0.85rem; margin-left: 0.5rem;">({{ $material->formatted_file_size }})</span>
                            </div>
                        </div>
                        <span style="font-size: 0.8rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">
                            Berkas Saat Ini
                        </span>
                    </div>
                @endif

                <label for="file" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-cloud-upload-alt" style="color: #2563eb;"></i> {{ $material->file_path ? 'Ganti Berkas (Opsional)' : 'Pilih Berkas Materi' }}
                </label>
                <input type="file" 
                       id="file" 
                       name="file" 
                       class="form-control" 
                       accept=".pdf,.pptx,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       style="background: #f8fafc;">
                <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.5rem; line-height: 1.5;">
                    Format yang didukung: <strong>PDF, PPTX, DOCX</strong>. Maksimal: <strong>20 MB</strong>.<br>
                    Jika Anda mengunggah berkas baru, berkas lama akan otomatis digantikan dan dihapus dari penyimpanan server.
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
                       value="{{ old('external_url', $material->external_url) }}" 
                       class="form-control" 
                       placeholder="https://drive.google.com/... atau https://youtu.be/...">
                <div style="font-size: 0.825rem; color: #64748b; margin-top: 0.5rem;">
                    Gunakan tautan ini jika materi berada di luar sistem (Google Drive, YouTube, dll).
                </div>
                @error('external_url')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Uraian Materi / Catatan --}}
            <div class="form-group mb-4">
                <label for="content" style="font-weight: 600; color: #1e293b;">Isi / Uraian Materi</label>
                <textarea id="content" 
                          name="content" 
                          rows="6" 
                          class="form-control">{{ old('content', $material->content) }}</textarea>
                @error('content')
                    <div class="text-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
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
