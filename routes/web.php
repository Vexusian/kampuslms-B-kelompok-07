<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tentang', function () {
    return view('tentang');
});

Route::get('/courses/daftar-mata-kuliah', function () {
    return view('courses/daftar-mata-kuliah');
});