{{-- Menggunakan x-layout agar struktur HTML utama tidak perlu ditulis ulang. --}}
<x-layout title="{{ $course['nama'] }}">

    {{-- Menampilkan nama mata kuliah yang sedang dilihat. --}}
    <h1>{{ $course['nama'] }}</h1>

    {{-- Menampilkan informasi dasar mata kuliah. --}}
    <p>
        <strong>Kode:</strong> {{ $course['kode'] }}
    </p>

    <p>
        <strong>SKS:</strong> {{ $course['sks'] }}
    </p>

    {{-- Menampilkan deskripsi mata kuliah. --}}
    <p>
        <strong>Deskripsi:</strong> {{ $course['deskripsi'] }}
    </p>

    {{-- Menggunakan route() agar kembali ke daftar tanpa menulis URL secara hardcode. --}}
    <a href="{{ route('courses.index') }}">
        Kembali ke Daftar Mata Kuliah
    </a>

</x-layout>