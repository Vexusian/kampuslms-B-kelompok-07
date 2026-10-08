<x-layout title="Detail {{ $course->name }}">
    @php
        $user = auth()->user();
        $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
    @endphp

    <div class="mb-4">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
        <div>
            <h1 style="border-bottom: none; padding-bottom: 0; margin-bottom: 0.25rem;">{{ $course->name }}</h1>
            <p style="color: #64748b; font-size: 0.95rem;">Kode: <strong>{{ $course->code }}</strong> • {{ $course->sks }} SKS</p>
        </div>
        <div class="flex gap-2">
            @can('update', $course)
                <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Kelas
                </a>
            @endcan
            @can('delete', $course)
                <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                      onsubmit="return confirm('⚠️ Yakin ingin menghapus mata kuliah ini?');" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </form>
            @endcan
        </div>
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
                    <strong>{{ $course->lecturer->name ?? 'Belum ditentukan' }}</strong>
                    @if($course->lecturer)
                        <span style="color: #64748b; font-size: 0.85rem;">({{ $course->lecturer->email }})</span>
                    @endif
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
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 1rem;">Materi Perkuliahan</strong>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.15rem 0 0;">{{ $course->materials->count() }} Materi tersedia</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route($rolePrefix . '.courses.materials.index', $course->id) }}" class="btn btn-sm btn-secondary" style="flex: 1; text-align: center;">
                    <i class="fas fa-list"></i> Lihat Materi
                </a>
                @can('create', [App\Models\Material::class, $course])
                    <a href="{{ route('dosen.courses.materials.create', $course->id) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Tambah
                    </a>
                @endcan
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; font-size: 1rem;">Penugasan / Tugas</strong>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.15rem 0 0;">{{ $course->assignments->count() }} Tugas terdaftar</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route($rolePrefix . '.courses.assignments.index', $course->id) }}" class="btn btn-sm btn-secondary" style="flex: 1; text-align: center;">
                    <i class="fas fa-list"></i> Lihat Tugas
                </a>
                @can('create', [App\Models\Assignment::class, $course])
                    <a href="{{ route('dosen.courses.assignments.create', $course->id) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Tambah
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Manajemen Enrollment Mahasiswa (Khusus Admin & Dosen Pengampu untuk Demo UTS) --}}
    @can('enroll', $course)
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <div class="flex flex-between mb-4" style="flex-wrap: wrap; gap: 0.5rem;">
                <h3 style="font-size: 1.1rem; color: #0f172a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user-plus" style="color: #10b981;"></i> Manajemen Peserta Kelas (Enrollment Mahasiswa)
                </h3>
                <span style="font-size: 0.85rem; color: #64748b;">
                    Total: <strong>{{ $course->students->count() }}</strong> mahasiswa terdaftar
                </span>
            </div>

            {{-- Form Tambah Mahasiswa ke Kelas --}}
            @if(isset($availableStudents) && $availableStudents->count() > 0)
                <form action="{{ route('courses.enroll', $course->id) }}" method="POST" class="mb-4 flex gap-2" style="background: #f8fafc; padding: 1rem; border-radius: 6px; align-items: center; flex-wrap: wrap;">
                    @csrf
                    <label for="student_id" style="font-size: 0.9rem; font-weight: 600; color: #334155;">Daftarkan Mahasiswa:</label>
                    <select name="student_id" id="student_id" class="form-control" style="flex: 1; min-width: 250px;" required>
                        <option value="">-- Pilih Mahasiswa Baru --</option>
                        @foreach($availableStudents as $mhs)
                            <option value="{{ $mhs->id }}">{{ $mhs->name }} (NIM: {{ $mhs->nim_nip ?? '-' }}) - {{ $mhs->email }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle"></i> Tambahkan ke Kelas
                    </button>
                </form>
            @endif

            {{-- Tabel Mahasiswa Terdaftar --}}
            <table>
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Email</th>
                        <th>Tanggal Terdaftar</th>
                        <th style="text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($course->students as $student)
                        <tr>
                            <td><strong>{{ $student->nim_nip ?? '-' }}</strong></td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->email }}</td>
                            <td style="color: #64748b; font-size: 0.85rem;">
                                {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->format('d M Y, H:i') : '-' }}
                            </td>
                            <td style="text-align: center;">
                                <form action="{{ route('courses.unenroll', [$course->id, $student->id]) }}" method="POST" onsubmit="return confirm('Keluarkan mahasiswa {{ $student->name }} dari kelas ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="Keluarkan">
                                        <i class="fas fa-user-times"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 1.5rem; color: #94a3b8;">
                                Belum ada mahasiswa yang didaftarkan pada mata kuliah ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcan

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