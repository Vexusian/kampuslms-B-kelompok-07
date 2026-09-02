<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

/*
|--------------------------------------------------------------------------
| Course Routes
|--------------------------------------------------------------------------
| Route untuk modul mata kuliah.
| Menggunakan nama route agar URL tidak hardcode dan mudah diubah.
|--------------------------------------------------------------------------
*/

// Menampilkan daftar mata kuliah
Route::post('/mata-kuliah', [CourseController::class, 'index'])
    ->name('mata-kuliah.index');

// Menampilkan detail satu mata kuliah
Route::post('/mata-kuliah/{id}', [CourseController::class, 'show'])
    ->name('mata-kuliah.show');
    
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
