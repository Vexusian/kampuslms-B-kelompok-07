<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

// 3. CRUD Mata Kuliah (Otomatis membuat 7 route: index, create, store, show, edit, update, destroy)
// Route::resource otomatis menempatkan route statis (/create, /edit) DI ATAS route dinamis (/{course})
Route::resource('courses', CourseController::class);
