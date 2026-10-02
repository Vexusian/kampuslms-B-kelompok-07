## Catatan Minggu 5

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### 1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.
```
  GET|HEAD        / .......................................... dashboard › routes/web.php:19
  GET|HEAD        admin/courses ............... admin.courses.index › CourseController@index
  POST            admin/courses ............... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ...... admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} ........ admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} .... admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} .. admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ... admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users ..................... admin.users.index › UserController@index
  POST            admin/users ..................... admin.users.store › UserController@store
  GET|HEAD        admin/users/create ............ admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} ................ admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ............ admin.users.update › UserController@update
  DELETE          admin/users/{user} .......... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ........... admin.users.edit › UserController@edit
  GET|HEAD        courses ........................... courses.index › CourseController@index
  POST            courses ........................... courses.store › CourseController@store
  GET|HEAD        courses/create .................. courses.create › CourseController@create
  GET|HEAD        courses/{course} .................... courses.show › CourseController@show
  PUT|PATCH       courses/{course} ................ courses.update › CourseController@update
  DELETE          courses/{course} .............. courses.destroy › CourseController@destroy
  GET|HEAD        courses/{course}/edit ............... courses.edit › CourseController@edit
  GET|HEAD        dev/login/{user} ........................... dev.login › routes/web.php:33
  GET|HEAD        dev/logout ................................ dev.logout › routes/web.php:38
  GET|HEAD        dosen/assignments/{assignment} dosen.assignments.show › AssignmentControl…
  PUT|PATCH       dosen/assignments/{assignment} dosen.assignments.update › AssignmentContr…
  DELETE          dosen/assignments/{assignment} dosen.assignments.destroy › AssignmentCont…
  GET|HEAD        dosen/assignments/{assignment}/edit dosen.assignments.edit › AssignmentCo…
  GET|HEAD        dosen/assignments/{assignment}/submissions dosen.assignments.submissions.…
  GET|HEAD        dosen/courses ............... dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} ........ dosen.courses.show › CourseController@show
  PUT|PATCH       dosen/courses/{course} .... dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/assignments dosen.courses.assignments.index › Assi…
  POST            dosen/courses/{course}/assignments dosen.courses.assignments.store › Assi…
  GET|HEAD        dosen/courses/{course}/assignments/create dosen.courses.assignments.creat…
  GET|HEAD        dosen/courses/{course}/edit ... dosen.courses.edit › CourseController@edit
  GET|HEAD        dosen/courses/{course}/materials dosen.courses.materials.index › Material…
  POST            dosen/courses/{course}/materials dosen.courses.materials.store › Material…
  GET|HEAD        dosen/courses/{course}/materials/create dosen.courses.materials.create › …
  GET|HEAD        dosen/materials/{material} dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} dosen.materials.update › MaterialController@up…
  DELETE          dosen/materials/{material} dosen.materials.destroy › MaterialController@d…
  GET|HEAD        dosen/materials/{material}/edit dosen.materials.edit › MaterialController…
  GET|HEAD        dosen/submissions/{submission} dosen.submissions.show › SubmissionControl…
  GET|HEAD        login .......................................... login › routes/web.php:27
  GET|HEAD        mahasiswa/assignments/{assignment} mahasiswa.assignments.show › Assignmen…
  POST            mahasiswa/assignments/{assignment}/submissions mahasiswa.assignments.subm…
  GET|HEAD        mahasiswa/courses ....... mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} mahasiswa.courses.show › CourseController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments mahasiswa.courses.assignments.inde…
  GET|HEAD        mahasiswa/courses/{course}/materials mahasiswa.courses.materials.index › …
  GET|HEAD        mahasiswa/materials/{material} mahasiswa.materials.show › MaterialControl…
  GET|HEAD        mahasiswa/submissions/{submission} mahasiswa.submissions.show › Submissio…
  GET|HEAD        mata-kuliah ................... mata-kuliah.index › CourseController@index
  POST            mata-kuliah ................... mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create .......... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{mata_kuliah} ....... mata-kuliah.show › CourseController@show
  PUT|PATCH       mata-kuliah/{mata_kuliah} ... mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{mata_kuliah} . mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{mata_kuliah}/edit .. mata-kuliah.edit › CourseController@edit
  GET|HEAD        tentang ...................................... tentang › routes/web.php:23

                                                                         Showing [61] routes
```

### 2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst). 

