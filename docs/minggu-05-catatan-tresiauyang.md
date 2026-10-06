# Catatan Minggu 5

## Nama : Tresia Uyang
## NIM : 10241072

### READ 

1. Jalankan `php artisan route:list --except-vendor`. 
>    GET|HEAD        / ...................................................................................................................................... routes/web.php:20
  GET|HEAD        admin/courses ............................................................................................... admin.courses.index › CourseController@index
  POST            admin/courses ............................................................................................... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ...................................................................................... admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} ........................................................................................ admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} .................................................................................... admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} .................................................................................. admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ................................................................................... admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users ..................................................................................................... admin.users.index › UserController@index
  POST            admin/users ..................................................................................................... admin.users.store › UserController@store
  GET|HEAD        admin/users/create ............................................................................................ admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} ................................................................................................ admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ............................................................................................ admin.users.update › UserController@update
  DELETE          admin/users/{user} .......................................................................................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ........................................................................................... admin.users.edit › UserController@edit
  POST            api/v1/assignments ........................................................................................................ Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} .......................................................................................... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} ......................................................................................... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions ......................................................................... Api\AssignmentController@submissions
  POST            api/v1/assignments/{assignment}/submissions ..................................................................... Api\AssignmentController@storeSubmission
  POST            api/v1/auth/login ............................................................................................................... Api\AuthController@login
  POST            api/v1/auth/logout ............................................................................................................. Api\AuthController@logout
  GET|HEAD        api/v1/courses ................................................................................................................ Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} ........................................................................................................ Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments ..................................................................................... Api\CourseController@assignments
  GET|HEAD        api/v1/courses/{course}/materials ......................................................................................... Api\CourseController@materials
  GET|HEAD        api/v1/me .......................................................................................................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications .................................................................................................... Api\NotificationController@index
  POST            api/v1/notifications/{id}/read ..................................................................................... Api\NotificationController@markAsRead
  PUT             api/v1/submissions/{submission}/grade ..................................................................................... Api\SubmissionController@grade
  GET|HEAD        courses ........................................................................................................... courses.index › CourseController@index
  POST            courses ........................................................................................................... courses.store › CourseController@store
  GET|HEAD        courses/create .................................................................................................. courses.create › CourseController@create
  GET|HEAD        courses/{course} .................................................................................................... courses.show › CourseController@show
  PUT|PATCH       courses/{course} ................................................................................................ courses.update › CourseController@update
  DELETE          courses/{course} .............................................................................................. courses.destroy › CourseController@destroy
  GET|HEAD        courses/{course}/edit ............................................................................................... courses.edit › CourseController@edit
  GET|HEAD        dashboard .......................................................................................................... dashboard › DashboardController@index
  GET|HEAD        dev/login/{user} ........................................................................................................... dev.login › routes/web.php:36
  GET|HEAD        dev/logout ................................................................................................................ dev.logout › routes/web.php:41
  GET|HEAD        dosen/assignments/{assignment} ........................................................................ dosen.assignments.show › AssignmentController@show
  PUT|PATCH       dosen/assignments/{assignment} .................................................................... dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} .................................................................. dosen.assignments.destroy › AssignmentController@destroy
  GET|HEAD        dosen/assignments/{assignment}/edit ................................................................... dosen.assignments.edit › AssignmentController@edit
  GET|HEAD        dosen/assignments/{assignment}/submissions .............................................. dosen.assignments.submissions.index › SubmissionController@index
  GET|HEAD        dosen/courses ............................................................................................... dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} ........................................................................................ dosen.courses.show › CourseController@show
  PUT|PATCH       dosen/courses/{course} .................................................................................... dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/assignments .......................................................... dosen.courses.assignments.index › AssignmentController@index
  POST            dosen/courses/{course}/assignments .......................................................... dosen.courses.assignments.store › AssignmentController@store
  GET|HEAD        dosen/courses/{course}/assignments/create ................................................. dosen.courses.assignments.create › AssignmentController@create
  GET|HEAD        dosen/courses/{course}/edit ................................................................................... dosen.courses.edit › CourseController@edit
  GET|HEAD        dosen/courses/{course}/materials ................................................................ dosen.courses.materials.index › MaterialController@index
  POST            dosen/courses/{course}/materials ................................................................ dosen.courses.materials.store › MaterialController@store
  GET|HEAD        dosen/courses/{course}/materials/create ....................................................... dosen.courses.materials.create › MaterialController@create
  GET|HEAD        dosen/materials/{material} ................................................................................ dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} ............................................................................ dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} .......................................................................... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/materials/{material}/edit ........................................................................... dosen.materials.edit › MaterialController@edit
  GET|HEAD        dosen/submissions/{submission} ........................................................................ dosen.submissions.show › SubmissionController@show
  GET|HEAD        login .......................................................................................................................... login › routes/web.php:30
  GET|HEAD        mahasiswa/assignments/{assignment} ................................................................ mahasiswa.assignments.show › AssignmentController@show
  POST            mahasiswa/assignments/{assignment}/submissions ...................................... mahasiswa.assignments.submissions.store › SubmissionController@store
  GET|HEAD        mahasiswa/courses ....................................................................................... mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} ................................................................................ mahasiswa.courses.show › CourseController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments .................................................. mahasiswa.courses.assignments.index › AssignmentController@index
  GET|HEAD        mahasiswa/courses/{course}/materials ........................................................ mahasiswa.courses.materials.index › MaterialController@index
  GET|HEAD        mahasiswa/materials/{material} ........................................................................ mahasiswa.materials.show › MaterialController@show
  GET|HEAD        mahasiswa/submissions/{submission} ................................................................ mahasiswa.submissions.show › SubmissionController@show
  GET|HEAD        mata-kuliah ................................................................................................... mata-kuliah.index › CourseController@index
  POST            mata-kuliah ................................................................................................... mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create .......................................................................................... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{mata_kuliah} ....................................................................................... mata-kuliah.show › CourseController@show
  PUT|PATCH       mata-kuliah/{mata_kuliah} ................................................................................... mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{mata_kuliah} ................................................................................. mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{mata_kuliah}/edit .................................................................................. mata-kuliah.edit › CourseController@edit
  GET|HEAD        tentang ...................................................................................................................... tentang › routes/web.php:26

