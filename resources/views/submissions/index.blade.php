<x-layout title="Pengumpulan Tugas - {{ $assignment->title }}">
    <div class="mb-4">
        <a href="{{ route('dosen.assignments.show', $assignment->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Tugas
        </a>
    </div>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">Pengumpulan Tugas</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Tugas: <strong>{{ $assignment->title }}</strong> ({{ $assignment->course->code }} - {{ $assignment->course->name }})
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <div style="background: #eff6ff; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 0.85rem; color: #1e40af;">
                <i class="fas fa-users"></i> Total Terdaftar: <strong>{{ $enrolledStudents->count() }}</strong>
            </div>
            <div style="background: #ecfdf5; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #a7f3d0; font-size: 0.85rem; color: #065f46;">
                <i class="fas fa-check-circle"></i> Sudah Mengumpulkan: <strong>{{ $submissions->total() }}</strong>
            </div>
            <div style="background: #fef2f2; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #fecaca; font-size: 0.85rem; color: #991b1b;">
                <i class="fas fa-clock"></i> Belum Mengumpulkan: <strong>{{ $unsubmittedStudents->count() }}</strong>
            </div>
        </div>
    </div>

    <h2 style="font-size: 1.15rem; margin-bottom: 0.75rem; color: #0f172a;">
        <i class="fas fa-file-arrow-up" style="color: #2563eb; margin-right: 0.35rem;"></i> Daftar Mahasiswa yang Sudah Mengumpulkan
    </h2>

    <table>
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">No</th>
                <th>Mahasiswa</th>
                <th>NIM</th>
                <th>Berkas Tugas</th>
                <th>Waktu Pengumpulan</th>
                <th>Status Waktu</th>
                <th style="text-align: center;">Nilai</th>
                <th style="text-align: center; width: 130px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submissions as $index => $submission)
                @php
                    $isLate = $submission->submitted_at && $assignment->due_at && $submission->submitted_at->isAfter($assignment->due_at);
                @endphp
                <tr>
                    <td style="text-align: center; color: #64748b;">
                        {{ $submissions->firstItem() ? $submissions->firstItem() + $index : $index + 1 }}
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $submission->student->name ?? 'Mahasiswa #' . $submission->user_id }}</strong>
                        <div style="font-size: 0.8rem; color: #64748b;">{{ $submission->student->email ?? '-' }}</div>
                    </td>
                    <td>
                        <code style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $submission->student->nim_nip ?? '-' }}</code>
                    </td>
                    <td>
                        @if ($submission->file_path)
                            <div style="font-size: 0.85rem; color: #334155;">
                                <i class="fas {{ $submission->file_icon }}" style="color: #2563eb; margin-right: 0.25rem;"></i>
                                {{ $submission->original_filename ?? basename($submission->file_path) }}
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                Ukuran: {{ $submission->formatted_file_size }}
                            </div>
                        @else
                            <span style="color: #94a3b8; font-size: 0.85rem; font-style: italic;">Hanya teks</span>
                        @endif
                    </td>
                    <td style="color: #475569; font-size: 0.9rem;">
                        {{ $submission->submitted_at ? $submission->submitted_at->format('d M Y, H:i') : '-' }}
                    </td>
                    <td>
                        @if ($isLate)
                            <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                                Terlambat
                            </span>
                        @else
                            <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                                Tepat Waktu
                            </span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if ($submission->grade)
                            <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.85rem; font-weight: 700; background: #dbeafe; color: #1e40af;">
                                {{ $submission->grade->score }} / 100
                            </span>
                        @else
                            <span style="color: #94a3b8; font-size: 0.85rem; font-style: italic;">
                                Belum dinilai
                            </span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <a href="{{ route('dosen.submissions.show', $submission->id) }}" 
                           class="btn btn-sm" 
                           style="background: #3b82f6;" 
                           title="Periksa Tugas">
                            <i class="fas fa-search"></i> Periksa
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                        <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                        <p>Belum ada mahasiswa yang mengumpulkan tugas ini.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 mb-6">
        {{ $submissions->links() }}
    </div>

    @if ($unsubmittedStudents->isNotEmpty())
        <h2 style="font-size: 1.15rem; margin-top: 2rem; margin-bottom: 0.75rem; color: #991b1b;">
            <i class="fas fa-user-xmark" style="color: #ef4444; margin-right: 0.35rem;"></i> Daftar Mahasiswa yang Belum Mengumpulkan ({{ $unsubmittedStudents->count() }})
        </h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Email</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unsubmittedStudents as $idx => $student)
                    <tr>
                        <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #0f172a;">{{ $student->name }}</strong></td>
                        <td><code style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $student->nim_nip ?? '-' }}</code></td>
                        <td style="color: #64748b; font-size: 0.9rem;">{{ $student->email }}</td>
                        <td style="text-align: center;">
                            <span style="padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                                Belum Mengumpulkan
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-layout>
