<x-layout title="{{ $assignment->title }} - KampusLMS">
    @php
        $user = auth()->user();
        $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
        $isPast = $assignment->due_at && $assignment->due_at->isPast();

        $mySubmission = null;
        if ($user && $user->role === 'mahasiswa') {
            $mySubmission = $assignment->submissions->firstWhere('user_id', $user->id);
        }
    @endphp

    <div class="mb-4 flex gap-2">
        <a href="{{ route($rolePrefix . '.courses.assignments.index', $assignment->course_id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Daftar Tugas
        </a>
        <a href="{{ route('courses.show', $assignment->course_id) }}" class="btn btn-secondary" style="background: #94a3b8;">
            <i class="fas fa-book"></i> Mata Kuliah
        </a>
    </div>

    <h1>{{ $assignment->title }}</h1>

    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <table style="border: none; margin-top: 0;">
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; width: 180px; border: none; color: #64748b;">Mata Kuliah</td>
                <td style="padding: 0.4rem; border: none; font-weight: 600; color: #0f172a;">
                    {{ $assignment->course->code }} - {{ $assignment->course->name }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Dosen Pengampu</td>
                <td style="padding: 0.4rem; border: none;">
                    {{ $assignment->course->lecturer->name ?? 'Belum ditentukan' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Batas Waktu (Deadline)</td>
                <td style="padding: 0.4rem; border: none;">
                    <i class="far fa-clock"></i> {{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:i') : 'Tidak ditentukan' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 1rem 0.4rem 0; font-weight: 600; border: none; color: #64748b;">Status Tenggat</td>
                <td style="padding: 0.4rem; border: none;">
                    @if ($isPast)
                        <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                            Waktu Pengumpulan Telah Berakhir
                        </span>
                    @else
                        <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                            Masih Terbuka
                        </span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Instruksi Tugas --}}
    <div style="background: #ffffff; padding: 2rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem; line-height: 1.8;">
        <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
            <i class="fas fa-file-alt" style="color: #2563eb; margin-right: 0.4rem;"></i> Instruksi & Petunjuk Tugas
        </h3>

        @php
            $instructions = $assignment->instructions ?? $assignment->description;
        @endphp

        @if (!empty($instructions))
            <div style="white-space: pre-wrap; color: #334155; font-size: 1rem;">{!! nl2br(e($instructions)) !!}</div>
        @else
            <p style="color: #94a3b8; font-style: italic;">Tidak ada petunjuk tertulis untuk tugas ini.</p>
        @endif
    </div>

    {{-- Section Dosen: Ringkasan Pengumpulan --}}
    @can('viewAny', [App\Models\Submission::class, $assignment])
        <div style="background: #f1f5f9; padding: 1.5rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
            <div class="flex flex-between" style="flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="font-size: 1.05rem; color: #0f172a; margin-bottom: 0.25rem;">
                        <i class="fas fa-users" style="color: #2563eb; margin-right: 0.4rem;"></i> Pengumpulan Mahasiswa
                    </h3>
                    <p style="color: #64748b; font-size: 0.9rem; margin: 0;">
                        Total <strong>{{ $assignment->submissions->count() }}</strong> berkas tugas telah dikumpulkan.
                    </p>
                </div>
                <a href="{{ route('dosen.assignments.submissions.index', $assignment->id) }}" class="btn btn-primary">
                    <i class="fas fa-list-check"></i> Kelola & Nilai Berkas Masuk
                </a>
            </div>
        </div>

        <div class="flex gap-2 mb-6">
            @can('update', $assignment)
                <a href="{{ route('dosen.assignments.edit', $assignment->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Tugas
                </a>
            @endcan
            @can('delete', $assignment)
                <form action="{{ route('dosen.assignments.destroy', $assignment->id) }}" method="POST" 
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus Tugas
                    </button>
                </form>
            @endcan
        </div>
    @endcan

    {{-- Section Mahasiswa: Pengumpulan Tugas Sendiri --}}
    @can('create', [App\Models\Submission::class, $assignment])
        <div style="background: #f8fafc; padding: 1.75rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 1.5rem;">
            <h3 style="font-size: 1.15rem; color: #0f172a; margin-bottom: 1rem;">
                <i class="fas fa-cloud-upload-alt" style="color: #2563eb; margin-right: 0.4rem;"></i> Status Pengumpulan Tugas Anda
            </h3>

            @if ($mySubmission)
                <div style="background: #ffffff; padding: 1.25rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.85rem; font-weight: 600; color: #065f46; background: #d1fae5; padding: 0.25rem 0.6rem; border-radius: 12px;">
                            <i class="fas fa-check-circle"></i> Sudah Mengumpulkan
                        </span>
                        <span style="color: #64748b; font-size: 0.85rem;">
                            Dikirim pada: {{ $mySubmission->submitted_at ? $mySubmission->submitted_at->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>

                    <p style="font-weight: 600; color: #334155; margin-bottom: 0.25rem; font-size: 0.9rem;">Jawaban / Catatan Anda:</p>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 0.95rem; white-space: pre-wrap;">{{ $mySubmission->content }}</div>

                    @if ($mySubmission->grade)
                        <div style="margin-top: 1rem; padding: 1rem; background: #ecfdf5; border-radius: 6px; border: 1px solid #a7f3d0;">
                            <strong style="color: #065f46; font-size: 1rem;">Nilai: {{ $mySubmission->grade->score }} / 100</strong>
                            @if ($mySubmission->grade->feedback)
                                <p style="color: #065f46; margin-top: 0.25rem; font-size: 0.9rem;">
                                    Catatan Dosen: {{ $mySubmission->grade->feedback }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p style="margin-top: 0.75rem; font-size: 0.85rem; color: #64748b; font-style: italic;">
                            <i class="fas fa-hourglass-half"></i> Tugas sedang menunggu penilaian dari dosen pengampu.
                        </p>
                    @endif
                </div>
            @endif

            {{-- Form Submit / Update Pengumpulan --}}
            <form action="{{ route('mahasiswa.assignments.submissions.store', $assignment->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="content">
                        {{ $mySubmission ? 'Perbarui Jawaban / Tautan Tugas' : 'Tuliskan Jawaban / Tautan Tugas Anda' }} 
                        <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="content" 
                              name="content" 
                              rows="6" 
                              class="form-control" 
                              placeholder="Ketik jawaban tugas, ringkasan, atau tautan Google Drive / GitHub repositori Anda..." 
                              required>{{ old('content', $mySubmission ? $mySubmission->content : '') }}</textarea>
                    @error('content')
                        <div class="text-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> {{ $mySubmission ? 'Perbarui Tugas' : 'Kirim Tugas Sekarang' }}
                </button>
            </form>
        </div>
    @endcan
</x-layout>
