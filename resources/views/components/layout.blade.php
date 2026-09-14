<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'KampusLMS' }}</title>
    
    {{-- Font Awesome untuk icon --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    {{-- Vite untuk CSS/JS Laravel --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: #f8fafc; 
            color: #334155;
            line-height: 1.6;
        }
        nav { 
            background: #0f172a; 
            padding: 1rem 2rem; 
            display: flex; 
            gap: 1.5rem; 
            align-items: center;
        }
        nav a { 
            color: #f8fafc; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        nav a:hover { color: #38bdf8; }
        main { 
            max-width: 1100px; 
            margin: 2rem auto; 
            padding: 2rem; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #0f172a; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1rem; 
        }
        th, td { 
            padding: 0.75rem 1rem; 
            border-bottom: 1px solid #e2e8f0; 
            text-align: left; 
        }
        th { 
            background: #f1f5f9; 
            font-weight: 600; 
            color: #475569;
        }
        tr:hover { background: #f8fafc; }
        .btn { 
            display: inline-block; 
            padding: 0.5rem 1rem; 
            background: #2563eb; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; }
        .btn-secondary:hover { background: #475569; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: #f59e0b; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.85rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 0.4rem;
            color: #334155;
        }
        .form-control { 
            width: 100%; 
            padding: 0.6rem; 
            border: 1px solid #cbd5e1; 
            border-radius: 4px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .text-error { color: #dc2626; font-size: 0.85rem; margin-top: 0.25rem; }
        .flex { display: flex; }
        .flex-between { justify-content: space-between; align-items: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1rem; }
    </style>
</head>
<body>

    <nav>
        <a href="{{ route('dashboard') }}"><i class="fas fa-home"></i> Dashboard</a>
        <a href="{{ route('courses.index') }}"><i class="fas fa-book"></i> Mata Kuliah</a>
        <a href="{{ route('tentang') }}"><i class="fas fa-info-circle"></i> Tentang</a>
    </nav>

    <main>
        {{ $slot }}
    </main>

</body>
</html>
