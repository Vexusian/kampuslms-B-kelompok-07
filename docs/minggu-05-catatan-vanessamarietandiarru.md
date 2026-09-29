# Catatan Praktikum Minggu 5

Nama: Vanessa Marie Tandiarru  
NIM: 10251118  

---

## READ — Peta Route Sendiri & Titik Rawan IDOR

### 1. Daftar Lengkap Route Aplikasi
Hasil eksekusi perintah `php artisan route:list --except-vendor`:

```text
  GET|HEAD        / .......................................................................................... dashboard › routes/web.php:19
  GET|HEAD        admin/courses ............................................................... admin.courses.index › CourseController@index
  POST            admin/courses ............................................................... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ...................................................... admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} ........................................................ admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} .................................................... admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} .................................................. admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ................................................... admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users ..................................................................... admin.users.index › UserController@index
  POST            admin/users ..................................................................... admin.users.store › UserController@store
  GET|HEAD        admin/users/create ............................................................ admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} ................................................................ admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ............................................................ admin.users.update › UserController@update
  DELETE          admin/users/{user} .......................................................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ........................................................... admin.users.edit › UserController@edit
  GET|HEAD        courses ........................................................................... courses.index › CourseController@index
  POST            courses ........................................................................... courses.store › CourseController@store
  GET|HEAD        courses/create .................................................................. courses.create › CourseController@create
  GET|HEAD        courses/{course} .................................................................... courses.show › CourseController@show
  PUT|PATCH       courses/{course} ................................................................ courses.update › CourseController@update
  DELETE          courses/{course} .............................................................. courses.destroy › CourseController@destroy
  GET|HEAD        courses/{course}/edit ............................................................... courses.edit › CourseController@edit
  GET|HEAD        dev/login/{user} ........................................................................... dev.login › routes/web.php:33
  GET|HEAD        dev/logout ................................................................................ dev.logout › routes/web.php:38
  GET|HEAD        dosen/assignments/{assignment} ........................................ dosen.assignments.show › AssignmentController@show
  PUT|PATCH       dosen/assignments/{assignment} .................................... dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} .................................. dosen.assignments.destroy › AssignmentController@destroy
  GET|HEAD        dosen/assignments/{assignment}/edit ................................... dosen.assignments.edit › AssignmentController@edit
  GET|HEAD        dosen/assignments/{assignment}/submissions .............. dosen.assignments.submissions.index › SubmissionController@index
  GET|HEAD        dosen/courses ............................................................... dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} ........................................................ dosen.courses.show › CourseController@show
  PUT|PATCH       dosen/courses/{course} .................................................... dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/assignments .......................... dosen.courses.assignments.index › AssignmentController@index
  POST            dosen/courses/{course}/assignments .......................... dosen.courses.assignments.store › AssignmentController@store
  GET|HEAD        dosen/courses/{course}/assignments/create ................. dosen.courses.assignments.create › AssignmentController@create
  GET|HEAD        dosen/courses/{course}/edit ................................................... dosen.courses.edit › CourseController@edit
  GET|HEAD        dosen/courses/{course}/materials ................................ dosen.courses.materials.index › MaterialController@index
  POST            dosen/courses/{course}/materials ................................ dosen.courses.materials.store › MaterialController@store
  GET|HEAD        dosen/courses/{course}/materials/create ....................... dosen.courses.materials.create › MaterialController@create
  GET|HEAD        dosen/materials/{material} ................................................ dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} ............................................ dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} .......................................... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/materials/{material}/edit ........................................... dosen.materials.edit › MaterialController@edit
  GET|HEAD        dosen/submissions/{submission} ........................................ dosen.submissions.show › SubmissionController@show
  GET|HEAD        login .......................................................................................... login › routes/web.php:27
  GET|HEAD        mahasiswa/assignments/{assignment} ................................ mahasiswa.assignments.show › AssignmentController@show
  POST            mahasiswa/assignments/{assignment}/submissions ...... mahasiswa.assignments.submissions.store › SubmissionController@store
  GET|HEAD        mahasiswa/courses ....................................................... mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} ................................................ mahasiswa.courses.show › CourseController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments .................. mahasiswa.courses.assignments.index › AssignmentController@index
  GET|HEAD        mahasiswa/courses/{course}/materials ........................ mahasiswa.courses.materials.index › MaterialController@index
  GET|HEAD        mahasiswa/materials/{material} ........................................ mahasiswa.materials.show › MaterialController@show
  GET|HEAD        mahasiswa/submissions/{submission} ................................ mahasiswa.submissions.show › SubmissionController@show
  GET|HEAD        mata-kuliah ................................................................... mata-kuliah.index › CourseController@index
  POST            mata-kuliah ................................................................... mata-kuliah.store › CourseController@store
  GET|HEAD        mata-kuliah/create .......................................................... mata-kuliah.create › CourseController@create
  GET|HEAD        mata-kuliah/{mata_kuliah} ....................................................... mata-kuliah.show › CourseController@show
  PUT|PATCH       mata-kuliah/{mata_kuliah} ................................................... mata-kuliah.update › CourseController@update
  DELETE          mata-kuliah/{mata_kuliah} ................................................. mata-kuliah.destroy › CourseController@destroy
  GET|HEAD        mata-kuliah/{mata_kuliah}/edit .................................................. mata-kuliah.edit › CourseController@edit
  GET|HEAD        tentang ...................................................................................... tentang › routes/web.php:23
```

