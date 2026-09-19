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

        <form action="{{ route('users.store') }}" method="POST" class="mt-4">
            @csrf
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>NIM/NIP</label>
                <input type="text" name="nim_nip" value="{{ old('nim_nip') }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" />
            </div>
            <button type="submit" class="btn btn-success">Simpan</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary ml-2">&larr; Kembali</a>
        </form>

    <p><a href="{{ route('users.index') }}">&larr; Kembali</a></p>
</x-layout>
