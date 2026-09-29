<x-layout title="Detail {{ $course->name }}">
    @php
        $user = auth()->user();
        $isLecturerOrAdmin = $user && ($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id));
        $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
    @endphp

    <div class="mb-4">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
        <h1 style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">{{ $course->name }}</h1>
        @if ($isLecturerOrAdmin)
            <div class="flex gap-2">
                <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                      onsubmit="return confirm('⚠️ Yakin ingin menghapus mata kuliah ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Info Mata Kuliah --}}
    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
        <table style="border: none; margin-top: 0;">
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; width: 180px; border: none; color: #64748b;">Kode</td>
                <td style="padding: 0.5rem; border: none; font-weight: 700; color: #0f172a;">{{ $course->code }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Nama Mata Kuliah</td>
                <td style="padding: 0.5rem; border: none; color: #0f172a;">{{ $course->name }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">SKS</td>
                <td style="padding: 0.5rem; border: none;">{{ $course->sks }} SKS</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Status</td>
                <td style="padding: 0.5rem; border: none;">
                    <span style="
                        padding: 0.25rem 0.6rem;
                        border-radius: 12px;
                        font-size: 0.85rem;
                        font-weight: 600;
                        background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }};
                        color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};
                    ">
                        {{ ucfirst($course->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Dosen Pengampu</td>
                <td style="padding: 0.5rem; border: none;">
                    {{ $course->lecturer->name ?? 'Belum ditentukan' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b; vertical-align: top;">Deskripsi</td>
                <td style="padding: 0.5rem; border: none; color: #334155; line-height: 1.7;">
                    {{ $course->description ?: 'Tidak ada deskripsi.' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Jumlah Mahasiswa</td>
                <td style="padding: 0.5rem; border: none;">
                    <i class="fas fa-user-graduate" style="color: #2563eb;"></i> {{ $course->students->count() }} Mahasiswa Terdaftar
                </td>
            </tr>
        </table>
    </div>

    {{-- Pintasan Navigasi ke Materi & Tugas --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <a href="{{ route($rolePrefix . '.courses.materials.index', $course->id) }}" style="text-decoration: none; color: inherit;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 1rem;">Materi Perkuliahan</strong>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.15rem 0 0;">{{ $course->materials->count() }} Materi tersedia</p>
                </div>
                <i class="fas fa-chevron-right" style="margin-left: auto; color: #94a3b8; font-size: 0.85rem;"></i>
            </div>
        </a>

        <a href="{{ route($rolePrefix . '.courses.assignments.index', $course->id) }}" style="text-decoration: none; color: inherit;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 1rem;">Penugasan / Tugas</strong>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.15rem 0 0;">{{ $course->assignments->count() }} Tugas terdaftar</p>
                </div>
                <i class="fas fa-chevron-right" style="margin-left: auto; color: #94a3b8; font-size: 0.85rem;"></i>
            </div>
        </a>
    </div>

    {{-- Preview 3 Materi Terbaru --}}
    @if ($course->materials->count() > 0)
        <div class="mb-6">
            <div class="flex flex-between mb-4">
                <h3 style="font-size: 1.1rem; color: #0f172a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-file-alt" style="color: #2563eb;"></i> Materi Terbaru
                </h3>
                <a href="{{ route($rolePrefix . '.courses.materials.index', $course->id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                    Lihat Semua <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Judul Materi</th>
                        <th>Tanggal Rilis</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($course->materials->sortByDesc('created_at')->take(3) as $material)
                        <tr>
                            <td>{{ $material->title }}</td>
                            <td style="color: #64748b; font-size: 0.9rem;">
                                {{ $material->created_at ? $material->created_at->format('d M Y') : '-' }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route($rolePrefix . '.materials.show', $material->id) }}" class="btn btn-sm" style="background: #3b82f6;">
                                    <i class="fas fa-eye"></i> Baca
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Preview 3 Tugas Terbaru --}}
    @if ($course->assignments->count() > 0)
        <div>
            <div class="flex flex-between mb-4">
                <h3 style="font-size: 1.1rem; color: #0f172a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-tasks" style="color: #b45309;"></i> Tugas Terbaru
                </h3>
                <a href="{{ route($rolePrefix . '.courses.assignments.index', $course->id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                    Lihat Semua <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Judul Tugas</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($course->assignments->sortByDesc('due_at')->take(3) as $assignment)
                        @php $isPast = $assignment->due_at && $assignment->due_at->isPast(); @endphp
                        <tr>
                            <td>{{ $assignment->title }}</td>
                            <td style="color: #64748b; font-size: 0.9rem;">
                                {{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td>
                                @if ($isPast)
                                    <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;">Berakhir</span>
                                @else
                                    <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">Aktif</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route($rolePrefix . '.assignments.show', $assignment->id) }}" class="btn btn-sm" style="background: #3b82f6;">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout>