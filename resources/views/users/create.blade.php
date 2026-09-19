<x-layout title="Tambah User">
    <h1>Tambah User Baru</h1>

    <form action="{{ route('users.store') }}" method="POST" style="max-width: 600px;" class="mt-4">
        @csrf

        <div class="form-group">
            <label for="name">Nama Lengkap <span style="color: #dc2626;">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control" placeholder="Nama pengguna" required />
            @error('name') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email <span style="color: #dc2626;">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="contoh@kampus.ac.id" required />
            @error('email') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="nim_nip">NIM / NIP</label>
            <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip') }}" class="form-control" placeholder="Nomor Induk Mahasiswa / Pegawai" />
            @error('nim_nip') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password">Password <span style="color: #dc2626;">*</span></label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required />
            @error('password') <div class="text-error">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Simpan
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</x-layout>