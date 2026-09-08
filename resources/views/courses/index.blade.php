<x-layout title="Mata Kuliah">

    {{-- Menampilkan judul halaman daftar mata kuliah. --}}
    {{-- Halaman ini digunakan untuk melihat seluruh mata kuliah yang tersedia. --}}
    <h1>Daftar Mata Kuliah</h1>

    {{-- Menampilkan data mata kuliah dalam bentuk tabel. --}}
    {{-- Tabel digunakan karena data memiliki beberapa atribut yang cocok dibandingkan dalam kolom. --}}
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>SKS</th>
                <th>Dosen</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

            {{-- Melakukan perulangan untuk setiap mata kuliah dalam array. --}}
            {{-- @foreach digunakan karena jumlah data dapat lebih dari satu. --}}
            @foreach ($courses as $course)
                <tr>
                    {{-- Menampilkan kode mata kuliah dengan escaping otomatis Blade. --}}
                    <td>{{ $course['kode'] }}</td>

                    {{-- Menampilkan nama mata kuliah. --}}
                    <td>{{ $course['nama'] }}</td>

                    {{-- Menampilkan jumlah SKS. --}}
                    <td>{{ $course['sks'] }}</td>

                    {{-- Menampilkan nama dosen. --}}
                    <td>{{ $course['dosen'] }}</td>

                    {{-- Membuat tautan menuju halaman detail mata kuliah. --}}
                    {{-- route() digunakan agar URL dibuat berdasarkan nama route dan parameter ID. --}}
                    <td>
                        <a href="{{ route('courses.show', ['course' => $course['id']]) }}">
                            Detail
                        </a>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>

</x-layout>