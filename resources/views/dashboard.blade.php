<x-layout title="Dashboard">

    {{-- Menampilkan halaman utama KampusLMS. --}}
    {{-- Dashboard digunakan sebagai titik awal ketika pengguna membuka aplikasi. --}}
    <h1>Dashboard KampusLMS</h1>

    <p>Selamat datang di KampusLMS.</p>

    {{-- Mengarahkan pengguna ke daftar mata kuliah menggunakan nama route. --}}
    {{-- route() digunakan agar URL tidak ditulis secara hardcoded. --}}
    <a href="{{ route('courses.index') }}">
        Lihat Mata Kuliah
    </a>

</x-layout>