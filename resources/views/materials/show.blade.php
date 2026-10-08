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

    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <table style="border: none; margin-top: 0;">
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
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Diterbitkan Pada</td>
                <td style="padding: 0.4rem; border: none;">
                    <i class="far fa-calendar-alt"></i> {{ $material->created_at ? $material->created_at->format('d M Y, H:i') : '-' }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Isi Konten Materi --}}
    <div style="background: #ffffff; padding: 2rem; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 250px; line-height: 1.8;">
        <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
            <i class="fas fa-align-left" style="color: #2563eb; margin-right: 0.4rem;"></i> Uraian Materi
        </h3>

        @if (!empty($material->content))
            <div style="white-space: pre-wrap; color: #334155; font-size: 1rem;">{!! nl2br(e($material->content)) !!}</div>
        @else
            <p style="color: #94a3b8; font-style: italic;">Tidak ada uraian teks untuk materi ini.</p>
        @endif
    </div>

    <div class="flex gap-2 mt-4">
        @can('update', $material)
            <a href="{{ route('dosen.materials.edit', $material->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Materi
            </a>
        @endcan
        @can('delete', $material)
            <form action="{{ route('dosen.materials.destroy', $material->id) }}" method="POST" 
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus Materi
                </button>
            </form>
        @endcan
    </div>
</x-layout>