2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst).

> | No | Method | URI | Parameter Model | Pemilik Sah / Hak | Risiko IDOR jika tanpa otorisasi | Mekanisme pencegahan saat ini |
>|---|---|---|---|---|---|---|
>| 1 |GET | `/submissions/{submission}` | `{submission}` | Mahasiswa pemilik, dosen pengampu, Admin | Mahasiswa lain dapat melihat submission mahasiswa lain dengan mengubah ID | `abort_unless(...)` pada `SubmissionController.php` | Terbukti: Mahasiswa A bisa melihat submission milik mahasiswa B secara lengkap, termasuk jawaban dan nilainya. Hal ini terjadi karena sistem hanya mengecek apakah submission dengan ID tersebut ada, bukan menegcek siapa pemiliknya. | 
> | 2 | GET, PUT, DELETE | `/dosen/courses/{course}/edit` |`{course}`| Dosen pemilik `(lecturer_id)`, Admin | Dosen A dapat mengubah/menghapus mata kuliah Dosen B. | `abort_unless(...)` pada `CourseController.php` | Terbukti: Assigment 99 tetap bisa ditampilkan walaupun berada dibawah course 1 pada URL. Sistem hanya mencari assigment berdasarkan ID 99 dan belum mengecek apakah assigment tersebut memang milik course 1. |
> | 3 |GET, PUT, DELETE | `/materials/{material}`|`{material}`|Dosen pengampu, Mahasiswa enrolled (view/download), Admin|Pengguna yang tidak berhak dapat mengakses/mengubah materi.|Pengecekan enrollment dan `lecturer_id` pada `MaterialController.php`| Terbukti: Setelah `scopeBidings()` ditambahkan, assignment 99 tidak bisa lagi diakses melalui course 1. Sistem ikut mengecek hubungan antara assignment dan course. Karena tidak sesuai, sistem menampilkan 404 Not Found |
> | 4 |GET, PUT, DELETE | `/assignments/{assignment}` | `{assignment}` | Dosen pengampu (CRUD), Mahasiswa enrolled (view), Admin |Pengguna luar dapat melihat/mengubah tugas yang bukan haknya.|Pengecekan enrollment dan kepemilikan pada `AssignmentController.php`| 
> | 5 |GET, PUT, DELETE |`/admin/users/{user}`|`{user}`|Admin; pengguna terkait untuk profil sendiri | Pengguna dapat memanipulasi profil user lain jika tidak ada otorisasi. | Middleware `role:admin` dan proteksi `UserController.php` |


