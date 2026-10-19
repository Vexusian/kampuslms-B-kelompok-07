<x-layout title="{{ $material->title }} - KampusLMS">
    @php
        $user = auth()->user();
        $isLecturerOrAdmin = $user && ($user->role === 'admin' || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id));
        $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
    @endphp

    <div class="mb-4 flex gap-2">
        <a href="{{ route($rolePrefix . '.courses.materials.index', $material->course_id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Daftar Materi
        </a>
        <a href="{{ route('courses.show', $material->course_id) }}" class="btn btn-secondary" style="background: #94a3b8;">
            <i class="fas fa-book"></i> Mata Kuliah
        </a>
    </div>

    <h1>{{ $material->title }}</h1>

    {{-- Kartu Info Meta Perkuliahan --}}
    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <table style="border: none; margin-top: 0; width: 100%;">
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; width: 160px; border: none; color: #64748b;">Mata Kuliah</td>
                <td style="padding: 0.4rem; border: none; font-weight: 600; color: #0f172a;">
                    {{ $material->course->code }} - {{ $material->course->name }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Dosen Pengampu</td>
                <td style="padding: 0.4rem; border: none;">
                    {{ $material->course->lecturer->name ?? 'Belum ditentukan' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Bentuk Materi</td>
                <td style="padding: 0.4rem; border: none;">
                    @if ($material->type === 'link')
                        <span style="background: #d1fae5; color: #065f46; font-size: 0.85rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fas fa-link"></i> Tautan Eksternal
                        </span>
                    @elseif ($material->file_path)
                        <span style="background: #dbeafe; color: #1e40af; font-size: 0.85rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fas {{ $material->file_icon }}"></i> Berkas Dokumen ({{ strtoupper(pathinfo($material->original_name ?? $material->file_path, PATHINFO_EXTENSION)) }})
                        </span>
                    @else
                        <span style="background: #f1f5f9; color: #475569; font-size: 0.85rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fas fa-file-alt"></i> Teks / Catatan
                        </span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Diterbitkan Pada</td>
                <td style="padding: 0.4rem; border: none;">
                    <i class="far fa-calendar-alt"></i> {{ $material->created_at ? $material->created_at->format('d M Y, H:i') : '-' }}
                </td>
            </tr>
            @if ($material->description)
                <tr>
                    <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b; vertical-align: top;">Deskripsi</td>
                    <td style="padding: 0.4rem; border: none; color: #334155;">
                        {{ $material->description }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Bagian Berkas Unduhan / Tautan Eksternal --}}
    @if ($material->type === 'link' && $material->external_url)
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 52px; height: 52px; border-radius: 8px; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-external-link-alt"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; margin: 0 0 0.25rem; color: #064e3b; font-weight: 600;">Materi Berupa Tautan Eksternal</h3>
                    <p style="margin: 0; color: #047857; font-size: 0.9rem; word-break: break-all;">
                        {{ $material->external_url }}
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ $material->external_url }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="btn btn-success" 
                   style="background: #059669; padding: 0.65rem 1.25rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-external-link-alt"></i> Buka Tautan Materi
                </a>
            </div>
        </div>
    @elseif ($material->file_path)
        <div style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 52px; height: 52px; border-radius: 8px; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
                    <i class="fas {{ $material->file_icon }}"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; margin: 0 0 0.25rem; color: #0f172a; font-weight: 600;">
                        {{ $material->original_name ?? basename($material->file_path) }}
                    </h3>
                    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
                        <span><i class="fas fa-database"></i> Ukuran: <strong>{{ $material->formatted_file_size }}</strong></span>
                        <span style="margin: 0 0.5rem;">•</span>
                        <span><i class="fas fa-shield-alt" style="color: #10b981;"></i> Berkas Privat Terproteksi</span>
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('materials.download', $material->id) }}" 
                   class="btn btn-primary" 
                   style="background: #2563eb; padding: 0.65rem 1.25rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-download"></i> Unduh Berkas Materi
                </a>
            </div>
        </div>
    @endif

    {{-- Isi Konten / Uraian Materi --}}
    @if (!empty($material->content))
        <div style="background: #ffffff; padding: 2rem; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 180px; line-height: 1.8; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <i class="fas fa-align-left" style="color: #2563eb; margin-right: 0.4rem;"></i> Uraian Materi / Catatan Perkuliahan
            </h3>

            <div style="white-space: pre-wrap; color: #334155; font-size: 1rem;">{!! nl2br(e($material->content)) !!}</div>
        </div>
    @endif

    {{-- Tombol Aksi untuk Dosen / Admin --}}
    <div class="flex gap-2 mt-4">
        @can('update', $material)
            <a href="{{ route('dosen.materials.edit', $material->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Materi
            </a>
        @endcan
        @can('delete', $material)
            <form action="{{ route('dosen.materials.destroy', $material->id) }}" method="POST" 
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini beserta seluruh berkas fisiknya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus Materi
                </button>
            </form>
        @endcan
    </div>
</x-layout>
