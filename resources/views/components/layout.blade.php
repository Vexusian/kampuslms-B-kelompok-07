<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    {{-- Menentukan judul halaman agar setiap view dapat memberikan judulnya sendiri. --}}
    <title>{{ $title ?? 'Kampus LMS' }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Memuat CSS dan JavaScript melalui Vite. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    {{-- Menampilkan navigasi sederhana agar pengguna dapat kembali ke daftar mata kuliah. --}}
    <nav>
        <a href="{{ route('courses.index') }}">Daftar Mata Kuliah</a>
        |
        <a href="{{ route('tentang') }}">Tentang Kelompok</a>
    </nav>

    <hr>

    {{-- Menampilkan isi view yang dibungkus oleh komponen x-layout. --}}
    {{ $slot }}

</body>
</html>