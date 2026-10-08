<x-layout title="Dashboard - KampusLMS">
    {{-- Header Dashboard Sesuai Peran --}}
    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
        <div>
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">
                @if(!$user)
                    Dashboard Publik KampusLMS
                @elseif($user->role === 'admin')
                    Dashboard Administrator
                @elseif($user->role === 'dosen')
                    Dashboard Dosen Pengampu
                @else
                    Dashboard Mahasiswa
                @endif
            </h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                @if(!$user)
                    Selamat datang di Sistem Informasi Manajemen Pembelajaran <strong>KampusLMS</strong>.
                @elseif($user->role === 'admin')
                    Kelola seluruh data sistem, mata kuliah, dosen pengampu, dan mahasiswa kampus.
                @elseif($user->role === 'dosen')
                    Selamat datang, <strong>{{ $user->name }}</strong>. Pantau mata kuliah yang Anda ampu dan evaluasi tugas mahasiswa.
                @else
                    Selamat datang, <strong>{{ $user->name }}</strong> ({{ $user->nim_nip }}). Berikut adalah ringkasan kelas dan penugasan Anda.
                @endif
            </p>
        </div>
        <div class="flex gap-2" style="flex-wrap: wrap;">
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                <i class="fas fa-book"></i> 
                {{ $user && $user->role === 'mahasiswa' ? 'Mata Kuliah Saya' : ($user && $user->role === 'dosen' ? 'Kelas Saya' : 'Katalog Mata Kuliah') }}
            </a>
            @can('create', App\Models\Course::class)
                <a href="{{ route('courses.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Mata Kuliah
                </a>
            @endcan
        </div>
    </div>

    {{-- Ringkasan Statistik (Stat Cards Terpersonalisasi) --}}
    <div class="mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        {{-- Card 1: Mata Kuliah --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">
                    {{ $user && $user->role === 'mahasiswa' ? 'Mata Kuliah Diikuti' : ($user && $user->role === 'dosen' ? 'Mata Kuliah Diampu' : 'Total Mata Kuliah') }}
                </p>
                <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ $totalCourses }}</h3>
                <span style="font-size: 0.8rem; color: #059669; font-weight: 600;">
                    <i class="fas fa-check"></i> {{ $activeCourses }} Aktif
                </span>
                @if($draftCourses > 0)
                    <span style="font-size: 0.8rem; color: #d97706; font-weight: 600; margin-left: 0.5rem;">
                        • {{ $draftCourses }} Draft
                    </span>
                @endif
            </div>
            <div style="width: 48px; height: 48px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-book-open"></i>
            </div>
        </div>

        {{-- Card 2: Pengajar / Mahasiswa --}}
        @if($user && $user->role === 'mahasiswa')
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">Tugas Dikumpulkan</p>
                    <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ $totalSubmissions }}</h3>
                    <span style="font-size: 0.8rem; color: #64748b;">Pengumpulan Tercatat</span>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        @else
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">
                        {{ $user && $user->role === 'dosen' ? 'Peserta Kelas' : 'Mahasiswa' }}
                    </p>
                    <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ $totalStudents }}</h3>
                    <span style="font-size: 0.8rem; color: #64748b;">
                        {{ $user && $user->role === 'dosen' ? 'Total Mahasiswa Anda' : 'Peserta Aktif Terdaftar' }}
                    </span>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 8px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        @endif

        {{-- Card 3: Materi & Tugas --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">Materi & Tugas</p>
                <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ $totalMaterials + $totalAssignments }}</h3>
                <span style="font-size: 0.8rem; color: #64748b;">{{ $totalMaterials }} Materi • {{ $totalAssignments }} Tugas</span>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 8px; background: #ecfdf5; color: #047857; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        {{-- Card 4: Pengajar / Pengguna --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">
                    {{ $user && $user->role === 'mahasiswa' ? 'Dosen Pengampu' : 'Pengajar Terdaftar' }}
                </p>
                <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ $totalLecturers }}</h3>
                <span style="font-size: 0.8rem; color: #64748b;">
                    {{ $user && $user->role === 'mahasiswa' ? 'Dosen di Kelas Anda' : 'Dosen Aktif' }}
                </span>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 8px; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
        </div>
    </div>

    {{-- Tabel Mata Kuliah Terkait --}}
    <div class="mb-6">
        <div class="flex flex-between mb-4">
            <h2 style="font-size: 1.2rem; color: #0f172a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-book" style="color: #2563eb;"></i> 
                {{ $user && $user->role === 'mahasiswa' ? 'Mata Kuliah yang Anda Ikuti' : ($user && $user->role === 'dosen' ? 'Mata Kuliah yang Anda Ampu' : 'Mata Kuliah Terbaru') }}
            </h2>
            <a href="{{ route('courses.index') }}" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                Lihat Semua ({{ $totalCourses }}) <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Mata Kuliah</th>
                    <th style="text-align: center;">SKS</th>
                    <th>Dosen</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentCourses as $course)
                    <tr>
                        <td><strong>{{ $course->code }}</strong></td>
                        <td>{{ $course->name }}</td>
                        <td style="text-align: center;">{{ $course->sks }}</td>
                        <td>{{ $course->lecturer->name ?? '-' }}</td>
                        <td>
                            <span style="
                                padding: 0.25rem 0.6rem;
                                border-radius: 12px;
                                font-size: 0.8rem;
                                font-weight: 600;
                                background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }};
                                color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};
                            ">
                                {{ ucfirst($course->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-2" style="justify-content: center;">
                                <a href="{{ route('courses.show', $course->id) }}" 
                                   class="btn btn-sm" 
                                   style="background: #3b82f6;"
                                   title="Detail">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                @can('update', $course)
                                    <a href="{{ route('courses.edit', $course->id) }}" 
                                       class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">
                            <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                            <p style="margin-top: 0.5rem;">
                                @if($user && $user->role === 'mahasiswa')
                                    Anda belum terdaftar pada mata kuliah apa pun.
                                @elseif($user && $user->role === 'dosen')
                                    Anda belum ditugaskan mengampu mata kuliah.
                                @else
                                    Belum ada data mata kuliah.
                                @endif
                            </p>
                            @can('create', App\Models\Course::class)
                                <a href="{{ route('courses.create') }}" class="btn btn-success btn-sm mt-4">
                                    <i class="fas fa-plus"></i> Tambah Sekarang
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Daftar Tugas Terbaru / Mendekati Deadline (Khusus Mahasiswa & Dosen) --}}
    @if($recentAssignments->count() > 0)
        <div class="mb-6">
            <div class="flex flex-between mb-4">
                <h2 style="font-size: 1.2rem; color: #0f172a; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-tasks" style="color: #b45309;"></i> 
                    {{ $user && $user->role === 'mahasiswa' ? 'Tugas Pada Mata Kuliah Anda' : 'Penugasan Terbaru' }}
                </h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tugas</th>
                        <th>Mata Kuliah</th>
                        <th>Batas Waktu</th>
                        @if($user && $user->role === 'mahasiswa')
                            <th>Status Pengumpulan</th>
                        @endif
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAssignments as $assignment)
                        @php
                            $rolePrefix = ($user && $user->role === 'mahasiswa') ? 'mahasiswa' : 'dosen';
                            $userSubmission = ($user && $user->role === 'mahasiswa') ? $assignment->submissions->first() : null;
                        @endphp
                        <tr>
                            <td><strong>{{ $assignment->title }}</strong></td>
                            <td>{{ $assignment->course->name ?? '-' }}</td>
                            <td style="color: #64748b; font-size: 0.9rem;">
                                {{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:i') : '-' }}
                            </td>
                            @if($user && $user->role === 'mahasiswa')
                                <td>
                                    @if($userSubmission)
                                        <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                                            <i class="fas fa-check"></i> Sudah Dikumpulkan
                                        </span>
                                    @else
                                        <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #991b1b;">
                                            <i class="fas fa-clock"></i> Belum Mengumpulkan
                                        </span>
                                    @endif
                                </td>
                            @endif
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

    {{-- Akses Cepat / Menu Pintas --}}
    <div style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
        <h3 style="font-size: 1.05rem; color: #0f172a; margin-bottom: 1rem; font-weight: 600;">
            <i class="fas fa-compass" style="color: #64748b; margin-right: 0.35rem;"></i> Akses Cepat
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <a href="{{ route('courses.index') }}" style="text-decoration: none; color: inherit;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; transition: border-color 0.2s, box-shadow 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                        <i class="fas fa-book" style="color: #2563eb; font-size: 1.1rem;"></i>
                        <strong style="color: #0f172a; font-size: 0.95rem;">Daftar Mata Kuliah</strong>
                    </div>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Lihat mata kuliah yang tersedia sesuai hak akses Anda.</p>
                </div>
            </a>

            @if($user && $user->role === 'admin')
                <a href="{{ route('admin.users.index') }}" style="text-decoration: none; color: inherit;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; transition: border-color 0.2s, box-shadow 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                            <i class="fas fa-users-cog" style="color: #6d28d9; font-size: 1.1rem;"></i>
                            <strong style="color: #0f172a; font-size: 0.95rem;">Manajemen Pengguna</strong>
                        </div>
                        <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Kelola akun admin, dosen pengampu, dan mahasiswa.</p>
                    </div>
                </a>
            @endif

            <a href="{{ route('tentang') }}" style="text-decoration: none; color: inherit;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; transition: border-color 0.2s, box-shadow 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                        <i class="fas fa-info-circle" style="color: #059669; font-size: 1.1rem;"></i>
                        <strong style="color: #0f172a; font-size: 0.95rem;">Informasi LMS</strong>
                    </div>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Lihat informasi anggota kelompok pengembang KampusLMS.</p>
                </div>
            </a>
        </div>
    </div>
</x-layout>
