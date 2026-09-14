{{-- resources/views/users/edit.blade.php --}}
<x-layout>
    <x-slot:title>Edit User - Kampus LMS</x-slot:title>

    <h1>Edit User: {{ $user->name }}</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT') {{-- route update terdaftar sebagai PUT, wajib method spoofing ini --}}

        <div>
            <label>Nama</label><br>
            <input type="text" name="name" value="{{ old('name', $user->name) }}">
        </div>

        <div>
            <label>Email</label><br>
            <input type="email" name="email" value="{{ old('email', $user->email) }}">
        </div>

        <div>
            <label>NIM/NIP</label><br>
            <input type="text" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip) }}">
        </div>

        <div>
            <label>Password (kosongkan kalau tidak ingin mengubah)</label><br>
            <input type="password" name="password">
        </div>

        <button type="submit">Simpan Perubahan</button>
    </form>

    <p><a href="{{ route('users.index') }}">&larr; Kembali</a></p>
</x-layout>
