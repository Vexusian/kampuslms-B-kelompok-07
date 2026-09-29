<x-layout title="Tugas - {{ $course->name }}">
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
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">Penugasan Perkuliahan</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Mata Kuliah: <strong>{{ $course->code }} - {{ $course->name }}</strong>
            </p>
        </div>
        @if ($isLecturerOrAdmin)
            <a href="{{ route('dosen.courses.assignments.create', $course->id) }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Tugas
            </a>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">No</th>
                <th>Judul Tugas</th>
                <th>Batas Waktu (Deadline)</th>
                <th>Status Tenggat</th>
                @if ($isLecturerOrAdmin)
                    <th style="text-align: center;">Pengumpulan</th>
                @endif
                <th style="text-align: center; width: 220px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $index => $assignment)
                @php
                    $isPast = $assignment->due_at && $assignment->due_at->isPast();
                @endphp
                <tr>
                    <td style="text-align: center; color: #64748b;">
                        {{ $assignments->firstItem() ? $assignments->firstItem() + $index : $index + 1 }}
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $assignment->title }}</strong>
                    </td>
                    <td style="color: #475569; font-size: 0.9rem;">
                        <i class="far fa-clock"></i> {{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:i') : 'Tidak ditentukan' }}
                    </td>
                    <td>
                        @if ($isPast)
                            <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                                Berakhir
                            </span>
                        @else
                            <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                                Aktif
                            </span>
                        @endif
                    </td>
                    @if ($isLecturerOrAdmin)
                        <td style="text-align: center;">
                            <a href="{{ route('dosen.assignments.submissions.index', $assignment->id) }}" 
                               class="btn btn-sm" 
                               style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;" 
                               title="Lihat Mahasiswa yang Mengumpulkan">
                                <i class="fas fa-inbox"></i> {{ $assignment->submissions()->count() }} Berkas
                            </a>
                        </td>
                    @endif
                    <td>
                        <div class="flex gap-2" style="justify-content: center;">
                            <a href="{{ route($rolePrefix . '.assignments.show', $assignment->id) }}" 
                               class="btn btn-sm" 
                               style="background: #3b82f6;" 
                               title="Lihat Detail Tugas">
                                <i class="fas fa-eye"></i> Detail
                            </a>

                            @if ($isLecturerOrAdmin)
                                <a href="{{ route('dosen.assignments.edit', $assignment->id) }}" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit Tugas">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('dosen.assignments.destroy', $assignment->id) }}" 
                                      method="POST" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Tugas">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isLecturerOrAdmin ? '6' : '5' }}" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                        <i class="fas fa-tasks" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                        <p>Belum ada penugasan untuk mata kuliah ini.</p>
                        @if ($isLecturerOrAdmin)
                            <a href="{{ route('dosen.courses.assignments.create', $course->id) }}" class="btn btn-success btn-sm mt-4">
                                <i class="fas fa-plus"></i> Buat Tugas Pertama
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $assignments->links() }}
    </div>
</x-layout>
