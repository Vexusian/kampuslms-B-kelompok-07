<x-layout :title="$course['nama']">

    <h2>Detail Mata Kuliah</h2>

    {{-- 
        Menampilkan detail mata kuliah
        yang dipilih pengguna.
    --}}
    <p>
        <strong>Kode:</strong>
        {{ $course['kode'] }}
    </p>

    <p>
        <strong>Nama:</strong>
        {{ $course['nama'] }}
    </p>

    <p>
        <strong>SKS:</strong>
        {{ $course['sks'] }}
    </p>

    {{-- 
        Tombol kembali ke daftar mata kuliah.
        Tetap menggunakan helper route().
    --}}
    <a href="{{ route('mata-kuliah.index') }}">
        Kembali ke Daftar Mata Kuliah
    </a>

</x-layout>