{{-- resources/views/users/show.blade.php --}}
<x-layout>
    <x-slot:title>{{ $user->name }} - Kampus LMS</x-slot:title>

    <p><a href="{{ route('users.index') }}">&larr; Kembali ke daftar user</a></p>

    <h1>{{ $user->name }}</h1>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Role:</strong> {{ $user->role }}</p>
    <p><strong>NIM/NIP:</strong> {{ $user->nim_nip ?? '-' }}</p>
    <p><strong>Terdaftar sejak:</strong> {{ $user->created_at->format('d M Y') }}</p>

    <p>
        <a href="{{ route('users.edit', $user) }}">Edit</a>
    </p>
</x-layout>
