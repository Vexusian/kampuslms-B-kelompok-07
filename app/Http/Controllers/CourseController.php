<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    // Menyimpan data mata kuliah secara statis sebagai array.
    // Data masih hardcoded karena belum menggunakan database.
    private array $courses = [
        [
            'id'        => 1,
            'kode'      => 'MK001',
            'nama'      => 'Kapita Selekta',
            'sks'       => 2,
            'deskripsi' => 'Belajar AI Engineering',
            'dosen'     => 'Aidil',
        ],
        [
            'id'        => 2,
            'kode'      => 'MK002',
            'nama'      => 'Pemrograman Web',
            'sks'        => 3,
            'deskripsi' => 'Belajar HTML, CSS, JavaScript, dan framework web modern',
            'dosen'     => 'Aidil',
        ],
        [
            'id'        => 3,
            'kode'      => 'MK003',
            'nama'      => 'Basis Data',
            'sks'       => 3,
            'deskripsi' => 'Belajar perancangan basis data, query SQL, dan manajemen relasi data',
            'dosen'     => 'Arif Wicaksono',
        ],
        [
            'id'        => 4,
            'kode'      => 'MK004',
            'nama'      => 'Algoritma Pemrograman',
            'sks'       => 4,
            'deskripsi' => 'Belajar logika pemrograman, pemecahan masalah, dan struktur data kompleks',
            'dosen'     => 'Dwi Arif',
        ],
        [
            'id'        => 5,
            'kode'      => 'MK005',
            'nama'      => 'DMJK',
            'sks'       => 3,
            'deskripsi' => 'Belajar konsep dasar protokol jaringan, arsitektur TCP/IP, dan keamanan jaringan',
            'dosen'     => 'Aidil'
        ],
    ];

    // Menampilkan seluruh data mata kuliah.
    // Method ini digunakan oleh route courses.index untuk mengirim data ke view index.
    public function index()
    {
        return view('courses.index', [
            'courses' => $this->courses,
        ]);
    }

    // Menampilkan satu mata kuliah berdasarkan ID yang diterima dari URL.
    // Pencarian dilakukan pada array karena data belum berasal dari database.
    public function show($course)
    {
        // Mencari data yang ID-nya sama dengan parameter route.
        // Hasil pencarian digunakan untuk menentukan mata kuliah yang akan ditampilkan.
        $courseData = collect($this->courses)->firstWhere('id', (int) $course);

        // Jika ID tidak ditemukan, Laravel mengembalikan halaman 404.
        // Ini mencegah view menerima data kosong untuk mata kuliah yang tidak ada.
        abort_if($courseData === null, 404);

        // Mengirim satu data mata kuliah ke view show.
        // View tersebut bertanggung jawab mengatur bagaimana data ditampilkan.
        return view('courses.show', [
            'course' => $courseData,
        ]);
    }
}