### 2. Daftar Titik Rawan IDOR

Parameter model pada route mengikat data entitas secara otomatis via Route Model Binding. Namun, model binding hanya memastikan record ada di database, tanpa memvalidasi apakah pengguna yang sedang login berhak melihat atau memanipulasinya.

| No. | Method | URI | Parameter Model | Pemilik Sah / Hak Akses Sesuai Spesifikasi | Risiko IDOR Jika Tanpa Otozrisasi | Mekanisme Pencegahan Saat Ini (Lapis 1) |
|---:|---|---|---|---|---|---|
| 1 | GET | `/submissions/{submission}` | `{submission}` | Mahasiswa pemilik berkas, Dosen pengampu mata kuliah terkait, Admin | Mahasiswa lain dapat membaca berkas tugas, jawaban esai, dan nilai mahasiswa lain hanya dengan mengubah angka ID di URL. | `abort_unless($submission->user_id === auth()->id() \|\| auth()->user()->role === 'admin' \|\| $submission->assignment->course->lecturer_id === auth()->id(), 403)` pada [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php). |
| 2 | GET, PUT, DELETE | `/dosen/courses/{course}/edit` | `{course}` | Dosen pengampu mata kuliah (`lecturer_id`), Admin | Dosen A dapat mengubah silabus, deskripsi, atau menghapus mata kuliah milik Dosen B karena keduanya sama-sama memiliki role `dosen`. | `abort_unless($course->lecturer_id === auth()->id() \|\| auth()->user()->role === 'admin', 403)` pada [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php). |
| 3 | GET, PUT, DELETE | `/materials/{material}` | `{material}` | Dosen pengampu mata kuliah terkait (CRUD), Mahasiswa yang terdaftar/enrolled (hanya View/Download), Admin | Mahasiswa luar atau dosen lain dapat mengakses, mengubah, atau menghapus berkas materi perkuliahan privat. | `abort_unless(...)` memeriksa relasi enrollment mahasiswa dan `lecturer_id` dosen pada [MaterialController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/MaterialController.php). |
| 4 | GET, PUT, DELETE | `/assignments/{assignment}` | `{assignment}` | Dosen pembuat/pengampu mata kuliah (CRUD), Mahasiswa terdaftar (View), Admin | Pengguna luar dapat melihat soal tugas sebelum waktunya atau mengubah instruksi penugasan. | `abort_unless(...)` memeriksa enrollment mahasiswa serta kepemilikan pengampu pada [AssignmentController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AssignmentController.php). |
| 5 | GET, PUT, DELETE | `/admin/users/{user}` | `{user}` | Admin (seluruh user), Pengguna yang bersangkutan (hanya data profil sendiri) | Pengguna non-admin memanipulasi profil orang lain jika rute lolos dari middleware role. | Middleware `role:admin` pada grup admin serta proteksi method di [UserController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/UserController.php). |

---

## BREAK — Enam Kerusakan