### 3. Untuk setiap route bertanda, jawab: **siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain?** Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.

### 4. Buat tabel di `docs/minggu-05-<nama>.md` berjudul "Daftar Titik Rawan IDOR". Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.

### Daftar Titik Rawan IDOR

| Route | Parameter Model | Yang Seharusnya Boleh Mengakses | Yang Saat Ini Mencegah Orang Lain |
|---|---|---|---|
| `/dev/login/{user}` | `{user}` | Hanya untuk pengujian lokal | Hanya dibatasi `environment('local')`; belum ada pembatasan user yang boleh dipilih |
| `/admin/users/{user}` | `{user}` | Admin | Middleware `auth` dan `role:admin`; belum ada Policy per user |
| `/admin/courses/{course}` | `{course}` | Admin | Middleware `auth` dan `role:admin` |
| `/dosen/courses/{course}` | `{course}` | Dosen yang mengajar course | Middleware `role:dosen`; `edit/update` memiliki pengecekan `lecturer_id`, tetapi `show` belum mengecek kepemilikan |
| `/dosen/courses/{course}/materials` | `{course}` | Admin atau dosen yang mengajar course | Middleware `role:dosen` dan pengecekan kepemilikan course di controller |
| `/dosen/materials/{material}` | `{material}` | Admin atau dosen yang mengajar course terkait | Middleware `role:dosen` dan pengecekan kepemilikan course di controller |
| `/dosen/courses/{course}/assignments` | `{course}` | Admin atau dosen yang mengajar course | Middleware `role:dosen` dan pengecekan kepemilikan course di controller |
| `/dosen/assignments/{assignment}` | `{assignment}` | Admin atau dosen yang mengajar course terkait | Middleware `role:dosen` dan pengecekan dosen pada course assignment |
| `/dosen/assignments/{assignment}/submissions` | `{assignment}` | Admin atau dosen yang mengajar course terkait | Middleware `role:dosen` dan pengecekan dosen pada course |
| `/dosen/submissions/{submission}` | `{submission}` | Admin atau dosen yang mengajar course terkait | Middleware `role:dosen` dan pengecekan dosen pada course submission |
| `/mahasiswa/courses/{course}` | `{course}` | Mahasiswa yang terdaftar pada course | Middleware `role:mahasiswa`, tetapi `show()` belum mengecek apakah mahasiswa terdaftar pada course |
| `/mahasiswa/courses/{course}/materials` | `{course}` | Mahasiswa yang terdaftar pada course | Middleware `role:mahasiswa` dan pengecekan enrollment di controller |
| `/mahasiswa/materials/{material}` | `{material}` | Mahasiswa yang terdaftar pada course terkait | Middleware `role:mahasiswa` dan pengecekan enrollment di controller |
| `/mahasiswa/courses/{course}/assignments` | `{course}` | Mahasiswa yang terdaftar pada course | Middleware `role:mahasiswa` dan pengecekan enrollment di controller |
| `/mahasiswa/assignments/{assignment}` | `{assignment}` | Mahasiswa yang terdaftar pada course terkait | Middleware `role:mahasiswa` dan pengecekan enrollment di controller |
| `/mahasiswa/assignments/{assignment}/submissions` | `{assignment}` | Mahasiswa yang terdaftar pada course terkait | Middleware `role:mahasiswa` dan pengecekan enrollment di controller |
| `/mahasiswa/submissions/{submission}` | `{submission}` | Pemilik submission; admin/dosen terkait juga dapat melihat | Middleware `role:mahasiswa` dan pengecekan pemilik submission di controller |
| `/courses/{course}` | `{course}` | Pengguna yang memiliki hak akses terhadap course | **Belum ada middleware `auth` pada route ini** dan `show()` belum memiliki pengecekan akses |
| `/mata-kuliah/{course}` | `{course}` | Pengguna yang memiliki hak akses terhadap course | **Belum ada middleware `auth` pada route ini** dan `show()` belum memiliki pengecekan akses |

Route yang menerima parameter model seperti `{course}`, `{assignment}`, `{material}`, `{submission}`, dan `{user}` perlu diperiksa karena berpotensi menjadi titik IDOR.

Sebagian route sudah memiliki perlindungan melalui `auth`, `role`, pengecekan kepemilikan, atau enrollment. Namun, beberapa route masih belum memiliki pengecekan akses terhadap objek secara spesifik. Route seperti `/courses/{course}` dan `/mata-kuliah/{course}` juga berada di luar middleware `auth`, sehingga perlu menjadi perhatian.

