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
        <div style="background: #eff6ff; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 0.9rem; color: #1e40af;">
            <i class="fas fa-file-arrow-up"></i> Total Pengumpulan: <strong>{{ $submissions->total() }}</strong> Mahasiswa
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">No</th>
                <th>Mahasiswa</th>
                <th>NIM</th>
                <th>Waktu Pengumpulan</th>
                <th>Status Waktu</th>
                <th style="text-align: center;">Nilai</th>
                <th style="text-align: center; width: 140px;">Aksi</th>
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
                    <td colspan="7" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                        <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                        <p>Belum ada mahasiswa yang mengumpulkan tugas ini.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</x-layout>
