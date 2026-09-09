<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    // Data statis array multidimensi
    private array $courses = [
        1 => [
            'id' => 1,
            'code' => 'CS101',
            'title' => 'Pemrograman Web Lanjut',
            'lecturer' => 'Dr. Budi Santoso, M.Kom.',
            'sks' => 3,
            'description' => 'Mempelajari pengembangan aplikasi web modern dengan PHP, framework Laravel 12, dan arsitektur RESTful.',
        ],
        2 => [
            'id' => 2,
            'code' => 'CS102',
            'title' => 'Basis Data Lanjut',
            'lecturer' => 'Siti Aminah, M.T.',
            'sks' => 3,
            'description' => 'Membahas perancangan basis data relasional, optimasi query, indeks, dan pemrosesan transaksi.',
        ],
        3 => [
            'id' => 3,
            'code' => 'CS103',
            'title' => 'Algoritma & Struktur Data',
            'lecturer' => 'Eko Prasetyo, M.Sc.',
            'sks' => 4,
            'description' => 'Pemahaman mengenai struktur data linear, non-linear, pencarian, pengurutan, dan analisis kompleksitas algoritma.',
        ],
    ];

    /**
     * Menampilkan daftar seluruh mata kuliah.
     */
    public function index(): View
    {
        return view('courses.index', [
            'courses' => $this->courses,
        ]);
    }

    /**
     * Menampilkan detail mata kuliah berdasarkan ID.
     */
    public function show(int $id): View
    {
        abort_if(!isset($this->courses[$id]), 404, 'Mata Kuliah tidak ditemukan.');

        $course = $this->courses[$id];

        return view('courses.show', compact('course'));
    }
}