## BREAK  
| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengubah angka di URL | **IDOR nyata di aplikasi Anda sendiri** |
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Nested route tanpa scoping |
| 3 | Aktifkan `Route::scopeBindings()`, ulangi nomor 2 | Bandingkan hasilnya |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | Berkas itu tidak ada — kenali gejalanya |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | 403 dari middleware |
| 6 | Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`) | **Middleware saja tidak cukup** |

## FIX

Branch `w05` pada repository **KampusLMS** memiliki 7 masalah keamanan pada route, middleware, authorization, dan penggunaan HTTP method. Berikut adalah catatan perbaikan dari masing-masing masalah.

---

### 1. Route `/profile` Berada di Luar Middleware `auth`  
Lokasi file: `routes/web.php`  

**Kode yang salah**    
```php
Route::get('/profile', function () {
    return view('profile');
})->name('profile');

Route::middleware('auth')->group(function () {
    // route lainnya
});
```

**Letak kesalahannya**  
Route `/profile` berada di luar:  
```php
Route::middleware('auth')->group(function () {
```
Akibatnya, route `/profile` tidak dilindungi middleware `auth`. User yang belum login berpotensi mengakses halaman tersebut.  

**Kode yang diperbaiki**  
```php
Route::middleware('auth')->group(function () {

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    // route lainnya
});
```
Route `/profile` dipindahkan ke dalam middleware `auth`, sehingga hanya pengguna yang sudah login yang dapat mengakses halaman tersebut.

### 2. IDOR pada Submission  
Lokasi file: `app/Http/Controllers/SubmissionController.php`  

**Kode yang salah**    
```php
public function show(Submission $submission)
{
    return view('submissions.show', compact('submission'));
}
```

**Letak kesalahannya**  
Controller hanya mengambil submission berdasarkan ID tanpa memeriksa apakah submission tersebut memang milik user yang sedang login.

Contohnya, user dengan ID `5` dapat mencoba mengakses submission milik user dengan ID `10` hanya dengan mengganti ID pada URL. 

**Kode yang diperbaiki**  
```php
public function show(Submission $submission)
{
    abort_unless($submission->user_id === auth()->id(), 403);

    return view('submissions.show', compact('submission'));
}
```

Jika submission bukan milik user yang sedang login, request dihentikan dengan status 403 Forbidden. Perbaikan ini mencegah IDOR (Insecure Direct Object Reference).

### 3. IDOR pada Material  
Masalah ini berkaitan dengan akses dosen terhadap material pada course.  
Lokasi file: `routes/web.php`  

**Kode yang salah**  
Contohnya pada method `show()`:  
```php
public function show(Course $course, Material $material)
{
    return view('materials.show', compact('course', 'material'));
}
});
```
Tidak terdapat pengecekan apakah course tersebut memang dimiliki oleh dosen yang sedang login.

**Kode yang diperbaiki**  
Tambahkan method berikut di dalam `MaterialController`: 
```php
private function authorizeCourse(Course $course): void
{
    abort_unless($course->lecturer_id === auth()->id(), 403);
}
```
Kemudian digunakan pada method yang mengakses material, misalnya:
```php
public function show(Course $course, Material $material)
{
    $this->authorizeCourse($course);

    return view('materials.show', compact('course', 'material'));
}
```

Sistem sekarang memeriksa apakah `lecturer_id` pada course sama dengan ID user yang sedang login.

Jika dosen mencoba mengakses material dari course yang bukan miliknya, sistem memberikan **403 Forbidden**. Dengan demikian, dosen tidak dapat mengakses atau memanipulasi material milik dosen lain.

### 4. Nested route tidak menggunakan `scopeBindings()`  
Lokasi file: `routes/web.php`  

**Kode yang salah**    
```php
Route::resource('courses.materials', MaterialController::class)->shallow();
```

**Letak kesalahannya**  
Route menggunakan nested resource: `courses/{course}/materials/{material}` tetapi tidak menggunakan `scopeBindings()`.

Akibatnya, Laravel dapat mengambil `Material` berdasarkan ID tanpa memastikan material tersebut memang berhubungan dengan `Course` yang ada pada URL.

**Kode yang diperbaiki**  
```php
Route::resource('courses.materials', MaterialController::class)->scopeBindings();
```
`scopeBindings()` membuat Laravel melakukan binding berdasarkan hubungan parent-child.  
Contohnya: `/dosen/courses/1/materials/10` Laravel akan memastikan bahwa material `10` memang berada di course `1`.

Dengan demikian, material dari course lain tidak dapat diakses hanya dengan mengganti ID pada URL.

### 5. Middleware `role` didaftarkan di file yang salah  
Lokasi file: `app/Http/Kernel.php`  

**Kode yang salah**    
```php
protected $routeMiddleware = [
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
];
```

**Letak kesalahannya**  
Project menggunakan struktur Laravel yang mendaftarkan middleware melalui: `bootstrap/app.php` buka melalui `app/Http/Kernel.php`.

**Kode yang diperbaiki**  
Di `bootstrap/app.php`  
tambahkan import:  
```php
use App\Http\Middleware\EnsureUserHasRole;
```
Kemudian daftarkan alias:  
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => EnsureUserHasRole::class,
    ]);
})
```
Alias middleware `role` dipindahkan dari `Kernel.php` ke konfigurasi middleware di `bootstrap/app.php` sesuai struktur Laravel yang digunakan project.

