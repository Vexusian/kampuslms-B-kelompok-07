<x-layout title="Materi - {{ $course->name }}">
    <div class="mb-4">
        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Mata Kuliah
        </a>
    </div>

    @php
        $user = auth()->user();
        $isLecturerOrAdmin = $user && ($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id));
        $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
    @endphp

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">Materi Perkuliahan</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Mata Kuliah: <strong>{{ $course->code }} - {{ $course->name }}</strong>
            </p>
        </div>
        @can('create', [App\Models\Material::class, $course])
            <a href="{{ route('dosen.courses.materials.create', $course->id) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Materi
            </a>
        @endcan
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">No</th>
                <th>Judul Materi</th>
                <th style="width: 170px;">Bentuk / Berkas</th>
                <th style="width: 150px;">Tanggal Rilis</th>
                <th style="text-align: center; width: 260px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($materials as $index => $material)
                <tr>
                    <td style="text-align: center; color: #64748b;">
                        {{ $materials->firstItem() ? $materials->firstItem() + $index : $index + 1 }}
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 1rem;">{{ $material->title }}</strong>
                        @if ($material->description)
                            <p style="color: #64748b; font-size: 0.85rem; margin: 0.2rem 0 0;">
                                {{ Str::limit($material->description, 90) }}
                            </p>
                        @endif
                    </td>
                    <td>
                        @if ($material->type === 'link')
                            <span style="background: #d1fae5; color: #065f46; font-size: 0.8rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <i class="fas fa-link"></i> Tautan
                            </span>
                        @elseif ($material->file_path)
                            <span style="background: #dbeafe; color: #1e40af; font-size: 0.8rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <i class="fas {{ $material->file_icon }}"></i> {{ strtoupper(pathinfo($material->original_name ?? $material->file_path, PATHINFO_EXTENSION)) }} ({{ $material->formatted_file_size }})
                            </span>
                        @else
                            <span style="background: #f1f5f9; color: #475569; font-size: 0.8rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <i class="fas fa-file-alt"></i> Teks
                            </span>
                        @endif
                    </td>
                    <td style="color: #64748b; font-size: 0.9rem;">
                        <i class="far fa-calendar-alt"></i> {{ $material->created_at ? $material->created_at->format('d M Y') : '-' }}
                    </td>
                    <td>
                        <div class="flex gap-2" style="justify-content: center; flex-wrap: wrap;">
                            <a href="{{ route($rolePrefix . '.materials.show', $material->id) }}" 
                               class="btn btn-sm" 
                               style="background: #3b82f6;" 
                               title="Lihat Detail Materi">
                                <i class="fas fa-eye"></i> Detail
                            </a>

                            @if ($material->type === 'file' && $material->file_path)
                                <a href="{{ route('materials.download', $material->id) }}" 
                                   class="btn btn-sm btn-primary" 
                                   style="background: #0284c7;" 
                                   title="Unduh Berkas Materi">
                                    <i class="fas fa-download"></i> Unduh
                                </a>
                            @elseif ($material->type === 'link' && $material->external_url)
                                <a href="{{ $material->external_url }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="btn btn-sm btn-success" 
                                   style="background: #059669;" 
                                   title="Buka Tautan Eksternal">
                                    <i class="fas fa-external-link-alt"></i> Buka
                                </a>
                            @endif

                            @can('update', $material)
                                <a href="{{ route('dosen.materials.edit', $material->id) }}" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit Materi">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $material)
                                <form action="{{ route('dosen.materials.destroy', $material->id) }}" 
                                      method="POST" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini beserta berkas fisiknya?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Materi">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                        <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                        <p>Belum ada materi perkuliahan untuk mata kuliah ini.</p>
                        @if ($isLecturerOrAdmin)
                            <a href="{{ route('dosen.courses.materials.create', $course->id) }}" class="btn btn-success btn-sm mt-4">
                                <i class="fas fa-plus"></i> Tambah Materi Pertama
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>
</x-layout>
