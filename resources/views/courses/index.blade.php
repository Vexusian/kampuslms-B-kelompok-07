<x-layout title="Daftar Mata Kuliah">
    <div class="flex flex-between mb-6">
        <h1>Daftar Mata Kuliah</h1>
        <a href="{{ route('courses.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Tambah Mata Kuliah
        </a>
    </div>

    {{-- Form Pencarian & Filter (state dipertahankan di query string) --}}
    <form method="GET" action="{{ route('courses.index') }}" class="mb-4 flex gap-2" style="flex-wrap: wrap; align-items: center; background: #f1f5f9; padding: 1rem; border-radius: 6px;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama mata kuliah...">
        </div>
        <div style="width: 180px;">
            <select name="status" class="form-control">
                <option value="">-- Semua Status --</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Cari
        </button>
        @if(request()->hasAny(['q', 'status', 'lecturer_id']))
            <a href="{{ route('courses.index') }}" class="btn btn-secondary" style="background: #94a3b8;" title="Reset Filter">
                <i class="fas fa-undo"></i> Reset
            </a>
        @endif
    </form>

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

    <div class="mt-4">
        {{ $courses->links() }}
    </div>
</x-layout>