### BREAK 

| # | Yang dicoba | Prediksi | Hasil Pengamatan Rill |
|---|-------------|------------------------|-----------------|
| 1 | Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengubah angka di URL | Mahasiswa A kemungkinan masih bisa melihat submission mahasiswa B karena belum ada pengecekan apakah data tersebut memang miliknya | Terbukti: Mahasiswa A bisa melihat submission milik mahasiswa B secara lengkap, termasuk jawaban dan nilainya. Hal ini terjadi karena sistem hanya mengecek apakah submission dengan ID tersebut ada, bukan mengecek siapa pemiliknya.
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Assignment 99 kemungkinan tetap bisa dibuka walaupun tidak berhubungan dengan course 1 | Terbukti: Assignment 99 tetap bisa ditampilkan walaupun berada dibawah course 1 pada URL. Sistem hanya mencari assigment berdasrkan ID 99 dan belum mengecek apakah assigment tersebut memang milik course 1 | 
| 3 | Aktifkan `Route::scopeBindings()`, ulangi nomor 2 |Assignment 99 seharusnya tidak bisa dibuka karena bukan bagian dari course 1, sehingga menghasilkan 404 Not Found | Terbukti: Setelah `scopeBindings()` ditambahkan, assignment 99 tidak bisa lagi diakses melalui course 1. Sistem ikut mengecek hubungan antara assignment dan course. Karena tidak sesuai, sistem menampilkan 404 Not Found. |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | Kemungkinan akan mengalami kendala karena file karnel.php tidak ada pada Laravel 12. | Terbukti: File `app/Http/Kernel.php` memang tidak ditemukan. Setelah dicek, pada Laravel 12 pendaftaran middleware dilakukan melalui `bootstrap/app.php` |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | Akun dosen seharusnya tidak bisa mengakses route yang hanya diperbolehkan untuk admin dan akan mendapatkan 403 Forbidden. | Terbukti: Saat akun dosen mencoba mengakses route tersebut, akses langsung ditolak dengan 403 Forbidden. Ini karena role pengguna adalah `dosen`, sedangkan route hanya menerima role `admin` |
| 6 | Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`) | Dosen A kemungkinan masih bisa mengaksesnya karena middleware hanya mengecek apakah pengguna memiliki role `dosen`, bukan mengecek apakah mata kuliah tersebut milik Dosen A | Terbukti: Dosen A tetap bisa melewati pemeriksaan `role:dosen` karena memang memiliki role sebagai dosen. Masalahnya, sistem belum mengecek kepemilikan mata kuliah tersebut. Jadi, Dosen A bisa mengakses data yang seharusnya menjadi milik Dosen B |


### FIX 

Branch `w05` pada repo `kampuslms-broken` berisi **7 masalah**: satu route di luar grup `auth`, dua IDOR pada submission dan material, nested route tanpa `scopeBindings`, middleware didaftarkan di berkas yang salah, nama route bentrok antar peran, dan satu route destruktif yang memakai `GET`.

#### 1. Rute internal dibungkus dengan `auth`
sebelumnya, beberapa rute internal masih bisa diakses tanpa login. Hal ini membuat pengguna yang belum masuk sistem bisa mencoba membuka halaman inetrnal.

**Perbaikan:**
Rute internal dimasukan ke dalam grup middleware `auth`:

```php
Route::middleware('auth')->group(function () {

    // Rute dashboard
    // Rute materi
    // Rute submission
    // Rute internal lainnya

});

