<x-layout title="{{ $user->name }} - KampusLMS">
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar User
        </a>
    </div>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
        <div>
            <h1 style="border-bottom: none; padding-bottom: 0; margin-bottom: 0.25rem;">{{ $user->name }}</h1>
            <span style="
                display: inline-block;
                padding: 0.15rem 0.6rem;
                border-radius: 12px;
                font-size: 0.8rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                background: {{ $user->role === 'admin' ? '#fee2e2' : ($user->role === 'dosen' ? '#ede9fe' : '#dbeafe') }};
                color: {{ $user->role === 'admin' ? '#991b1b' : ($user->role === 'dosen' ? '#5b21b6' : '#1e40af') }};
            ">
                {{ $user->role }}
            </span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit User
            </a>
            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                  onsubmit="return confirm('⚠️ Yakin ingin menghapus user ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- Info Pengguna --}}
    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
        <table style="border: none; margin-top: 0;">
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; width: 180px; border: none; color: #64748b;">Nama Lengkap</td>
                <td style="padding: 0.5rem; border: none; font-weight: 700; color: #0f172a;">{{ $user->name }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Email</td>
                <td style="padding: 0.5rem; border: none;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">NIM / NIP</td>
                <td style="padding: 0.5rem; border: none;">
                    @if ($user->nim_nip)
                        <code style="background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px;">{{ $user->nim_nip }}</code>
                    @else
                        <span style="color: #94a3b8; font-style: italic;">Belum diisi</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Role</td>
                <td style="padding: 0.5rem; border: none;">
                    <span style="
                        padding: 0.25rem 0.6rem;
                        border-radius: 12px;
                        font-size: 0.8rem;
                        font-weight: 700;
                        text-transform: uppercase;
                        background: {{ $user->role === 'admin' ? '#fee2e2' : ($user->role === 'dosen' ? '#ede9fe' : '#dbeafe') }};
                        color: {{ $user->role === 'admin' ? '#991b1b' : ($user->role === 'dosen' ? '#5b21b6' : '#1e40af') }};
                    ">
                        {{ $user->role }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Bergabung Sejak</td>
                <td style="padding: 0.5rem; border: none; color: #334155;">
                    <i class="far fa-calendar-alt"></i> {{ $user->created_at->format('d M Y') }}
                </td>
            </tr>
            @if ($user->email_verified_at)
                <tr>
                    <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; color: #64748b;">Status Email</td>
                    <td style="padding: 0.5rem; border: none;">
                        <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                            <i class="fas fa-check-circle"></i> Terverifikasi
                        </span>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Section khusus: Dosen — mata kuliah yang diampu --}}
    @if ($user->role === 'dosen')
        @php $taughtCourses = $user->taughtCourses()->latest()->get(); @endphp
        <div class="mb-6">
            <h3 style="font-size: 1.1rem; color: #0f172a; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chalkboard-teacher" style="color: #6d28d9;"></i> Mata Kuliah yang Diampu
            </h3>

            @if ($taughtCourses->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Mata Kuliah</th>
                            <th style="text-align: center;">SKS</th>
                            <th>Status</th>
                            <th style="text-align: center; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($taughtCourses as $course)
                            <tr>
                                <td><strong>{{ $course->code }}</strong></td>
                                <td>{{ $course->name }}</td>
                                <td style="text-align: center;">{{ $course->sks }}</td>
                                <td>
                                    <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }}; color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm" style="background: #3b82f6;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #94a3b8;">
                    <i class="fas fa-book-open" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <p>Belum mengampu mata kuliah apapun.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Section khusus: Mahasiswa — mata kuliah yang diambil --}}
    @if ($user->role === 'mahasiswa')
        @php $enrolledCourses = $user->courses()->with('lecturer')->get(); @endphp
        <div class="mb-6">
            <h3 style="font-size: 1.1rem; color: #0f172a; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-user-graduate" style="color: #1d4ed8;"></i> Mata Kuliah yang Diambil
                <span style="font-size: 0.8rem; font-weight: 500; color: #64748b;">({{ $enrolledCourses->count() }} Mata Kuliah)</span>
            </h3>

            @if ($enrolledCourses->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Mata Kuliah</th>
                            <th>Dosen Pengampu</th>
                            <th>Status</th>
                            <th style="text-align: center; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrolledCourses as $course)
                            <tr>
                                <td><strong>{{ $course->code }}</strong></td>
                                <td>{{ $course->name }}</td>
                                <td>{{ $course->lecturer->name ?? '-' }}</td>
                                <td>
                                    <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }}; color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm" style="background: #3b82f6;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #94a3b8;">
                    <i class="fas fa-graduation-cap" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <p>Belum terdaftar di mata kuliah apapun.</p>
                </div>
            @endif
        </div>
    @endif
</x-layout>
