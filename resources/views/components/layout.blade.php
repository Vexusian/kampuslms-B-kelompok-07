<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LMS Kampus' }}</title>

    <!-- Memuat aset CSS & JS via Vite bundler Laravel 12 -->
 
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">

    <!-- Header / Navigation Bar -->
    <header class="bg-indigo-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">LMS Kampus</h1>
            <nav class="space-x-4">
                <a href="{{ route('courses.index') }}" class="hover:underline font-medium">Daftar Mata Kuliah</a>
            </nav>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

</body>
</html>