```
Dengan begitu, pengguna harus login terlebih dahulu sebelum dapat mengakses rute-rute tersebut.

#### 2. Memperbaiki IDOR pada Submission dan Material 

Sebelumnya, `show()`hanya menggunakan route model binding. Artinya, selama ID data ditemukan, data tersebut akan ditampilkan tanpa mengecek apakah pengguna memang memiliki hak untuk melihatnya.

**perbaikian pada subbmision:**

```php
abort_unless(
    $submission->user_id === auth()->id()
        || auth()->user()->role === 'admin'
        || $submission->assignment->course->lecturer_id === auth()->id(),
    403,
    'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
);

```
Pengecekan ini memastikan bahwa submission hanya dapat dilihat oleh mahasiswa yang memiliki submission tersebut, admin, atau dosen yang mengampu mata kuliahnya. Pengecekan yang sama diterapkan pada modul material, sehingga pengguna tidak cukup hanya mengetahui ID material untuk bisa mengaksesnya.

#### 3. Menambahkan `scopeBindings() pada nested route 

Sebelumnya, route seperti:

> /courses/{course}/assignments/{assigment}

route tersebut hanya mencari assignment berdasaran ID. Sistem belum memastikan bahwa assignment tersebut memang berada di dalam course yang terdapat pada URL. 

**Perbaikan**

```php
Route::scopeBindings()->group(function () {

    Route::get(
        '/courses/{course}/assignments/{assignment}',
        [AssignmentController::class, 'show']
    )->name('assignments.show');

});
```
Dengan `scopeBindings()`, Laravel akan ikut memeriksa hubungan antara `course` dan `assignment`. Jadi, jika assignment 99 bukan milik course 1, URL:

> /courses/1/assignments/99

tidak akan menemukan data dan menghasilkan 404 Not Found.

#### 4. Memindahkan pendaftaran middleware ke `bootstrap/app.php`

Pada laravel 12, middleware tidak lagi didaftarkan melalui 
>app/http/kernel.php

karena dile tersebut memang tidak digunakan pada struktur laravel 12.

**Perbaikkan dilakukan di `bootstrap/app.php`:

```php 
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})

```
Setelah itu middleware  dapat digunakan pada route seperti:

```php 
Route::middleware('role:admin')->group(function () {
    // route khusus admin
});
```

#### 5. Memperbaiki bentrok nama route 

Sebelumnya terdapat nama route yang sama seperti:

```php 
courses.index
```

pada beberapa role, hal ini dapat membuat pemanggilan `route('courses.index')` mengarah ke route yang tidak sesuai.

**Perbaikan**

Nama route dibuat berbeda berdasarkan role dengan menggunakan nama prefix.

Contohnya:

```php 
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::resource('courses', CourseController::class);

    });

```

Untuk dosen:

```php 
Route::prefix('dosen')
    ->name('dosen.')
    ->middleware(['auth', 'role:dosen'])
    ->group(function () {

        Route::resource('courses', CourseController::class);

    });
```

Untuk mahasiswa:

```php
Route::prefix('mahasiswa')
    ->name('mahasiswa.')
    ->middleware(['auth', 'role:mahasiswa'])
    ->group(function () {

        Route::resource('courses', CourseController::class);

    });
```
Dengan begitu nama route menjadi berbeda, misalnya:

> admin.courses.index
dosen.courses.index
mahasiswa.courses.index

Jadi tidak lagi saling bertabrakan.

#### 6. Mengubah route penghapusan dari `GET` menjadi `DELETE`

Sebelumnya penghapusan data dilakukan menggunakan `GET`, misalnya:

> GET/courses/5/delete

Hal ini kurang aman karena request `GET` seharusnya tidak digunakan untuk menghapus data.

**Perbaikan**

Route penghapusan diubah menjadi:

```php
Route::delete(
    '/courses/{course}',
    [CourseController::class, 'destroy']
)->name('courses.destroy');
```
Kemudian pada form digunakan:

```blade
<form action="{{ route('courses.destroy', $course) }}" method="POST">
    @csrf
    @method('DELETE')

    <button type="submit">Hapus</button>
</form>
```

Dengan cara ini, proses penghapusan hanya dilakukan melalui request `DELET` dan tetap menggunakan perlindungan CSRF.

#### 7. Membuat halaman error 403 sendiri 

Halaman error 403 bawaan diganti dengan halaman khusus agar infromasi tentang data atau pemilik dokumen tidak ikut ditampilkan.

