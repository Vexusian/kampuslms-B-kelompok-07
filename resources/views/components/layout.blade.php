{{-- components/layout.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <title>{{ $title ?? 'KampusLMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav>...</nav>
    <main>{{ $slot }}</main>
</body>
</html>