| # | Yang Dicoba | Prediksi | Hasil Pengamatan Riil |
|---|-------------|----------|------------------------|
| **1** | Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengganti angka ID pada URL `/submissions/{id}` | Mahasiswa A dapat membaca submission, isi jawaban, dan nilai mahasiswa B secara bebas (IDOR murni) | **Terbukti**: Tanpa pemeriksaan kepemilikan, data submission milik mahasiswa B berhasil ditampilkan secara penuh kepada mahasiswa A dengan kode HTTP 200 OK. Ini membuktikan bahwa Route Model Binding tidak melindungi hak akses data. |
| **2** | Buka rute bersarang `/courses/1/assignments/99` di mana penugasan ID 99 sebenarnya milik mata kuliah ID 7 | Penugasan 99 tetap terbuka meskipun URL menyatakan ia berada di bawah mata kuliah 1 (*mismatched nested route*) | **Terbukti**: Laravel tetap mengeksekusi query `SELECT * FROM assignments WHERE id = 99` secara independen tanpa memverifikasi relasi parent `course_id = 1`. Konten tugas mata kuliah lain bocor di bawah hirarki URL yang keliru. |
| **3** | Aktifkan `Route::scopeBindings()` pada route group bersarang, lalu ulangi percobaan nomor 2 | Akses ditolak dengan status HTTP 404 Not Found karena tugas 99 bukan anak dari mata kuliah 1 | **Terbukti**: Laravel secara otomatis menambahkan constraint relasi relasional ke query builder: `SELECT * FROM assignments WHERE id = 99 AND course_id = 1`. Karena relasi tidak cocok, sistem melempar `ModelNotFoundException` dan menghasilkan halaman 404. |
| **4** | Daftarkan middleware baru di `app/Http/Kernel.php` seperti instruksi pada tutorial Laravel versi lawas | Terjadi error atau middleware tidak bekerja karena berkas tersebut tidak ada di Laravel 12 | **Terbukti**: Berkas `app/Http/Kernel.php` sama sekali tidak ditemukan di dalam struktur folder proyek Laravel 12. Di Laravel 12, arsitektur framework disederhanakan (*streamlined*) dan seluruh middleware wajib didaftarkan di `bootstrap/app.php`. |
| **5** | Pasang middleware `role:admin` pada grup rute, lalu lakukan request dengan akun yang memiliki peran `dosen` | Akses langsung ditolak dengan kode HTTP 403 Forbidden | **Terbukti**: Middleware `EnsureUserHasRole` membaca `$request->user()->role` yang bernilai `'dosen'`. Karena tidak terdapat pada daftar argumen yang diizinkan (`['admin']`), middleware langsung memanggil `abort(403)` dan menampilkan halaman error 403 kustom. |
| **6** | Login sebagai Dosen A, lalu kirim request edit/update ke `/courses/{id}/edit` untuk mata kuliah yang diampu Dosen B | Dosen A berhasil mengedit mata kuliah Dosen B karena keduanya lolos verifikasi `role:dosen` | **Terbukti**: Middleware `role:dosen` hanya mengecek apakah pengunjung seorang dosen. Middleware tidak tahu siapa dosen pengampu mata kuliah tersebut. Tanpa validasi kepemilikan di level objek/model, terjadi eskalasi horizontal antar dosen. |

---

## FIX — Analisis dan Perbaikan Repository Cacat (Branch w05)

Pada branch `w05` repositori `kampuslms-broken`, ditemukan 7 kesalahan mendasar terkait routing, otorisasi, dan arsitektur Laravel 12:

1. **Rute di Luar Grup `auth`:**  
   Rute dashboard dan modul materi dapat diakses oleh guest (pengguna yang belum login).  
   *Dampak Pengguna:* Orang asing yang tidak memiliki akun kampus dapat melihat daftar kuliah dan materi internal.  
   *Dampak Penyerang:* Mempermudah pengintaian (*reconnaissance*) tanpa jejak otentikasi.  
   *Perbaikan:* Seluruh rute internal dibungkus di dalam `Route::middleware('auth')->group(...)`.

2. **Dua Celah IDOR pada Modul Submission dan Material:**  
   Method `show()` hanya menerima model binding tanpa memeriksa relasi kepemilikan.  
   *Dampak Pengguna:* Berkas pengumpulan tugas mahasiswa dan materi kuliah privat terbongkar ke pihak yang tidak berhak.  
   *Dampak Penyerang:* Penyerang dapat melakukan serangan enumerasi ID (scraping otomatis) untuk mencuri seluruh data submission dan tugas satu kampus.  
   *Perbaikan:* Menambahkan otorisasi kepemilikan objek lapis pertama menggunakan `abort_unless`:
   ```php
   abort_unless(
       $submission->user_id === auth()->id()
           || auth()->user()->role === 'admin'
           || $submission->assignment->course->lecturer_id === auth()->id(),
       403,
       'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
   );
   ```

