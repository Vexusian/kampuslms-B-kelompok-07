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
        @if ($isLecturerOrAdmin)
            <a href="{{ route('dosen.courses.materials.create', $course->id) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Materi
            </a>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">No</th>
                <th>Judul Materi</th>
                <th>Tanggal Rilis</th>
                <th style="text-align: center; width: 220px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($materials as $index => $material)
                <tr>
                    <td style="text-align: center; color: #64748b;">
                        {{ $materials->firstItem() ? $materials->firstItem() + $index : $index + 1 }}
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $material->title }}</strong>
                    </td>
                    <td style="color: #64748b; font-size: 0.9rem;">
                        <i class="far fa-calendar-alt"></i> {{ $material->created_at ? $material->created_at->format('d M Y, H:i') : '-' }}
                    </td>
                    <td>
                        <div class="flex gap-2" style="justify-content: center;">
                            <a href="{{ route($rolePrefix . '.materials.show', $material->id) }}" 
                               class="btn btn-sm" 
                               style="background: #3b82f6;" 
                               title="Lihat Materi">
                                <i class="fas fa-eye"></i> Baca
                            </a>

                            @if ($isLecturerOrAdmin)
                                <a href="{{ route('dosen.materials.edit', $material->id) }}" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit Materi">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('dosen.materials.destroy', $material->id) }}" 
                                      method="POST" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Materi">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
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
