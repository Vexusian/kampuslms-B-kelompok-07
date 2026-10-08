<x-layout title="Detail Pengumpulan Tugas - {{ $submission->assignment->title }}">
    @php
        $user = auth()->user();
        $isLate = $submission->submitted_at && $submission->assignment->due_at && $submission->submitted_at->isAfter($submission->assignment->due_at);
    @endphp

    <div class="mb-4">
        @can('viewAny', [App\Models\Submission::class, $submission->assignment])
            <a href="{{ route('dosen.assignments.submissions.index', $submission->assignment_id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pengumpulan
            </a>
        @else
            <a href="{{ route('mahasiswa.assignments.show', $submission->assignment_id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Halaman Tugas
            </a>
        @endcan
    </div>

    <h1>Detail Pengumpulan Tugas</h1>

    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <table style="border: none; margin-top: 0;">
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; width: 180px; border: none; color: #64748b;">Mata Kuliah</td>
                <td style="padding: 0.4rem; border: none; font-weight: 600; color: #0f172a;">
                    {{ $submission->assignment->course->code }} - {{ $submission->assignment->course->name }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Judul Tugas</td>
                <td style="padding: 0.4rem; border: none; color: #0f172a;">
                    {{ $submission->assignment->title }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Mahasiswa</td>
                <td style="padding: 0.4rem; border: none;">
                    <strong>{{ $submission->student->name ?? 'Mahasiswa #' . $submission->user_id }}</strong> 
                    ({{ $submission->student->nim_nip ?? 'Tanpa NIM' }})
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Waktu Pengumpulan</td>
                <td style="padding: 0.4rem; border: none;">
                    <i class="far fa-clock"></i> {{ $submission->submitted_at ? $submission->submitted_at->format('d M Y, H:i:s') : '-' }}
                    @if ($isLate)
                        <span style="margin-left: 0.5rem; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                            Terlambat
                        </span>
                    @else
                        <span style="margin-left: 0.5rem; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                            Tepat Waktu
                        </span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Isi Berkas / Jawaban Mahasiswa --}}
    <div style="background: #ffffff; padding: 2rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem; line-height: 1.8;">
        <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
            <i class="fas fa-file-lines" style="color: #2563eb; margin-right: 0.4rem;"></i> Jawaban & Catatan Mahasiswa
        </h3>

        @if (!empty($submission->content))
            <div style="white-space: pre-wrap; color: #334155; font-size: 1rem; background: #f8fafc; padding: 1.25rem; border-radius: 6px; border: 1px solid #e2e8f0;">{!! nl2br(e($submission->content)) !!}</div>
        @else
            <p style="color: #94a3b8; font-style: italic;">Tidak ada lampiran teks.</p>
        @endif

        @if (!empty($submission->file_path))
            <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-paperclip" style="color: #64748b;"></i>
                <span style="color: #334155; font-size: 0.9rem;">Berkas Terlampir: <strong>{{ basename($submission->file_path) }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Status / Hasil Penilaian --}}
    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 0.75rem;">
            <i class="fas fa-award" style="color: #f59e0b; margin-right: 0.4rem;"></i> Evaluasi & Penilaian
        </h3>

        @if ($submission->grade)
            <div style="background: #ecfdf5; padding: 1.25rem; border-radius: 6px; border: 1px solid #a7f3d0;">
                <div style="font-size: 1.25rem; font-weight: 700; color: #065f46; margin-bottom: 0.5rem;">
                    Nilai: {{ $submission->grade->score }} / 100
                </div>
                @if ($submission->grade->feedback)
                    <p style="color: #065f46; margin: 0; font-size: 0.95rem;">
                        <strong>Catatan Koreksi:</strong> {{ $submission->grade->feedback }}
                    </p>
                @endif
                <div style="color: #047857; font-size: 0.8rem; margin-top: 0.5rem;">
                    Dinilai pada: {{ $submission->grade->created_at ? $submission->grade->created_at->format('d M Y, H:i') : '-' }}
                </div>
            </div>
        @else
            <div style="padding: 1rem; background: #ffffff; border-radius: 6px; border: 1px solid #e2e8f0; color: #64748b;">
                <p style="margin: 0; font-style: italic;">
                    <i class="fas fa-info-circle"></i> Berkas tugas ini belum diberikan penilaian angka oleh dosen pengampu.
                </p>
            </div>
        @endif
    </div>

    {{-- Form Penilaian oleh Dosen Pengampu --}}
    @can('create', [App\Models\Grade::class, $submission])
        <div style="background: #ffffff; padding: 1.5rem; border-radius: 8px; border: 1px solid #cbd5e1;">
            <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 1rem;">
                <i class="fas fa-edit" style="color: #2563eb; margin-right: 0.4rem;"></i> Form Penilaian Dosen
            </h3>

            <form action="{{ route('submissions.grade', $submission->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group" style="max-width: 200px;">
                    <label for="score">Nilai Angka (0-100) <span style="color: #ef4444;">*</span></label>
                    <input type="number" id="score" name="score" min="0" max="100" step="1"
                           class="form-control" value="{{ old('score', $submission->grade?->score) }}" required>
                </div>

                <div class="form-group">
                    <label for="feedback">Umpan Balik / Catatan Evaluasi</label>
                    <textarea id="feedback" name="feedback" rows="4" class="form-control"
                              placeholder="Tuliskan catatan evaluasi untuk mahasiswa...">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Penilaian
                </button>
            </form>
        </div>
    @endcan
</x-layout>