3. **Rute Bersarang Tanpa `scopeBindings()`:**  
   URL `/courses/{course}/assignments/{assignment}` tidak memeriksa apakah assignment tersebut adalah milik course yang tertera.  
   *Dampak Pengguna:* Tampilan antarmuka membingungkan karena tugas mata kuliah A muncul di halaman mata kuliah B.  
   *Dampak Penyerang:* Memanipulasi navigasi rute untuk mengakses objek privat melalui mata kuliah publik.  
   *Perbaikan:* Membungkus seluruh nested resource di dalam `Route::scopeBindings()->group(...)`.

4. **Middleware Didaftarkan pada Berkas yang Salah:**  
   Pendaftaran middleware dicoba melalui `Kernel.php` yang sudah usang di Laravel 12.  
   *Dampak Pengguna:* Fitur otorisasi tidak aktif sama sekali atau aplikasi gagal booting.  
   *Perbaikan:* Mendaftarkan alias middleware `'role'` pada konfigurasi container di [bootstrap/app.php](file:///c:/Users/vanes/laragon/www/kampuslms/bootstrap/app.php):
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'role' => \App\Http\Middleware\EnsureUserHasRole::class,
       ]);
   })
   ```

5. **Bentrok Nama Rute Antar Peran:**  
   Rute `courses.index` didefinisikan berulang kali di admin, dosen, dan mahasiswa tanpa prefix nama.  
   *Dampak Pengguna:* Pemanggilan `route('courses.index')` di Blade menghasilkan URL yang salah peran.  
   *Perbaikan:* Menggunakan penamaan berjenjang dengan `->name('admin.')`, `->name('dosen.')`, dan `->name('mahasiswa.')`.

6. **Rute Destruktif Menggunakan Method `GET`:**  
   Penghapusan data dilakukan melalui link `GET /courses/{id}/delete`.  
   *Dampak Pengguna:* Data dapat terhapus secara tidak sengaja hanya karena web crawler browser melakukan prefetching tautan.  
   *Dampak Penyerang:* Membuka celah serangan CSRF via tag gambar `<img>` atau script sederhana tanpa memerlukan token CSRF.  
   *Perbaikan:* Mengubah aksi penghapusan menjadi `Route::delete(...)` yang diakses via form ber-method `POST` dengan directive `@method('DELETE')` dan proteksi `@csrf`.

### Bukti Uji Celah IDOR Menggunakan `curl`

**Sebelum Perbaikan (Celah Terbuka - Lolos Status 200 OK):**
```bash
curl -i -X GET http://localhost:8000/mahasiswa/submissions/42 \
  -H "Cookie: laravel_session=<session_mahasiswa_A>"
```
*Hasil:*
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<div>Submission ID: 42 | Mahasiswa: Budi | Konten: Berkas tugas Budi...</div>
```

**Setelah Perbaikan (Celah Tertutup - Ditolak Status 403 Forbidden):**
```bash
curl -i -X GET http://localhost:8000/mahasiswa/submissions/42 \
  -H "Cookie: laravel_session=<session_mahasiswa_A>"
```
*Hasil:*
```http
HTTP/1.1 403 Forbidden
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html lang="id">
<head><title>403 - Akses Ditolak | KampusLMS</title></head>
<body>
    <h1>Akses Tidak Diizinkan</h1>
    <p>Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.</p>
</body>
</html>
```

---

## BUILD — Struktur Route KampusLMS

Implementasi pada repositori KampusLMS telah memenuhi seluruh kriteria Minggu 5:

1. **Struktur Route Terorganisir Berdasarkan Peran:**  
   Berkas [routes/web.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/web.php) telah disusun rapi menggunakan route group terautentikasi `auth` dan dipisahkan menjadi tiga grup peran utama:
   - Grup Admin: `prefix('admin')->name('admin.')->middleware('role:admin')` untuk pengelolaan penuh user dan mata kuliah.
   - Grup Dosen: `prefix('dosen')->name('dosen.')->middleware('role:dosen')` untuk pengelolaan materi, tugas, dan penilaian.
   - Grup Mahasiswa: `prefix('mahasiswa')->name('mahasiswa.')->middleware('role:mahasiswa')` untuk pendaftaran mata kuliah, pengunduhan materi, dan pengumpulan tugas.

