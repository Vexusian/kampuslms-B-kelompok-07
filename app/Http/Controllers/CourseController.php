private array $courses = [
    [
        'id'        => 1,
        'kode'      => 'MK001',
        'nama'      => 'Kapita Selekta',
        'sks'       => 2,
        'deskripsi' => "Belajar AI Engineering"
    ],
    [
        'id'        => 2,
        'kode'      => 'MK002',
        'nama'      => 'Pemrograman Web',
        'sks'       => 3,
        'deskripsi' => "Belajar HTML, CSS, JavaScript, dan framework web modern"
    ],
    [
        'id'        => 3,
        'kode'      => 'MK003',
        'nama'      => 'Basis Data',
        'sks'       => 3,
        'deskripsi' => "Belajar perancangan basis data, query SQL, dan manajemen relasi data"
    ],
    [
        'id'        => 4,
        'kode'      => 'MK004',
        'nama'      => 'Algoritma dan Struktur Data',
        'sks'       => 4,
        'deskripsi' => "Belajar logika pemrograman, pemecahan masalah, dan struktur data kompleks"
    ],
    [
        'id'        => 5,
        'kode'      => 'MK005',
        'nama'      => 'Jaringan Komputer',
        'sks'       => 3,
        'deskripsi' => "Belajar konsep dasar protokol jaringan, arsitektur TCP/IP, dan keamanan jaringan"
    ]
];
=======
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
>>>>>>> d5cba73bdae26c05437faef5a82076ccfb61e478
