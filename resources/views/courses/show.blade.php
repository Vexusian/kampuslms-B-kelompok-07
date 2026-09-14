<x-layout title="Detail {{ $course->name }}">
    <div class="mb-4">
        <a href="{{ route('courses.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <h1>{{ $course->name }}</h1>

    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0;">
        <table style="border: none;">
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; width: 180px; border: none;">Kode</td>
                <td style="padding: 0.5rem; border: none;">{{ $course->code }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none;">Nama Mata Kuliah</td>
                <td style="padding: 0.5rem; border: none;">{{ $course->name }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none;">SKS</td>
                <td style="padding: 0.5rem; border: none;">{{ $course->sks }} SKS</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none;">Status</td>
                <td style="padding: 0.5rem; border: none;">
                    <span style="
                        padding: 0.25rem 0.6rem;
                        border-radius: 12px;
                        font-size: 0.85rem;
                        font-weight: 600;
                        background: {{ $course->status === 'active' ? '#d1fae5' : ($course->status === 'draft' ? '#fef3c7' : '#e2e8f0') }};
                        color: {{ $course->status === 'active' ? '#065f46' : ($course->status === 'draft' ? '#92400e' : '#475569') }};
                    ">
                        {{ ucfirst($course->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none;">Dosen Pengampu</td>
                <td style="padding: 0.5rem; border: none;">
                    {{ $course->lecturer->name ?? 'Belum ditentukan' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 1rem 0.5rem 0; font-weight: 600; border: none; vertical-align: top;">Deskripsi</td>
                <td style="padding: 0.5rem; border: none;">
                    {{ $course->description ?: 'Tidak ada deskripsi.' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="flex gap-2 mt-4">
        <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form action="{{ route('courses.destroy', $course->id) }}" method="POST" 
              onsubmit="return confirm('️ Yakin ingin menghapus mata kuliah ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
</x-layout>