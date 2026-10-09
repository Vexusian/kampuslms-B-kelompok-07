{{-- resources/views/users/index.blade.php --}}
<x-layout>
    <x-slot:title>Daftar User - Kampus LMS</x-slot:title>

    <div class="flex flex-between mb-6">
        <h1>Daftar User</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>

    {{-- Form Pencarian & Filter Role (state dipertahankan di query string) --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex gap-2" style="flex-wrap: wrap; align-items: center; background: #f1f5f9; padding: 1rem; border-radius: 6px;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama, email, atau NIM/NIP...">
        </div>
        <div style="width: 180px;">
            <select name="role" class="form-control">
                <option value="">-- Semua Role --</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="dosen" {{ request('role') === 'dosen' ? 'selected' : '' }}>Dosen</option>
                <option value="mahasiswa" {{ request('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Cari
        </button>
        @if(request()->hasAny(['q', 'role']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="background: #94a3b8;" title="Reset Filter">
                <i class="fas fa-undo"></i> Reset
            </a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>NIM/NIP</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role }}</td>
                    <td>{{ $user->nim_nip ?? '-' }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-primary btn-sm">Detail</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada user.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</x-layout>
