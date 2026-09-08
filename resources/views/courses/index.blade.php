{{-- Menggunakan x-layout agar struktur HTML utama tidak perlu ditulis ulang. --}}
<x-layout title="Daftar Mata Kuliah">

    {{-- Menampilkan judul halaman daftar mata kuliah. --}}
    <h1>Daftar Mata Kuliah</h1>

    {{-- Melakukan perulangan karena halaman index menampilkan seluruh mata kuliah. --}}
    @foreach ($courses as $course)

        <article>
            {{-- Menampilkan nama mata kuliah dari setiap data course. --}}
            <h2>{{ $course['nama'] }}</h2>

            {{-- Menampilkan kode dan jumlah SKS mata kuliah. --}}
            <p>
                <strong>Kode:</strong> {{ $course['kode'] }} |
                <strong>SKS:</strong> {{ $course['sks'] }}
            </p>

            {{-- Menampilkan deskripsi mata kuliah. --}}
            <p>{{ $course['deskripsi'] }}</p>

            {{-- Menggunakan route() agar URL detail tidak ditulis secara hardcode. --}}
            <a href="{{ route('courses.show', ['course' => $course['id']]) }}">
                Lihat Detail
            </a>
        </article>

        <hr>

    @endforeach

</x-layout>