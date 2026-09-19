<x-layout title="Edit User">
    <h1>Edit User: {{ $user->name }}</h1>

    <form action="{{ route('users.update', $user) }}" method="POST" style="max-width: 600px;" class="mt-4">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Nama Lengkap <span style="color: #dc2626;">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required />
            @error('name') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email <span style="color: #dc2626;">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required />
            @error('email') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="nim_nip">NIM / NIP</label>
            <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip) }}" class="form-control" />
            @error('nim_nip') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password">Password (kosongkan bila tidak ingin mengubah)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter jika diubah" />
            @error('password') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</x-layout>