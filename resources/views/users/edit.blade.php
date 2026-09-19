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

        <form action="{{ route('users.update', $user) }}" method="POST" class="mt-4">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>NIM/NIP</label>
                <input type="text" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip) }}" class="form-control" />
            </div>
            <div class="form-group">
                <label>Password (kosongkan bila tidak ingin mengubah)</label>
                <input type="password" name="password" class="form-control" />
            </div>
            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary ml-2">&larr; Kembali</a>
        </form>

    <p><a href="{{ route('users.index') }}">&larr; Kembali</a></p>
</x-layout>
