# Catatan Minggu 5

## Nama : Tresia Uyang
## NIM : 10241072

### READ 

1. Jalankan `php artisan route:list --except-vendor`. 
>   GET|HEAD        / ..................................................................................................................................... dashboard › routes/web.php:7
  GET|HEAD        courses ..................................................................................................................... courses.index › CourseController@index
  POST            courses ..................................................................................................................... courses.store › CourseController@store
  GET|HEAD        courses/create ............................................................................................................ courses.create › CourseController@create
  GET|HEAD        courses/{course} .............................................................................................................. courses.show › CourseController@show
  PUT|PATCH       courses/{course} .......................................................................................................... courses.update › CourseController@update
  DELETE          courses/{course} ........................................................................................................ courses.destroy › CourseController@destroy
  GET|HEAD        courses/{course}/edit ......................................................................................................... courses.edit › CourseController@edit
  GET|HEAD        mata-kuliah ............................................................................................................. mata-kuliah.index › CourseController@index
  POST            mata-kuliah ............................................................................................................. mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create .................................................................................................... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{mata_kuliah} ................................................................................................. mata-kuliah.show › CourseController@show
  PUT|PATCH       mata-kuliah/{mata_kuliah} ............................................................................................. mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{mata_kuliah} ........................................................................................... mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{mata_kuliah}/edit ............................................................................................ mata-kuliah.edit › CourseController@edit
  GET|HEAD        tentang ................................................................................................................................ tentang › routes/web.php:11
  GET|HEAD        users ........................................................................................................................... users.index › UserController@index
  POST            users ........................................................................................................................... users.store › UserController@store
  GET|HEAD        users/create .................................................................................................................. users.create › UserController@create
  GET|HEAD        users/{user} ...................................................................................................................... users.show › UserController@show
  PUT|PATCH       users/{user} .................................................................................................................. users.update › UserController@update
  DELETE          users/{user} ................................................................................................................ users.destroy › UserController@destroy
  GET|HEAD        users/{user}/edit ................................................................................................................. users.edit › UserController@edit

                                                                                                                                                                   Showing [23] routes

2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst).

> | No | Method | URI | Parameter Model | Pemilik Sah / Hak | Risiko IDOR jika tanpa otorisasi | Mekanisme pencegahan saat ini |
>|---|---|---|---|---|---|---|
>| 1 |GET | `/submissions/{submission}` | `{submission}` | Mahasiswa pemilik, dosen pengampu, Admin | Mahasiswa lain dapat melihat submission mahasiswa lain dengan mengubah ID | `abort_unless(...)` pada `SubmissionController.php` | 
> | 2 | GET, PUT, DELETE | `/dosen/courses/{course}/edit` |`{course}`| Dosen pemilik `(lecturer_id)`, Admin | Dosen A dapat mengubah/menghapus mata kuliah Dosen B. | `abort_unless(...)` pada `CourseController.php` |
> | 3 |GET, PUT, DELETE | `/materials/{material}`|`{material}`|Dosen pengampu, Mahasiswa enrolled (view/download), Admin|Pengguna yang tidak berhak dapat mengakses/mengubah materi.|Pengecekan enrollment dan `lecturer_id` pada `MaterialController.php`|
> | 4 |GET, PUT, DELETE | `/assignments/{assignment}` | `{assignment}` | Dosen pengampu (CRUD), Mahasiswa enrolled (view), Admin |Pengguna luar dapat melihat/mengubah tugas yang bukan haknya.|Pengecekan enrollment dan kepemilikan pada `AssignmentController.php`|
> | 5 |GET, PUT, DELETE |`/admin/users/{user}`|`{user}`|Admin; pengguna terkait untuk profil sendiri | Pengguna dapat memanipulasi profil user lain jika tidak ada otorisasi. | Middleware `role:admin` dan proteksi `UserController.php` |


### BREAK 

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengubah angka di URL | **IDOR nyata di aplikasi Anda sendiri** |
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Nested route tanpa scoping |
| 3 | Aktifkan `Route::scopeBindings()`, ulangi nomor 2 | Bandingkan hasilnya |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | Berkas itu tidak ada — kenali gejalanya |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | 403 dari middleware |
| 6 | Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`) | **Middleware saja tidak cukup** |

Nomor 1 dan 6 adalah inti minggu ini. Nomor 6 khususnya: banyak mahasiswa mengira sudah aman karena "kan sudah ada middleware role". Rasakan sendiri bahwa itu keliru.

### FIX 

Branch `w05` pada repo `kampuslms-broken` berisi **7 masalah**: satu route di luar grup `auth`, dua IDOR pada submission dan material, nested route tanpa `scopeBindings`, middleware didaftarkan di berkas yang salah, nama route bentrok antar peran, dan satu route destruktif yang memakai `GET`.

Perbaiki, kirim PR. Deskripsi PR wajib memuat **bukti** — sertakan perintah `curl` sebelum dan sesudah perbaikan untuk minimal satu IDOR.

### BUILD 

1. Susun ulang `routes/web.php` sepenuhnya dengan route group berdasarkan peran (`admin`, `dosen`, `mahasiswa`), lengkap dengan prefix dan name prefix.

2. Middleware `EnsureUserHasRole`, terdaftar di `bootstrap/app.php` sebagai alias `role`.
3. Terapkan route model binding di seluruh controller — hilangkan `findOrFail($id)` manual.
4. Route bersarang untuk materi dan tugas memakai `->shallow()` dan `scopeBindings()`.
5. Tambahkan pemeriksaan kepemilikan sementara (`abort_unless`) pada setiap titik rawan yang Anda daftarkan di bagian READ. Ini akan dirapikan menjadi Policy di minggu 7.
6. Halaman 403 kustom yang informatif tapi tidak membocorkan informasi (jangan tulis "submission ini milik Budi").