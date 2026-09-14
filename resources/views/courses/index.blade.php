<x-layout title="Daftar Mata Kuliah">
    <div class="flex flex-between mb-6">
        <h1>Daftar Mata Kuliah</h1>
        <a href="{{ route('courses.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Tambah Mata Kuliah
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Dosen</th>
                <th>Status</th>
                <th style="text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td><strong>{{ $course->code }}</strong></td>
                    <td>{{ $course->name }}</td>
                    <td style="text-align: center;">{{ $course->sks }}</td>
                    <td>{{ $course->lecturer->name ?? '-' }}</td>
                    <td>
                        <span style="
                            padding: 0.25rem 0.6rem;
                            border-radius: 12px;
                            font-size: 0.8rem;
                            font-weight: 600;
                            background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }};
                            color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};
                        ">
                            {{ ucfirst($course->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-2" style="justify-content: center;">
                            <a href="{{ route('courses.show', $course->id) }}" 
                               class="btn btn-sm" 
                               style="background: #3b82f6;"
                               title="Detail">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            <a href="{{ route('courses.edit', $course->id) }}" 
                               class="btn btn-sm btn-warning"
                               title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('courses.destroy', $course->id) }}" 
                                  method="POST" 
                                  style="display: inline;"
                                  onsubmit="return confirm('⚠️ Yakin ingin menghapus mata kuliah ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-danger"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                        <p>Belum ada data mata kuliah.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-layout>