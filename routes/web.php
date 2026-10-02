<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - KampusLMS
|--------------------------------------------------------------------------
*/

// --- Rute Publik / Guest ---
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::get('/login', function () {
    return response('Halaman Login (Akan dipasang penuh pada Minggu 7 via Breeze)', 200);
})->name('login');

// --- Helper Switch User untuk pengujian / demonstrasi dosen (Local Only) ---
if (app()->environment('local')) {
    Route::get('/dev/login/{user}', function (User $user) {
        Auth::login($user);
        return redirect()->route('dashboard')->with('success', "Login simulasi sebagai: {$user->name} (Role: {$user->role})");
    })->name('dev.login');

    Route::get('/dev/logout', function () {
        Auth::logout();
        return redirect()->route('dashboard')->with('success', 'Berhasil logout simulasi.');
    })->name('dev.logout');
}

// --- Rute Terautentikasi (Auth Group) ---
Route::middleware('auth')->group(function () {

    // 1. GRUP ADMIN (prefix: /admin, name: admin.*, middleware: role:admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('courses', CourseController::class);
    });

    // 2. GRUP DOSEN (prefix: /dosen, name: dosen.*, middleware: role:dosen)
    Route::middleware('role:dosen')->prefix('dosen')->name('dosen.')->group(function () {
        Route::resource('courses', CourseController::class)->only(['index', 'show', 'edit', 'update']);

        // Nested routes untuk materi dan tugas dengan scopeBindings() & shallow()
        Route::scopeBindings()->group(function () {
            Route::resource('courses.materials', MaterialController::class)->shallow();
            Route::resource('courses.assignments', AssignmentController::class)->shallow();

            // Rute daftar submission dan detail submission untuk dosen
            Route::get('assignments/{assignment}/submissions', [SubmissionController::class, 'index'])->name('assignments.submissions.index');
            Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        });
    });

    // 3. GRUP MAHASISWA (prefix: /mahasiswa, name: mahasiswa.*, middleware: role:mahasiswa)
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::resource('courses', CourseController::class)->only(['index', 'show']);

        // Nested routes untuk materi dan tugas dengan scopeBindings()
        Route::scopeBindings()->group(function () {
            Route::get('courses/{course}/materials', [MaterialController::class, 'index'])->name('courses.materials.index');
            Route::get('materials/{material}', [MaterialController::class, 'show'])->name('materials.show');

            Route::get('courses/{course}/assignments', [AssignmentController::class, 'index'])->name('courses.assignments.index');
            Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');

            // Pengumpulan tugas dan melihat tugas sendiri
            Route::post('assignments/{assignment}/submissions', [SubmissionController::class, 'store'])->name('assignments.submissions.store');
            Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        });
    });

});

// --- Rute Kompatibilitas / UI Minggu 4 ---
Route::resource('courses', CourseController::class);
Route::resource('mata-kuliah', CourseController::class);