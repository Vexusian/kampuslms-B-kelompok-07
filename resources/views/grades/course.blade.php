<x-layout title="Rekap Nilai - {{ $course->name }}">
    @php
        $user = auth()->user();
        $totalAssignments = $assignments->count();
    @endphp

    {{-- Tombol Navigasi Atas --}}
    <div class="mb-4 flex flex-between" style="flex-wrap: wrap; gap: 0.75rem;">
        <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Mata Kuliah
        </a>
        <div class="flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fas fa-print"></i> Cetak Rekap
            </button>
        </div>
    </div>

    {{-- Header Rekap Nilai --}}
    <div class="mb-6" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
            <span style="background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 6px;">
                {{ $course->code }}
            </span>
            <span style="background: #f1f5f9; color: #475569; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 600;">
                {{ $course->sks }} SKS
            </span>
            <span style="background: {{ $user->role === 'mahasiswa' ? '#d1fae5' : '#ede9fe' }}; color: {{ $user->role === 'mahasiswa' ? '#065f46' : '#6d28d9' }}; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 600;">
                <i class="fas fa-user-shield"></i> {{ $user->role === 'mahasiswa' ? 'Rekap Nilai Saya' : 'Rekap Nilai Kelas (Dosen/Admin)' }}
            </span>
        </div>
        <h1 style="border-bottom: none; padding-bottom: 0; margin-bottom: 0.35rem; font-size: 1.75rem; color: #0f172a;">
            Rekap Nilai: {{ $course->name }}
        </h1>
        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
            Dosen Pengampu: <strong style="color: #334155;">{{ $course->lecturer?->name ?? 'Belum ditentukan' }}</strong>
            @if($course->lecturer?->email)
                <span style="color: #94a3b8; font-size: 0.85rem;">({{ $course->lecturer->email }})</span>
            @endif
        </p>
    </div>

    {{-- Filter Pencarian Cepat --}}
    @if($totalAssignments > 0)
        <div class="mb-6" style="background: #f8fafc; padding: 0.85rem 1.25rem; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.6rem; flex: 1; max-width: 440px;">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" id="gradeSearchInput" placeholder="Cari nama mahasiswa, judul tugas, atau catatan..." class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.9rem; margin-bottom: 0;">
            </div>
            <div style="color: #64748b; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-layer-group" style="color: #94a3b8;"></i>
                Menampilkan <strong>{{ $totalAssignments }}</strong> modul penugasan
            </div>
        </div>
    @endif

    {{-- Daftar Kartu Rekap Per-Tugas --}}
    @forelse ($assignments as $assignment)
        <div class="assignment-card mb-6" style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            {{-- Header Kartu Tugas --}}
            <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 0.15rem; font-weight: 700; border: none; padding: 0;">
                            {{ $assignment->title }}
                        </h2>
                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.825rem; color: #64748b; flex-wrap: wrap;">
                            @if($assignment->due_at)
                                <span><i class="far fa-calendar-alt" style="color: #94a3b8;"></i> Batas Waktu: <strong>{{ $assignment->due_at->format('d M Y, H:i') }}</strong></span>
                            @endif
                            <span><i class="fas fa-star" style="color: #94a3b8;"></i> Skor Maks: <strong>{{ $assignment->max_score ?? 100 }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2" style="align-items: center;">
                    @php
                        $assignmentSubs = $assignment->submissions;
                        $assignmentGraded = $assignmentSubs->filter(fn($s) => $s->grade !== null)->count();
                    @endphp
                    <span style="background: #f1f5f9; color: #475569; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                        <i class="fas fa-file-lines" style="color: #64748b;"></i> {{ $assignmentSubs->count() }} Pengumpulan
                        @if($assignmentSubs->count() > 0)
                            • <span style="color: #15803d;">{{ $assignmentGraded }} Dinilai</span>
                        @endif
                    </span>

                    @if($user->role !== 'mahasiswa')
                        <a href="{{ route('dosen.assignments.submissions.index', $assignment->id) }}" class="btn btn-sm" style="background: #2563eb; color: white; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="fas fa-list-check"></i> Kelola Tugas
                        </a>
                    @else
                        <a href="{{ route('assignments.show', $assignment->id) }}" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="fas fa-eye"></i> Detail Tugas
                        </a>
                    @endif
                </div>
            </div>

            {{-- Isi Tabel Pengumpulan --}}
            @if ($assignment->submissions->isEmpty())
                <div style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; color: #94a3b8; font-size: 1.25rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p style="font-weight: 600; color: #475569; margin-bottom: 0.25rem; font-size: 0.95rem;">Belum Ada Pengumpulan</p>
                    <p style="font-size: 0.85rem; color: #94a3b8; margin: 0;">Belum ada mahasiswa yang mengumpulkan berkas untuk tugas ini.</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="margin-top: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                @if ($user->role !== 'mahasiswa')
                                    <th>Mahasiswa</th>
                                @endif
                                <th style="width: 170px;">Waktu Kumpul</th>
                                <th style="width: 140px; text-align: center;">Nilai</th>
                                <th>Feedback / Ulasan</th>
                                <th style="width: 160px;">Tanggal Dinilai</th>
                                @if ($user->role !== 'mahasiswa')
                                    <th style="width: 110px; text-align: center;">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignment->submissions as $idx => $submission)
                                @php
                                    $isLate = $submission->submitted_at && $assignment->due_at && $submission->submitted_at->isAfter($assignment->due_at);
                                    $grade = $submission->grade;
                                @endphp
                                <tr class="grade-row">
                                    <td style="text-align: center; color: #94a3b8; font-size: 0.85rem;">
                                        {{ $idx + 1 }}
                                    </td>
                                    @if ($user->role !== 'mahasiswa')
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                                <div style="width: 34px; height: 34px; border-radius: 50%; background: #e2e8f0; color: #334155; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; flex-shrink: 0;">
                                                    {{ strtoupper(substr($submission->student?->name ?? 'M', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong style="color: #0f172a; display: block; font-size: 0.9rem;">
                                                        {{ $submission->student?->name ?? 'Mahasiswa #' . $submission->user_id }}
                                                    </strong>
                                                    <div style="font-size: 0.775rem; color: #64748b;">
                                                        @if($submission->student?->nim_nip)
                                                            <code style="background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.75rem;">{{ $submission->student->nim_nip }}</code>
                                                        @else
                                                            {{ $submission->student?->email }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                    <td>
                                        @if($submission->submitted_at)
                                            <div style="font-size: 0.85rem; color: #334155;">
                                                {{ $submission->submitted_at->format('d M Y, H:i') }}
                                            </div>
                                            @if($isLate)
                                                <span style="display: inline-block; padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; background: #fee2e2; color: #991b1b; margin-top: 0.15rem;">
                                                    Terlambat
                                                </span>
                                            @else
                                                <span style="display: inline-block; padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; background: #d1fae5; color: #065f46; margin-top: 0.15rem;">
                                                    Tepat Waktu
                                                </span>
                                            @endif
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.85rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if ($grade)
                                            @php
                                                $score = $grade->score;
                                                $maxScore = $assignment->max_score ?? 100;
                                                $percentage = $maxScore > 0 ? ($score / $maxScore) * 100 : $score;
                                                if ($percentage >= 80) {
                                                    $bg = '#dcfce7'; $color = '#15803d'; $border = '#bbf7d0';
                                                } elseif ($percentage >= 60) {
                                                    $bg = '#fef3c7'; $color = '#b45309'; $border = '#fde68a';
                                                } else {
                                                    $bg = '#fee2e2'; $color = '#b91c1c'; $border = '#fca5a5';
                                                }
                                            @endphp
                                            <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.875rem; font-weight: 700; background: {{ $bg }}; color: {{ $color }}; border: 1px solid {{ $border }};">
                                                {{ $score }} / {{ $maxScore }}
                                            </span>
                                        @else
                                            <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.775rem; font-weight: 600; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                <i class="fas fa-hourglass-half"></i> Belum dinilai
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($grade && $grade->feedback)
                                            <div style="font-size: 0.85rem; color: #334155; background: #f8fafc; padding: 0.4rem 0.65rem; border-radius: 6px; border-left: 3px solid #3b82f6; line-height: 1.4;">
                                                {{ $grade->feedback }}
                                            </div>
                                        @else
                                            <span style="color: #cbd5e1; font-size: 0.9rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.85rem; color: #475569;">
                                        @if ($grade && $grade->graded_at)
                                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                <i class="far fa-clock" style="color: #94a3b8; font-size: 0.8rem;"></i>
                                                <span>{{ $grade->graded_at->format('d M Y, H:i') }}</span>
                                            </div>
                                        @else
                                            <span style="color: #cbd5e1;">—</span>
                                        @endif
                                    </td>
                                    @if ($user->role !== 'mahasiswa')
                                        <td style="text-align: center;">
                                            <a href="{{ route('submissions.show', $submission->id) }}" 
                                               class="btn btn-sm" 
                                               style="background: #0284c7; color: white; padding: 0.3rem 0.65rem; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 0.3rem;" 
                                               title="Periksa atau Edit Nilai">
                                                <i class="fas fa-search"></i> Periksa
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div style="text-align: center; padding: 3.5rem 1rem; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
            <i class="fas fa-folder-open" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 0.75rem;"></i>
            <h3 style="color: #475569; font-size: 1.1rem; margin-bottom: 0.25rem;">Belum Ada Tugas</h3>
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Mata kuliah ini belum memiliki tugas atau penugasan yang dibuat.</p>
        </div>
    @endforelse

    {{-- Navigasi Bawah --}}
    <div class="mt-6 pt-4" style="border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Mata Kuliah
        </a>
        <a href="#top" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" class="btn btn-sm btn-secondary" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
            <i class="fas fa-arrow-up"></i> Ke Atas
        </a>
    </div>

    {{-- Script Pencarian Cepat --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('gradeSearchInput');
            if (!searchInput) return;

            searchInput.addEventListener('input', function (e) {
                const query = e.target.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.assignment-card');

                cards.forEach(card => {
                    let cardMatches = false;
                    const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
                    const rows = card.querySelectorAll('.grade-row');

                    if (rows.length === 0) {
                        card.style.display = (query === '' || title.includes(query)) ? '' : 'none';
                        return;
                    }

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (query === '' || text.includes(query) || title.includes(query)) {
                            row.style.display = '';
                            cardMatches = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    card.style.display = (query === '' || cardMatches || title.includes(query)) ? '' : 'none';
                });
            });
        });
    </script>

    {{-- Style Tambahan untuk Print --}}
    <style>
        @media print {
            nav, .btn, #gradeSearchInput, form, button {
                display: none !important;
            }
            main {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .assignment-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
        }
    </style>
</x-layout>