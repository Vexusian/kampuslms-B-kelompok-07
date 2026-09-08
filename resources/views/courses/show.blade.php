<x-layout title="{{ $course['nama'] }}">

    {{-- Menampilkan detail mata kuliah yang dipilih pengguna. --}}
    {{-- Data berasal dari CourseController berdasarkan ID pada URL. --}}
    <h1>{{ $course['nama'] }}</h1>

    {{-- Menampilkan kode mata kuliah. --}}
    <p>
        <strong>Kode:</strong>
        {{ $course['kode'] }}
    </p>

    {{-- Menampilkan jumlah SKS mata kuliah. --}}
    <p>
        <strong>SKS:</strong>
        {{ $course['sks'] }}
    </p>

    {{-- Menampilkan dosen pengampu mata kuliah. --}}
    <p>
        <strong>Dosen:</strong>
        {{ $course['dosen'] }}
    </p>

    {{-- Menampilkan deskripsi mata kuliah. --}}
    <p>
        <strong>Deskripsi:</strong>
        {{ $course['deskripsi'] }}
    </p>

    {{-- Kembali ke daftar mata kuliah menggunakan nama route. --}}
    {{-- route() digunakan agar tautan tidak bergantung pada URL hardcoded. --}}
    <a href="{{ route('courses.index') }}">
        Kembali ke Daftar Mata Kuliah
    </a>

</x-layout>