<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\LazyLoadingViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MeasurePerformanceCommand extends Command
{
    protected $signature = 'app:measure-performance';
    protected $description = 'Ukur metrik performa (Query SQL, Waktu Eksekusi, Memori) pada halaman utama KampusLMS';

    public function handle(Kernel $kernel)
    {
        $this->info('=== PEMERIKSAAN BASIS DATA KAMPUSLMS ===');
        $userCount = User::count();
        $courseCount = Course::count();
        $assignmentCount = Assignment::count();
        $submissionCount = Submission::count();

        $this->line("Users: {$userCount} | Courses: {$courseCount} | Assignments: {$assignmentCount} | Submissions: {$submissionCount}");

        if ($userCount < 20 || $submissionCount < 50) {
            $this->warn('Basis data belum terisi penuh sesuai catatan soal (minimal puluhan mahasiswa & ratusan submission).');
            $this->info('Menjalankan seeder...');
            $this->call('db:seed');
            $this->info('Seeder selesai dijalankan.');
        }

        $dosen = User::where('role', 'dosen')->firstOrFail();
        $mahasiswa = User::where('role', 'mahasiswa')->firstOrFail();

        // Cari course yang diajar oleh dosen dan diikuti mahasiswa jika ada
        $courseDosen = Course::where('lecturer_id', $dosen->id)->first() ?? Course::firstOrFail();
        $courseMhs = $mahasiswa->courses()->first() ?? Course::firstOrFail();

        $assignmentDosen = Assignment::where('course_id', $courseDosen->id)->first() ?? Assignment::firstOrFail();
        $assignmentMhs = Assignment::where('course_id', $courseMhs->id)->first() ?? Assignment::firstOrFail();

        $materialDosen = \App\Models\Material::where('course_id', $courseDosen->id)->first() ?? \App\Models\Material::first();

        $submissionDosen = Submission::where('assignment_id', $assignmentDosen->id)->first() ?? Submission::firstOrFail();

        $pages = [
            [
                'name' => 'Daftar Mata Kuliah (Dosen)',
                'url' => '/courses',
                'user' => $dosen,
            ],
            [
                'name' => 'Daftar Mata Kuliah (Mahasiswa)',
                'url' => '/courses',
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Detail Mata Kuliah (Dosen)',
                'url' => "/courses/{$courseDosen->id}",
                'user' => $dosen,
            ],
            [
                'name' => 'Detail Mata Kuliah (Mahasiswa)',
                'url' => "/courses/{$courseMhs->id}",
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Daftar Materi (Dosen)',
                'url' => "/dosen/courses/{$courseDosen->id}/materials",
                'user' => $dosen,
            ],
            [
                'name' => 'Daftar Materi (Mahasiswa)',
                'url' => "/mahasiswa/courses/{$courseMhs->id}/materials",
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Daftar Tugas (Dosen)',
                'url' => "/dosen/courses/{$courseDosen->id}/assignments",
                'user' => $dosen,
            ],
            [
                'name' => 'Daftar Tugas (Mahasiswa)',
                'url' => "/mahasiswa/courses/{$courseMhs->id}/assignments",
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Detail Tugas (Dosen)',
                'url' => "/dosen/assignments/{$assignmentDosen->id}",
                'user' => $dosen,
            ],
            [
                'name' => 'Detail Tugas (Mahasiswa)',
                'url' => "/mahasiswa/assignments/{$assignmentMhs->id}",
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Daftar Pengumpulan / Submission (Dosen)',
                'url' => "/dosen/assignments/{$assignmentDosen->id}/submissions",
                'user' => $dosen,
            ],
            [
                'name' => 'Detail Submission (Dosen)',
                'url' => "/dosen/submissions/{$submissionDosen->id}",
                'user' => $dosen,
            ],
            [
                'name' => 'Rekap Nilai (Dosen)',
                'url' => "/courses/{$courseDosen->id}/grades",
                'user' => $dosen,
            ],
            [
                'name' => 'Rekap Nilai (Mahasiswa)',
                'url' => "/courses/{$courseMhs->id}/grades",
                'user' => $mahasiswa,
            ],
            [
                'name' => 'Dashboard Dosen',
                'url' => '/dashboard',
                'user' => $dosen,
            ],
            [
                'name' => 'Dashboard Mahasiswa',
                'url' => '/dashboard',
                'user' => $mahasiswa,
            ],
        ];

        $results = [];

        foreach ($pages as $p) {
            $this->line("Mengukur: {$p['name']} ({$p['url']})...");
            $metric = $this->measureRequest($kernel, $p['url'], $p['user']);
            $results[] = [
                'page' => $p['name'],
                'url' => $p['url'],
                'queries' => $metric['queries'],
                'time_ms' => round($metric['time_ms'], 2),
                'memory_mb' => round($metric['memory_mb'], 2),
                'status' => $metric['status'],
                'violation' => $metric['violation'] ? 'YA (Error!)' : 'Tidak',
            ];
        }

        $this->table(
            ['Halaman', 'URL', 'Query SQL', 'Waktu (ms)', 'Memori (MB)', 'HTTP Status', 'Lazy Loading Violation?'],
            $results
        );

        return 0;
    }

    private function measureRequest(Kernel $kernel, string $url, User $user): array
    {
        // Bersihkan state & query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        $session = app('session.store');
        $session->start();
        $session->put(Auth::getName(), $user->getAuthIdentifier());
        $session->save();

        Auth::setUser($user);

        $request = Request::create($url, 'GET');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        $violation = false;
        $violationMessage = '';
        $status = 200;

        $memStart = memory_get_usage();
        $timeStart = hrtime(true);

        try {
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $content = $response->getContent();

            if (isset($response->exception)) {
                $e = $response->exception;
                $isLazy = ($e instanceof LazyLoadingViolationException) ||
                          ($e->getPrevious() instanceof LazyLoadingViolationException) ||
                          str_contains($e->getMessage(), 'lazy load');

                if ($isLazy) {
                    $violation = true;
                    $violationMessage = $e->getMessage();
                    $this->error("LazyLoadingViolationException pada {$url}: " . $e->getMessage());
                } else {
                    $this->error("Exception pada {$url}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $isLazy = ($e instanceof LazyLoadingViolationException) ||
                      ($e->getPrevious() instanceof LazyLoadingViolationException) ||
                      str_contains($e->getMessage(), 'lazy load');

            if ($isLazy) {
                $violation = true;
                $violationMessage = $e->getMessage();
                $this->error("LazyLoadingViolationException pada {$url}: " . $e->getMessage());
            } else {
                $this->error("Error pada {$url}: " . $e->getMessage());
            }
            $status = 500;
        }

        $timeEnd = hrtime(true);
        $memPeak = memory_get_peak_usage();

        $timeMs = ($timeEnd - $timeStart) / 1e6;
        $memMb = ($memPeak) / (1024 * 1024);

        $actualQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        Auth::logout();

        return [
            'queries' => $actualQueries,
            'time_ms' => $timeMs,
            'memory_mb' => $memMb,
            'status' => $status,
            'violation' => $violation,
            'violation_msg' => $violationMessage,
        ];
    }
}