### 6. Nama route bentrok antara Admin dan Dosen  
Lokasi file: `routes/web.php`  

**Kode yang salah**    
Route Admin:
```php
Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::resource('courses', CourseController::class);
});
```
Route Dosen:
```php
Route::middleware('role:dosen')->prefix('dosen')->group(function () {
    Route::resource('courses', CourseController::class);
});
```

**Letak kesalahannya**  
Walaupun URL-nya berbeda:  
```
/admin/courses   
/dosen/courses
```  
nama route resource dapat sama, misalnya:  
```
courses.index
courses.create
courses.store
courses.show
courses.edit
courses.update
courses.destroy
```
Hal tersebut bentrokan nama route

**Kode yang diperbaiki**  
Ditambahkan prefix nama route:
```php
->name('admin.')
```  
```php
->name('dosen.')
```
Sehingga nama route menjadi berbeda:
```
admin.courses.index
dosen.courses.index
```
Dengan demikian tidak terjadi bentrokan antara route Admin dan Dosen.

### 7. Route destruktif menggunakan method `GET`  
Lokasi file: `routes/web.php`  

**Kode yang salah**    
```php
Route::get('/submissions/destroy-all', function () {
    \App\Models\Submission::truncate();

    return redirect()->back()->with(
        'success',
        'Semua submission dihapus.'
    );
})->name('submissions.destroy-all');
```

**Letak kesalahannya**  
Route menggunakan:
```php
Route::get(...)
```
padahal isi route melakukan operasi yang menghapus seluruh data:
```php
Submission::truncate();
```
`GET` seharusnya digunakan untuk mengambil atau membaca data, bukan untuk melakukan operasi destruktif.

**Kode yang diperbaiki**  
```php
Route::delete('/submissions/destroy-all', function () {
    \App\Models\Submission::truncate();

    return redirect()->back()->with(
        'success',
        'Semua submission dihapus.'
    );
})->name('submissions.destroy-all');
```  
Kemudian pemanggilan dari Blade menggunakan method `DELETE`:
```php
<form action="{{ route('admin.submissions.destroy-all') }}" method="POST">
    @csrf
    @method('DELETE')

    <button type="submit">
        Hapus Semua Submission
    </button>
</form>
```
Method route diubah dari `GET` menjadi `DELETE` karena route tersebut melakukan penghapusan data.

## BUILD

1. Susun ulang `routes/web.php` sepenuhnya dengan route group berdasarkan peran (`admin`, `dosen`, `mahasiswa`), lengkap dengan prefix dan name prefix.
2. Middleware `EnsureUserHasRole`, terdaftar di `bootstrap/app.php` sebagai alias `role`.
3. Terapkan route model binding di seluruh controller — hilangkan `findOrFail($id)` manual.
4. Route bersarang untuk materi dan tugas memakai `->shallow()` dan `scopeBindings()`.
5. Tambahkan pemeriksaan kepemilikan sementara (`abort_unless`) pada setiap titik rawan yang Anda daftarkan di bagian READ. Ini akan dirapikan menjadi Policy di minggu 7.
6. Halaman 403 kustom yang informatif tapi tidak membocorkan informasi (jangan tulis "submission ini milik Budi").

**Dari BUILD ini, kami sudah mengerjakan dan sudah bisa menghasilkan sesuai ketentuan di BUILD.**