{{-- resources/views/users/index.blade.php --}}
<x-layout>
    <x-slot:title>Daftar User - Kampus LMS</x-slot:title>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 0.75rem;">
        <h1>Daftar User</h1>
        <div class="flex gap-2" style="align-items: center; flex-wrap: wrap;">
            <a href="{{ route('admin.students.export') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-file-export"></i> Ekspor Mahasiswa (CSV)
            </a>
            <button type="button" onclick="document.getElementById('importStudentModal').style.display='flex'" class="btn btn-secondary btn-sm" style="background: #0284c7;">
                <i class="fas fa-file-import"></i> Impor Mahasiswa (CSV)
            </button>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Tambah User
            </a>
        </div>
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

    <div id="importStudentModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden;">
            <div style="background: #f8fafc; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;"><i class="fas fa-file-import" style="color: #0284c7;"></i> Impor Data Mahasiswa</h3>
                <button type="button" onclick="document.getElementById('importStudentModal').style.display='none'" style="background: none; border: none; font-size: 1.25rem; color: #64748b; cursor: pointer;">&times;</button>
            </div>
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" style="padding: 1.25rem;">
                @csrf
                <p style="font-size: 0.9rem; color: #475569; margin-bottom: 1rem;">
                    Unggah berkas CSV berisi data mahasiswa. Sistem akan membaca kolom <code>Nama</code>, <code>Email</code>, <code>NIM</code>, dan <code>Password</code> (opsional).
                </p>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1e40af; display: flex; justify-content: space-between; align-items: center;">
                    <span>Butuh contoh format CSV?</span>
                    <a href="{{ route('admin.students.template') }}" class="btn btn-sm" style="background: #2563eb; color: white;">Unduh Template</a>
                </div>
                <div class="form-group mb-4">
                    <label for="student_csv_file" style="font-weight: 600; font-size: 0.9rem;">Pilih Berkas CSV:</label>
                    <input type="file" id="student_csv_file" name="file" accept=".csv,text/csv" required class="form-control" style="padding: 0.4rem;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" onclick="document.getElementById('importStudentModal').style.display='none'" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Unggah & Impor</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
