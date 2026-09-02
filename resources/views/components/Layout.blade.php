{{-- 
    Layout utama aplikasi.
    Dibuat sebagai Blade Component agar dapat digunakan ulang
    oleh banyak halaman tanpa @extends dan @section.
--}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LMS Kampus' }}</title>
</head>
<body>

    <header>
        <h1>LMS Kampus</h1>

        <nav>
            {{-- 
                Menggunakan helper route()
                agar URL tidak ditulis manual.
            --}}
            <a href="{{ route('mata-kuliah.index') }}">
                Mata Kuliah
            </a>
        </nav>

        <hr>
    </header>

    <main>
        {{ $slot }}
    </main>

</body>
</html>