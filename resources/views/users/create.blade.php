{{-- resources/views/users/create.blade.php --}}
<x-layout>
    <x-slot:title>Tambah User - Kampus LMS</x-slot:title>

    <h1>Tambah User</h1>

    {{--
        Tampilkan semua pesan error validasi di atas form. $errors selalu
        tersedia otomatis di semua view kalau request sebelumnya redirect
        dari validate() yang gagal (Laravel share otomatis lewat session).
    --}}
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <div>
            <label>Nama</label><br>
            <input type="text" name="name" value="{{ old('name') }}">
        </div>

        <div>
            <label>Email</label><br>
            <input type="email" name="email" value="{{ old('email') }}">
        </div>

       

        <div>
            <label>NIM/NIP</label><br>
            <input type="text" name="nim_nip" value="{{ old('nim_nip') }}">
        </div>

        <div>
            <label>Password</label><br>
            {{-- Tidak pakai old('password') -- password memang tidak boleh
                 dikembalikan ke form setelah gagal validasi, alasan keamanan. --}}
            <input type="password" name="password">
        </div>

        <button type="submit">Simpan</button>
    </form>

    <p><a href="{{ route('users.index') }}">&larr; Kembali</a></p>
</x-layout>
