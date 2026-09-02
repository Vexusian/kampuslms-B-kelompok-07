<x-layout title="Daftar Mata Kuliah">

    <h2>Daftar Mata Kuliah</h2>

    {{-- 
        Menampilkan seluruh data mata kuliah
        yang dikirim oleh controller.
    --}}
    <ul>
        @foreach ($courses as $course)

            <li>
                <strong>{{ $course['kode'] }}</strong>
                -
                {{ $course['nama'] }}

                {{-- 
                    Link menuju halaman detail.
                    Menggunakan route() agar tidak hardcode.
                --}}
                <a href="{{ route('mata-kuliah.show', $course['id']) }}">
                    Lihat Detail
                </a>
            </li>

        @endforeach
    </ul>

</x-layout>