{{-- resources/views/users/index.blade.php --}}
<x-layout>
    <x-slot:title>Daftar User - Kampus LMS</x-slot:title>

    <h1>Daftar User</h1>

    {{-- Notifikasi sukses dari redirect()->with('success', ...) --}}
    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <p><a href="{{ route('users.create') }}">+ Tambah User</a></p>

    <table border="1" cellpadding="8">
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
                    <td>
                        <a href="{{ route('users.show', $user) }}">Detail</a>
                        <a href="{{ route('users.edit', $user) }}">Edit</a>

                        {{--
                            Hapus WAJIB pakai <form> method DELETE, bukan <a href>,
                            karena route destroy terdaftar sebagai Route::delete().
                            Browser tidak bisa kirim method DELETE lewat <a> biasa,
                            makanya butuh @method('DELETE') + @csrf.
                        --}}
                        <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus user ini?')">Hapus</button>
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

    {{-- Tautan pagination bawaan Laravel, otomatis pakai route() di baliknya --}}
    {{ $users->links() }}
</x-layout>