file yang dibuat:
> resources/views/errors /403.Blade.php

Contohnya:

```Blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>403 - Akses Ditolak | KampusLMS</title>
</head>
<body>
    <h1>Akses Tidak Diizinkan</h1>

    <p>
        Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
    </p>

    <a href="{{ url('/') }}">Kembali ke halaman utama</a>
</body>
</html>

```

Dengan halaman ini, pengguna hanya mendapatkan infromasi bahwa akses ditolak tanpa menampilkan identitas pemilik data yang seharusnya tidak diketahui.




### BUILD 

1. Susun ulang `routes/web.php` sepenuhnya dengan route group berdasarkan peran (`admin`, `dosen`, `mahasiswa`), lengkap dengan prefix dan name prefix.

> File `routes/web.php` disusun menggunakan `Route:middleware('auth')` dan dibagi menjadi tiga keompok utama:
>- Grup admin menggunakan `prefix('admin')->name ("admin.')->middleware("role:admin')` utnuk mengelola user, mata kuliah, dan pendaftaran.
>- Grup dosen menggunakan `prefix('dosen')->name('dosen.')->middleware('role:dosen')` untuk mengelola mata kuliah, materi, tugas, dan nilai mahasiswa.
>- Grup mahasiswa menggunakan `prefix('mahasiswa')->name('mahasiswa.')->middleware('role:mahasiswa')` untuk pendaftaran mata kuliah, melihat materi, mengumpulkan tugas, dan melihat nilai.

2. Middleware `EnsureUserHasRole`, terdaftar di `bootstrap/app.php` sebagai alias `role`.
>Dibuat middleware `EnsureUserHasRole` untuk mengecek role pengguna sebelum mengakses route tertentu. Middleware menggunakan parameter `string...$roles`, sehingga dapat digunakan untuk satu atau beberapa role. Jika pengguna belum login, akses ditolak dengan 401 Unauthorized, sedangkan pengguna yang sudah login tetapi memiliki role yang tidak sesuai mendapatkan 403 Forbidden. Middleware kemudian didaftarkan dengan alias `role` melalui `bootstrap/app.php`.

3. Terapkan route model binding di seluruh controller — hilangkan `findOrFail($id)` manual.
>Controller menggunakan route model binding agar model dapat langsung diterima sebagai parameter method. Contohnya, `show(Courses $course)` digunakan sebagai pengganti pencarian manual menggunakan `findOrFail($id)`. Cara ini membuat kode controller lebih sederhan dan mengurangi pencarian data secara manual.

4. Route bersarang untuk materi dan tugas memakai `->shallow()` dan `scopeBindings()`.
>Route yang memiliki hubungan nested, seperti course dengan assignment atau material, menggunakan `Route::scopeBindings()` utnuk memastikan data child memang berhubungan dengan parent nya. Selain itu, `->shallow()` digunakan agar URL untuk data per item tidak terlalu panjang. Route koleksi tetap menggunakan `/courses/{course}/assignments`, sedangkan akses satu assignment dapat menggunakan `/assignments/{assignment}`.

5. Tambahkan pemeriksaan kepemilikan sementara (`abort_unless`) pada setiap titik rawan yang Anda daftarkan di bagian READ. Ini akan dirapikan menjadi Policy di minggu 7.
> Pada bagian yang memiliki risiko IDOR, ditambahkan pemeriksaan kepemilikan menggunakan `abort_unless`. Pemeriksaan dilakukan berdasarkan hubungan data seperti `user_id`, `lecturer_id`, dan data pada tabel pivot `course_user`. Dengan begitu, pengguna tidak hanya harus memiliki role yang benar, tetapi juga harus memiliki hak terhadap data yang ingin diakses.

6. Halaman 403 kustom yang informatif tapi tidak membocorkan informasi (jangan tulis "submission ini milik Budi").
>Dibuat halaman `resources/views/errors/403.blade.php` untuk menangani akses yang ditolak. Halaman ini memberikan informasi bahwa pengguna tidak memiliki izin untuk mengakses halaman tersebut, tanpa menampilkan detail data atau informasi teknis yang tidak diperlukan. Disediakan juga tombol untuk kembali ke dashboard.