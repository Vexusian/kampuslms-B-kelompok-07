<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::resource('courses', CourseController::class);

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/tentang', function () {
    // $nama = "<script>alert('XSS')</script>";

    return view('tentang', [
        'nama' => $nama,
    ]);
})->name('tentang');

Route::resource('users', UserController::class);