{{-- resources/views/users/index.blade.php --}}
<x-layout>
    <x-slot:title>Daftar User - Kampus LMS</x-slot:title>

    <h1>Daftar User</h1>

    {{-- Notifikasi sukses dari redirect()->with('success', ...) --}}
    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <a href="{{ route('users.create') }}" class="btn btn-success mb-4">+ Tambah User</a>

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
                        <a href="{{ route('users.show', $user) }}" class="btn btn-primary btn-sm">Detail</a>
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block">
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
