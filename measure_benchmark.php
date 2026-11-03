<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$course = \App\Models\Course::first();
$dosen = \App\Models\User::find($course->lecturer_id);
$mhs = $course->students()->first() ?? \App\Models\User::where('role', 'mahasiswa')->first();
$assignment = $course->assignments()->first() ?? \App\Models\Assignment::first();

$pages = [
    'Daftar mata kuliah (Mahasiswa)' => [
        'uri' => '/mahasiswa/courses',
        'user' => $mhs,
    ],
    'Daftar mata kuliah (Dosen)' => [
        'uri' => '/dosen/courses',
        'user' => $dosen,
    ],
    'Detail mata kuliah (Mahasiswa)' => [
        'uri' => '/mahasiswa/courses/' . $course->id,
        'user' => $mhs,
    ],
    'Detail mata kuliah (Dosen)' => [
        'uri' => '/dosen/courses/' . $course->id,
        'user' => $dosen,
    ],
    'Daftar tugas (Mahasiswa)' => [
        'uri' => '/mahasiswa/courses/' . $course->id . '/assignments',
        'user' => $mhs,
    ],
    'Daftar tugas (Dosen)' => [
        'uri' => '/dosen/courses/' . $course->id . '/assignments',
        'user' => $dosen,
    ],
    'Daftar pengumpulan (Dosen)' => [
        'uri' => '/dosen/assignments/' . $assignment->id . '/submissions',
        'user' => $dosen,
    ],
    'Rekap nilai (Mahasiswa)' => [
        'uri' => '/courses/' . $course->id . '/grades',
        'user' => $mhs,
    ],
    'Rekap nilai (Dosen)' => [
        'uri' => '/courses/' . $course->id . '/grades',
        'user' => $dosen,
    ],
    'Dashboard mahasiswa' => [
        'uri' => '/dashboard',
        'user' => $mhs,
    ],
    'Dashboard dosen' => [
        'uri' => '/dashboard',
        'user' => $dosen,
    ],
];

echo "Benchmarking pages:\n";
echo str_repeat('-', 75) . "\n";
printf("%-34s | %-12s | %-12s | %-10s\n", "Halaman", "Jumlah Query", "Waktu (ms)", "Memori");
echo str_repeat('-', 75) . "\n";

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach ($pages as $name => $cfg) {
    \Illuminate\Support\Facades\Auth::login($cfg['user']);
    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $request = \Illuminate\Http\Request::create($cfg['uri'], 'GET');
    $request->setUserResolver(fn () => $cfg['user']);

    $start = microtime(true);
    $response = $httpKernel->handle($request);
    $duration = (microtime(true) - $start) * 1000;

    $queries = \Illuminate\Support\Facades\DB::getQueryLog();
    $queryCount = count($queries);
    $memUsage = (memory_get_peak_usage(true)) / 1024 / 1024;

    printf("%-34s | %12d | %9.2f ms | %7.2f MB (Status %d)\n", $name, $queryCount, $duration, $memUsage, $response->getStatusCode());

    $httpKernel->terminate($request, $response);
}
echo str_repeat('-', 75) . "\n";

