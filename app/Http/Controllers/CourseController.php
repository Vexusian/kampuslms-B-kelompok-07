<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Data Dummy
    |--------------------------------------------------------------------------
    | Data statis digunakan sementara sebelum database tersedia.
    | Nantinya bagian ini dapat diganti dengan Model Eloquent.
    |--------------------------------------------------------------------------
    */
    private array $courses = [
        [
            'id' => 1,
            'kode' => 'SI101',
            'nama' => 'Pemrograman Web',
            'sks' => 3,
        ],
        [
            'id' => 2,
            'kode' => 'SI102',
            'nama' => 'Basis Data',
            'sks' => 3,
        ],
        [
            'id' => 3,
            'kode' => 'SI103',
            'nama' => 'Analisis Sistem',
            'sks' => 2,
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | index()
    |--------------------------------------------------------------------------
    | Menampilkan seluruh mata kuliah.
    | Mengirim data ke view index agar dapat dirender oleh Blade.
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return view('courses.index', [
            'courses' => $this->courses
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | show()
    |--------------------------------------------------------------------------
    | Menampilkan detail satu mata kuliah berdasarkan ID.
    | Jika ID tidak ditemukan, Laravel mengembalikan 404.
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $course = collect($this->courses)
            ->firstWhere('id', (int) $id);

        abort_if(!$course, 404);

        return view('courses.show', [
            'course' => $course
        ]);
    }
}