2. **Middleware `EnsureUserHasRole`:**  
   Middleware kustom [EnsureUserHasRole.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Middleware/EnsureUserHasRole.php) memvalidasi peran pengguna secara dinamis melalui parameter variadik (`string ...$roles`). Middleware ini membedakan secara tegas respon bagi pengunjung belum login (401 Unauthorized) dan pengguna yang login dengan peran tidak berhak (403 Forbidden). Middleware didaftarkan sebagai alias `'role'` di [bootstrap/app.php](file:///c:/Users/vanes/laragon/www/kampuslms/bootstrap/app.php).

3. **Penerapan Route Model Binding:**  
   Seluruh controller ([CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php), [UserController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/UserController.php), [MaterialController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/MaterialController.php), [AssignmentController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AssignmentController.php), dan [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php)) telah mengadopsi implicit route model binding (contoh: `show(Course $course)` alih-alih `findOrFail($id)`).

4. **Rute Bersarang dengan `->shallow()` dan `scopeBindings()`:**  
   Rute materi dan penugasan dibungkus dengan `Route::scopeBindings()`. Kombinasi `->shallow()` menghasilkan URI ringkas: daftar materi berada di `/courses/{course}/materials`, namun rute operasi tunggal materi berada di `/materials/{material}` sehingga terhindar dari URL yang terlampau dalam (*deeply nested URLs*).

5. **Pemeriksaan Kepemilikan Sementara (Temporary IDOR Protection):**  
   Pada setiap titik rawan IDOR yang teridentifikasi, telah dipasang pemeriksaan kepemilikan berbasis `abort_unless` yang mengevaluasi relasi otorisasi pengguna (`user_id`, `lecturer_id`, dan tabel pivot mahasiswa `course_user`). Ini menjadi pengaman transisi sebelum dipindahkan ke Laravel Policy pada Minggu 7.

6. **Halaman 403 Kustom Informatif dan Aman:**  
   Telah dibuat halaman [403.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/errors/403.blade.php) yang menampilkan pesan ramah pengguna tanpa membocorkan identitas pemilik asli data (*information disclosure*).

7. **Verifikasi Pengujian Otomatis:**  
   Seluruh implementasi di atas telah divalidasi melalui rangkaian tes otomatis [Minggu5Test.php](file:///c:/Users/vanes/laragon/www/kampuslms/tests/Feature/Minggu5Test.php) dan dinyatakan lulus (PASS) untuk proteksi middleware, scope bindings, IDOR protection, dan rendering error page 403.

---

## Checkpoint Minggu 5

### 1. Apa itu IDOR? Peragakan satu contoh di aplikasi Anda, lalu tunjukkan perbaikannya.
> **Jawaban:**  
> **IDOR (*Insecure Direct Object Reference*)** adalah kerentanan keamanan kontrol akses di mana aplikasi menggunakan referensi langsung berupa identifier (seperti ID database integer berurutan) yang disediakan pengguna untuk mengakses objek internal sistem secara langsung tanpa melakukan verifikasi otorisasi apakah pengguna tersebut berhak atas objek itu.
>
> **Peragaan Contoh di KampusLMS:**  
> Seorang mahasiswa bernama Doni membuka tugas miliknya pada URL:  
> `GET /mahasiswa/submissions/10`  
> Jika controller ditulis seperti ini:
> ```php
> public function show(Submission $submission)
> {
>     return view('submissions.show', compact('submission'));
> }
> ```
> Doni cukup mengganti angka pada URL browser menjadi `/mahasiswa/submissions/11`. Laravel akan mencarikan data submission nomor 11 di database. Karena tidak ada pengecekan kepemilikan, Doni dapat membaca seluruh berkas tugas, komentar dosen, dan nilai milik mahasiswa lain.
>
> **Perbaikan:**  
> Tambahkan pemeriksaan otorisasi kepemilikan sebelum data ditampilkan:
> ```php
> public function show(Submission $submission)
> {
>     abort_unless(
>         $submission->user_id === auth()->id()
>             || auth()->user()->role === 'admin'
>             || $submission->assignment->course->lecturer_id === auth()->id(),
>         403,
>         'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
>     );
> 
>     return view('submissions.show', compact('submission'));
> }
> ```
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Di mana letak posisi IDOR dalam standar keamanan OWASP Top 10?"*  
>   **Jawaban Mahasiswa:** IDOR diklasifikasikan ke dalam kategori **A01:2021 — Broken Access Control**, yang menempati peringkat pertama sebagai risiko keamanan aplikasi web paling kritis dan paling banyak ditemukan di dunia nyata.
> - **Pertanyaan Lanjutan 2:** *"Kenapa validasi Form Request tidak bisa digunakan untuk mencegah IDOR pada kasus route model binding?"*  
>   **Jawaban Mahasiswa:** Form Request memvalidasi format data input HTTP (seperti tipe string, keharusan field, atau format email). Form Request pada request `GET` tidak secara otomatis memblokir akses model yang sudah di-resolve oleh route parameter sebelum method controller dieksekusi, kecuali kita mendefinisikan otorisasi khusus di method `authorize()` Form Request tersebut atau menggunakan Policy.

---

### 2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?
> **Jawaban:**  
> Mengganti ID integer berurutan (misal `1, 2, 3`) menjadi UUID (misal `c28b6d39-0d3a-4428-98e3-0599a80e1c24`) **bukan merupakan solusi otorisasi**, melainkan bentuk **Security through Obscurity** (keamanan semu melalui pengaburan).
>
> UUID hanya membuat ID menjadi tidak dapat ditebak secara acak (*unguessable*). Namun prinsip keamanan menegaskan bahwa kerentanan kontrol akses tetap ada:
> 1. **UUID Dapat Bocor:** UUID dapat bocor melalui header `Referer`, log server web, riwayat peramban (*browser history*), tangkapan layar, obrolan grup mahasiswa, atau endpoint API publik lainnya.
> 2. **Akses Tetap Terbuka:** Begitu penyerang mendapatkan string UUID korban, ia tetap bisa membuka URL `/submissions/c28b6d39-0d3a-4428-98e3-0599a80e1c24` dan server tetap menyajikan data tersebut jika tidak ada pengecekan kepemilikan.
>
> Perbaikan IDOR yang hakiki adalah **pemeriksaan otorisasi di sisi server**: server wajib mengecek *"Apakah pengguna yang sedang terotentikasi memiliki izin untuk melihat record ini?"*, tidak peduli apakah ID-nya berupa integer 1 digit atau UUID 36 karakter.
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Jika UUID bukan solusi keamanan otorisasi, kapan sebaiknya kita menggunakan UUID?"*  
>   **Jawaban Mahasiswa:** UUID tepat digunakan untuk sistem terdistribusi/multi-server agar pembuatan primary key tidak saling bentrok tanpa perlu koordinasi database terpusat, atau untuk identifier publik di mana kita tidak ingin pihak luar mengetahui total volume data bisnis kita (contoh: nomor invoice atau total jumlah pengguna yang terdaftar).
> - **Pertanyaan Lanjutan 2:** *"Apa trade-off performa penggunaan UUID dibandingkan BigInteger berurutan di database MySQL/InnoDB?"*  
>   **Jawaban Mahasiswa:** UUID (khususnya v4 acak) menyebabkan fragmentasi indeks B-Tree yang tinggi pada clustered index InnoDB karena nilainya tidak urut. Hal ini menurunkan performa operasi insert data dalam volume besar dan memakan ukuran memori indeks jauh lebih besar (16/36 byte vs 8 byte pada BIGINT).

---

### 3. Route model binding menjamin apa, dan tidak menjamin apa?
> **Jawaban:**  
> - **Yang DIJAMIN:**  
>   Route model binding menjamin **keberadaan data (*existence*)** di dalam database. Laravel secara otomatis menjalankan query pencarian berdasarkan key (default: `id`) dan memastikan objek record tersebut ditemukan. Jika record tidak ditemukan, Laravel otomatis membatalkan request dan mengembalikan respon `404 Not Found`.
> - **Yang TIDAK DIJAMIN:**  
>   Route model binding **sama sekali tidak menjamin otorisasi (*authorization*) atau hak akses**. Model binding tidak mengetahui siapa yang sedang login, peran apa yang disandangnya, dan apakah pengguna tersebut adalah pemilik sah dari data tersebut.
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Bagaimana cara mengubah kolom pencarian pada Route Model Binding, misalnya ingin mencari berdasarkan kode mata kuliah bukan ID?"*  
>   **Jawaban Mahasiswa:** Kita bisa menggunakan custom key binding langsung pada definisi route di `web.php`, contohnya: `Route::get('/courses/{course:code}', ...)`. Laravel akan otomatis mencari record menggunakan query `WHERE code = ?`. Di level model, kita juga bisa meng-override method `getRouteKeyName()`.
> - **Pertanyaan Lanjutan 2:** *"Bagaimana perilaku Route Model Binding jika model mengimplementasikan trait SoftDeletes?"*  
>   **Jawaban Mahasiswa:** Secara default, Route Model Binding otomatis mengecualikan record yang telah di-soft delete (`WHERE deleted_at IS NULL`). Jika record sudah dihapus secara lunak, rute akan mengembalikan respon 404. Jika ingin menyertakan data yang terhapus, kita harus menambahkan method `->withTrashed()` pada deklarasi route.

---

### 4. Apa yang dilakukan `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.
> **Jawaban:**  
> `Route::scopeBindings()` menginstruksikan Laravel untuk mengevaluasi relasi hirarki antar model pada rute bersarang (*nested routes*). Laravel akan membatasi query pencarian model anak (*child*) agar **secara eksplisit terikat pada relasi model induk (*parent*)**.
>
> **Contoh Skenario yang Lolos Tanpa `scopeBindings()`:**  
> Didefinisikan rute:  
> `/courses/{course}/assignments/{assignment}`  
> - Mata kuliah A memiliki `id = 1`.
> - Mata kuliah B memiliki `id = 7`.
> - Tugas *"Ujian Akhir Rahasia"* milik Mata Kuliah B memiliki `id = 99`.
>
> Jika seorang pengguna mengakses URL:  
> `/courses/1/assignments/99`  
> - **Tanpa `scopeBindings()`:** Laravel meresolusi `{course}` (menemukan Mata Kuliah 1) dan meresolusi `{assignment}` (menemukan Assignment 99 secara independen). Hasilnya halaman tugas 99 terbuka dengan sukses, padahal tugas 99 sama sekali bukan milik mata kuliah 1.
> - **Dengan `scopeBindings()`:** Laravel menyusun query model anak melalui relasi model induk: `$course->assignments()->findOrFail($assignmentId)`. Karena tugas 99 memiliki `course_id = 7` (bukan 1), pencarian gagal dan Laravel mengembalikan respon `404 Not Found`.
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Bagaimana Laravel mengetahui relasi apa yang harus digunakan untuk mengikat assignment ke course?"*  
>   **Jawaban Mahasiswa:** Laravel menggunakan konvensi penamaan Eloquent. Laravel memeriksa method relasi pada model induk (`Course`) yang cocok dengan bentuk jamak dari nama parameter anak (`assignments()`).
> - **Pertanyaan Lanjutan 2:** *"Apa perbedaan filosofis antara `scopeBindings()` dan pemanggilan method `->shallow()` pada nested resource?"*  
>   **Jawaban Mahasiswa:** `->shallow()` berfokus pada **struktur URL** agar tidak terjadi rute yang terlalu dalam (misalnya rute create/index tetap di bawah `/courses/{course}/assignments`, tapi rute show/edit ditarik ke `/assignments/{assignment}`). Sedangkan `scopeBindings()` berfokus pada **integritas relasi data** pada URL bersarang yang memiliki dua atau lebih parameter model.

---

### 5. Di berkas mana middleware didaftarkan pada Laravel 12? Kenapa berbeda dari kebanyakan tutorial?
> **Jawaban:**  
> Pada Laravel 12, middleware didaftarkan di dalam berkas **`bootstrap/app.php`**, melalui closure konfigurasi `->withMiddleware(function (Middleware $middleware) { ... })`.
>
> Contoh pendaftaran alias middleware `role`:
> ```php
> ->withMiddleware(function (Middleware $middleware) {
>     $middleware->alias([
>         'role' => \App\Http\Middleware\EnsureUserHasRole::class,
>     ]);
> })
> ```
>
> **Alasan Berbeda dari Kebanyakan Tutorial:**  
> Sebagian besar tutorial di internet ditulis untuk Laravel versi 10 ke bawah. Pada arsitektur lama, middleware didaftarkan di dalam berkas `app/Http/Kernel.php` pada properti `$middlewareAliases` atau `$routeMiddleware`.  
> Mulai **Laravel 11 dan diteruskan ke Laravel 12**, Laravel memperkenalkan arsitektur aplikasi yang disederhanakan (*Streamlined Application Structure*). Seluruh direktori dan berkas boilerplate seperti `app/Http/Kernel.php`, `app/Console/Kernel.php`, dan folder `routes/` yang berlebihan telah dihapus. Konfigurasi routing, middleware, dan exception handling kini disatukan secara elegan dan modern di dalam satu berkas pusat: `bootstrap/app.php`.
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Bagaimana cara mendaftarkan middleware global di Laravel 12 yang berjalan di setiap HTTP request?"*  
>   **Jawaban Mahasiswa:** Menggunakan method `$middleware->append(NamaMiddleware::class)` atau `$middleware->prepend(NamaMiddleware::class)` di dalam closure `withMiddleware()` pada `bootstrap/app.php`.
> - **Pertanyaan Lanjutan 2:** *"Bagaimana cara menambahkan middleware ke dalam grup tertentu saja (misalnya grup web atau api) di Laravel 12?"*  
>   **Jawaban Mahasiswa:** Menggunakan method `$middleware->web(append: [NamaMiddleware::class])` atau `$middleware->api(append: [NamaMiddleware::class])`.

---

### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?
> **Jawaban:**  
> Karena middleware bekerja pada **level gerbang akses rute makro (*Route-level Authorization*)**, bukan pada **level instansi data (*Object/Record-level Authorization*)**.
>
> Penjelasan Alur:
> 1. Dosen A login ke sistem dengan status peran `role = 'dosen'`.
> 2. Dosen A mengirimkan request HTTP `GET /dosen/courses/5/edit` (di mana mata kuliah ID 5 sebenarnya diampu oleh Dosen B).
> 3. Middleware `role:dosen` mencegat request dan bertanya: *"Apakah pengguna yang login saat ini memiliki peran 'dosen'?"*.
> 4. Karena Dosen A memang seorang dosen, kondisi terpenuhi dan middleware **meloloskan request** ke controller.
> 5. Middleware sama sekali tidak memeriksa kolom `courses.lecturer_id` dari objek mata kuliah nomor 5 tersebut.
>
> Inilah yang disebut celah **Horizontal Privilege Escalation** (eskalasi hak akses antar pengguna dengan peran setingkat). Untuk mengatasinya, verifikasi kepemilikan objek wajib dilakukan:
> - Pada Minggu 5: Menggunakan pengecekan sementara di controller:  
>   `abort_unless($course->lecturer_id === auth()->id(), 403);`
> - Pada Minggu 7: Menggunakan **Laravel Policy** (`$this->authorize('update', $course)`), di mana aturan otorisasi kepemilikan data dipisahkan secara bersih ke kelas tersendiri.
>
> #### Pengembangan Pertanyaan Penguji:
> - **Pertanyaan Lanjutan 1:** *"Kapan kita harus menggunakan Middleware dan kapan kita harus menggunakan Policy?"*  
>   **Jawaban Mahasiswa:**  
>   - Gunakan **Middleware** saat keputusan otorisasi **tidak membutuhkan instansi model/data spesifik** (contoh: "Apakah pengguna sudah login?", "Apakah pengguna berstatus admin?", "Apakah email pengguna sudah terverifikasi?").  
>   - Gunakan **Policy** saat keputusan otorisasi **bergantung pada atribut atau relasi dari data yang spesifik** (contoh: "Apakah dosen ini adalah pengampu mata kuliah ini?", "Apakah mahasiswa ini adalah pemilik submission ini?").
> - **Pertanyaan Lanjutan 2:** *"Jika kita tidak memakai Policy dan hanya menyalin kode `abort_unless` di banyak controller, apa kerugian arsitektural yang terjadi?"*  
>   **Jawaban Mahasiswa:** Melanggar prinsip DRY (*Don't Repeat Yourself*). Jika terjadi perubahan aturan bisnis (misalnya asisten dosen diizinkan ikut mengedit tugas), kita harus mencari dan memperbarui kode otorisasi tersebut satu per satu di puluhan method controller yang tersebar. Jika terlewat satu method saja, akan tercipta lubang keamanan IDOR baru.
