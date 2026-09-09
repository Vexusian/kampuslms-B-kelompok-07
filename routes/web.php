<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

// Grouping route dengan middleware auth (untuk pengujian lokal, dipastikan user sudah terautentikasi atau sesuaikan jika belum menggunakan sistem login)

    // 1. Index: Menampilkan daftar mata kuliah
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');

    // 2. Create: Form tambah mata kuliah
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');

    // 3. Store: Proses simpan data mata kuliah baru
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

    // 4. Show: Menampilkan detail satu mata kuliah
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // 5. Edit: Form edit mata kuliah
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');

    // 6. Update: Proses perbarui data mata kuliah
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');

    // 7. Destroy: Proses hapus mata kuliah
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');