<x-layout title="Halaman Tidak Ditemukan">

    {{-- Menampilkan pesan ketika halaman atau data yang diminta tidak ditemukan. --}}
    {{-- Halaman custom 404 membuat error lebih mudah dipahami oleh pengguna. --}}
    <h1>404 - Halaman Tidak Ditemukan</h1>

    <p>
        Halaman atau mata kuliah yang Anda cari tidak ditemukan.
    </p>

    {{-- Mengarahkan pengguna kembali ke daftar mata kuliah. --}}
    {{-- route() digunakan agar tautan tetap mengikuti nama route yang terdaftar. --}}
    <a href="{{ route('courses.index') }}">
        Kembali ke Mata Kuliah
    </a>

</x-layout>