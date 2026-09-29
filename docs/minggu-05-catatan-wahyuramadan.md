# Catatan Praktikum Minggu 5 — Routing Lanjutan, Route Model Binding, dan Middleware

**Nama:** Wahyu Ramadan  
**NIM:** 10241074  
**Kelas / Kelompok:** B / Kelompok 07  
**Proyek:** KampusLMS (Laravel 12)  

---

## READ — Peta Route Sendiri & Titik Rawan IDOR

### 1. Daftar Lengkap Route Aplikasi

Pemetaan route internal aplikasi KampusLMS yang dieksekusi melalui perintah `php artisan route:list --except-vendor`:

```text
  GET|HEAD        / ..................................................... dashboard › routes/web.php:28
  GET|HEAD        tentang ................................................. tentang › routes/web.php:32
  GET|HEAD        login ..................................................... login › routes/web.php:36
  POST            logout ................................................... logout › routes/web.php:40
  GET|HEAD        dev/login/{user} ......................................... dev.login › routes/web.php:49
  GET|HEAD        dev/logout ............................................. dev.logout › routes/web.php:54

  GET|HEAD        admin/courses ......................................... admin.courses.index › CourseController@index
  POST            admin/courses ......................................... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ................................ admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} .................................. admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} .............................. admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} ............................ admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ............................. admin.courses.edit › CourseController@edit
  GET|HEAD        admin/courses/{course}/enrollments ............... admin.courses.enrollments.index › EnrollmentController@index
  POST            admin/courses/{course}/enrollments ............... admin.courses.enrollments.store › EnrollmentController@store
  DELETE          admin/enrollments/{enrollment} ..................... admin.enrollments.destroy › EnrollmentController@destroy
  GET|HEAD        admin/users ............................................... admin.users.index › UserController@index
  POST            admin/users ............................................... admin.users.store › UserController@store
  GET|HEAD        admin/users/create ...................................... admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} .......................................... admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ...................................... admin.users.update › UserController@update
  DELETE          admin/users/{user} .................................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ..................................... admin.users.edit › UserController@edit

  GET|HEAD        dosen/courses ......................................... dosen.courses.index › CourseController@index
  GET|HEAD        dosen/courses/{course} .................................. dosen.courses.show › CourseController@show
  GET|HEAD        dosen/courses/{course}/edit ............................. dosen.courses.edit › CourseController@edit
  PUT|PATCH       dosen/courses/{course} .............................. dosen.courses.update › CourseController@update
  GET|HEAD        dosen/courses/{course}/students ............. dosen.courses.students.index › EnrollmentController@index
  GET|HEAD        dosen/courses/{course}/materials .......... dosen.courses.materials.index › MaterialController@index
  POST            dosen/courses/{course}/materials .......... dosen.courses.materials.store › MaterialController@store
  GET|HEAD        dosen/courses/{course}/materials/create . dosen.courses.materials.create › MaterialController@create
  GET|HEAD        dosen/materials/{material} .......................... dosen.materials.show › MaterialController@show
  GET|HEAD        dosen/materials/{material}/edit ..................... dosen.materials.edit › MaterialController@edit
  PUT|PATCH       dosen/materials/{material} ...................... dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} .................... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/courses/{course}/assignments .... dosen.courses.assignments.index › AssignmentController@index
  POST            dosen/courses/{course}/assignments .... dosen.courses.assignments.store › AssignmentController@store
  GET|HEAD        dosen/courses/{course}/assignments/create dosen.courses.assignments.create › AssignmentController@create
  GET|HEAD        dosen/courses/{course}/assignments/{assignment} dosen.courses.assignments.show › AssignmentController@show
  GET|HEAD        dosen/assignments/{assignment} .................. dosen.assignments.show › AssignmentController@show
  GET|HEAD        dosen/assignments/{assignment}/edit ............. dosen.assignments.edit › AssignmentController@edit
  PUT|PATCH       dosen/assignments/{assignment} .............. dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} ............ dosen.assignments.destroy › AssignmentController@destroy
  GET|HEAD        dosen/assignments/{assignment}/submissions dosen.assignments.submissions.index › SubmissionController@index
  GET|HEAD        dosen/submissions/{submission} .................. dosen.submissions.show › SubmissionController@show
  POST            dosen/submissions/{submission}/grades ........ dosen.submissions.grades.store › GradeController@store
  PUT|PATCH       dosen/grades/{grade} ................................. dosen.grades.update › GradeController@update

  GET|HEAD        mahasiswa/courses ................................. mahasiswa.courses.index › CourseController@index
  GET|HEAD        mahasiswa/courses/{course} .......................... mahasiswa.courses.show › CourseController@show
  POST            mahasiswa/courses/{course}/enroll ................... mahasiswa.courses.enroll › EnrollmentController@store
  DELETE          mahasiswa/courses/{course}/drop ....................... mahasiswa.courses.drop › EnrollmentController@destroy
  GET|HEAD        mahasiswa/courses/{course}/materials .. mahasiswa.courses.materials.index › MaterialController@index
  GET|HEAD        mahasiswa/materials/{material} .................. mahasiswa.materials.show › MaterialController@show
  GET|HEAD        mahasiswa/courses/{course}/assignments mahasiswa.courses.assignments.index › AssignmentController@index
  GET|HEAD        mahasiswa/assignments/{assignment} .......... mahasiswa.assignments.show › AssignmentController@show
  POST            mahasiswa/assignments/{assignment}/submissions mahasiswa.assignments.submissions.store › SubmissionController@store
  GET|HEAD        mahasiswa/submissions/{submission} .......... mahasiswa.submissions.show › SubmissionController@show
  GET|HEAD        mahasiswa/grades .................................... mahasiswa.grades.index › GradeController@index
```

---

### 2. Analisis Titik Rawan IDOR (*Insecure Direct Object Reference*)

Pada route yang memanfaatkan **Route Model Binding**, Laravel mengikat parameter URL (misalnya `{submission}`, `{course}`, `{material}`) secara otomatis ke entitas model database. Model binding menjamin record tersebut **ada di database**, namun **sama sekali tidak memeriksa otorisasi kepemilikan**. 

Tanpa pengecekan otorisasi tingkat data (*record-level authorization*), pengguna cukup memanipulasi angka ID pada URL untuk membaca, mengubah, atau menghapus data milik pengguna lain.

#### Tabel Daftar Titik Rawan IDOR:

| No. | Method | URI | Parameter Model | Pemilik Sah / Hak Akses | Risiko IDOR Jika Tanpa Otorisasi | Mekanisme Pencegahan (Lapis 1) |
|---:|---|---|---|---|---|---|
| 1 | GET | `/mahasiswa/submissions/{submission}` | `{submission}` | Mahasiswa pemilik berkas, Dosen pengampu mata kuliah terkait, Admin | Mahasiswa dapat mengintip jawaban tugas, file lampiran, dan nilai mahasiswa lain hanya dengan mengganti ID integer di URL. | `abort_unless($submission->user_id === auth()->id() \|\| auth()->user()->role === 'admin' \|\| $submission->assignment->course->lecturer_id === auth()->id(), 403)` pada `SubmissionController@show`. |
| 2 | GET, PUT, DELETE | `/dosen/courses/{course}/edit` | `{course}` | Dosen pengampu mata kuliah (`lecturer_id`), Admin | Dosen A dapat mengubah silabus, mengganti deskripsi, atau menghapus mata kuliah milik Dosen B karena keduanya berstatus `role:dosen`. | `abort_unless($course->lecturer_id === auth()->id() \|\| auth()->user()->role === 'admin', 403)` pada `CourseController`. |
| 3 | GET, PUT, DELETE | `/dosen/materials/{material}` | `{material}` | Dosen pengampu mata kuliah terkait (CRUD), Mahasiswa terdaftar (View), Admin | Dosen lain dapat menghapus/mengubah file modul kuliah rekan dosennya; mahasiswa luar bisa mengunduh modul privat. | Validasi `lecturer_id` pada parent `course` serta status enrollment mahasiswa pada `MaterialController`. |
| 4 | GET, PUT, DELETE | `/dosen/assignments/{assignment}` | `{assignment}` | Dosen pembuat/pengampu (CRUD), Mahasiswa terdaftar (View), Admin | Dosen lain dapat merusak soal ujian/tugas; mahasiswa dapat melihat soal sebelum masa penugasan dibuka. | Validasi kepemilikan pengampu `course->lecturer_id` dan pendaftaran mahasiswa pada `AssignmentController`. |
| 5 | GET, PUT, DELETE | `/admin/users/{user}` | `{user}` | Admin sistem, Pengguna bersangkutan (hanya untuk data profil pribadi) | Pengguna non-admin dapat memanipulasi identitas atau hak akses user lain jika middleware rute bocor. | Middleware `role:admin` pada grup admin serta pengecekan ID di `UserController`. |
| 6 | POST, PUT | `/dosen/submissions/{submission}/grades` | `{submission}` | Dosen pengampu mata kuliah terkait, Admin | Dosen dari prodi/mata kuliah lain dapat memberi nilai atau mengubah nilai mahasiswa yang bukan mahasiswanya. | Validasi `$submission->assignment->course->lecturer_id === auth()->id()` pada `GradeController`. |

---

## BREAK — Enam Kerusakan Praktikum

| # | Skenario Percobaan | Prediksi Awal | Hasil Pengamatan Riil & Analisis |
|---|---|---|---|
| **1** | Login sebagai Mahasiswa A, lalu buka URL submission milik Mahasiswa B: `/mahasiswa/submissions/{id_B}` | Mahasiswa A dapat membaca file tugas dan nilai milik Mahasiswa B secara bebas (IDOR murni). | **Terbukti**: Sistem merespons dengan HTTP 200 OK dan menyajikan data submission Mahasiswa B secara utuh. Route Model Binding hanya mengecek kueri `SELECT * FROM submissions WHERE id = ?`. Tanpa filter otorisasi kepemilikan akun, data privat bocor. |
| **2** | Akses URL bersarang: `/courses/1/assignments/99` di mana Assignment ID 99 sebenarnya milik Course ID 7 (bukan Course 1). | Assignment 99 tetap terbuka meskipun URL menyatakan ia berada di bawah Course 1 (*mismatched nested route*). | **Terbukti**: Laravel menjalankan kueri independen untuk `{course}` dan `{assignment}`. Halaman tugas dari mata kuliah lain berhasil dimuat di bawah URL hirarki yang keliru tanpa validasi hubungan parent-child. |
| **3** | Aktifkan `Route::scopeBindings()` pada route group bersarang, lalu akses kembali `/courses/1/assignments/99`. | Permintaan ditolak dengan status HTTP 404 Not Found karena tugas 99 bukan milik course 1. | **Terbukti**: Dengan `scopeBindings()`, Laravel otomatis menambahkan constraint relasi relasional Eloquent: `SELECT * FROM assignments WHERE id = 99 AND course_id = 1`. Karena data tidak cocok, Laravel melempar `ModelNotFoundException` (404 Not Found). |
| **4** | Daftarkan middleware baru di berkas `app/Http/Kernel.php` seperti panduan tutorial Laravel versi lama. | Aplikasi mengalami error atau middleware tidak bekerja karena berkas tersebut tidak ada di Laravel 12. | **Terbukti**: Berkas `app/Http/Kernel.php` tidak ditemukan di dalam direktori proyek. Di Laravel 12 arsitektur aplikasi telah disederhanakan (*streamlined structure*); seluruh pendaftaran alias middleware wajib dikonfigurasi melalui `bootstrap/app.php`. |
| **5** | Pasang middleware `role:admin` pada route group admin, lalu akses route tersebut menggunakan akun ber-role `dosen`. | Permintaan langsung diblokir dan menghasilkan kode status HTTP 403 Forbidden. | **Terbukti**: Middleware `EnsureUserHasRole` membaca properti `auth()->user()->role` yang bernilai `'dosen'`. Karena role tersebut tidak ada di daftar parameter yang diizinkan (`admin`), sistem memanggil `abort(403)` dan menampilkan halaman 403 kustom. |
| **6** | Login sebagai Dosen A, lalu buka form edit `/dosen/courses/{id}/edit` untuk mata kuliah milik Dosen B. | Dosen A berhasil membuka dan mengedit mata kuliah Dosen B karena keduanya sama-sama memiliki peran `dosen`. | **Terbukti**: Middleware `role:dosen` hanya mengecek apakah pengguna yang login adalah dosen. Middleware tidak tahu siapa dosen pengampu mata kuliah spesifik tersebut. Terjadi eskalasi hak akses horizontal (*Horizontal Privilege Escalation*). |

---

## FIX — Analisis dan Perbaikan Repository Cacat (Branch w05)

Pada repositori `kampuslms-broken` branch `w05`, ditemukan 7 permasalahan keamanan dan routing:

1. **Rute Internal Terbuka Tanpa Grup `auth`:**  
   Rute dashboard dan modul materi dapat diakses oleh guest (pengunjung tanpa login).  
   *Dampak Pengguna:* Orang luar kampus dapat melihat materi internal perkuliahan.  
   *Dampak Penyerang:* Memudahkan pengintaian (*reconnaissance*) tanpa jejak log autentikasi.  
   *Solusi:* Membungkus seluruh rute internal di dalam `Route::middleware('auth')->group(...)`.

2. **Dua Celah IDOR pada Modul Submission dan Material:**  
   Method `show()` hanya mengandalkan model binding tanpa memvalidasi kepemilikan pengguna.  
   *Dampak Pengguna:* Nilai dan tugas mahasiswa terbuka untuk publik/mahasiswa lain.  
   *Dampak Penyerang:* Penyerang dapat melakukan enumerasi integer sederhana (scraping otomatis) untuk mendownload seluruh tugas satu universitas.  
   *Solusi:* Menambahkan pemeriksaan otorisasi objek lapis pertama menggunakan `abort_unless`:
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
   Rute `/courses/{course}/assignments/{assignment}` tidak memeriksa apakah assignment tersebut adalah milik course yang tertera di URL.  
   *Dampak Pengguna:* Tampilan antarmuka rancu karena data tugas mata kuliah A muncul di bawah judul mata kuliah B.  
   *Dampak Penyerang:* Memanipulasi parameter URL untuk mengakses penugasan tersembunyi.  
   *Solusi:* Membungkus rute bersarang dengan `Route::scopeBindings()->group(...)`.

4. **Pendaftaran Middleware di Berkas yang Salah (`Kernel.php`):**  
   Middleware dicoba didaftarkan di `app/Http/Kernel.php` yang sudah usang di Laravel 12.  
   *Dampak Pengguna:* Proteksi otorisasi tidak aktif atau aplikasi error saat booting.  
   *Solusi:* Mendaftarkan alias middleware `'role'` pada konfigurasi container di `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'role' => \App\Http\Middleware\EnsureUserHasRole::class,
       ]);
   })
   ```

5. **Bentrok Nama Rute (*Route Name Collision*):**  
   Rute `courses.index` didefinisikan berulang kali di grup admin, dosen, dan mahasiswa tanpa prefix nama rute.  
   *Dampak Pengguna:* Pemanggilan `route('courses.index')` pada view menghasilkan URL yang salah peran atau mengarah ke halaman yang tidak berhak diakses.  
   *Solusi:* Menggunakan prefix nama berjenjang: `->name('admin.')`, `->name('dosen.')`, dan `->name('mahasiswa.')`.

6. **Operasi Destruktif Menggunakan Method `GET`:**  
   Penghapusan data dilakukan melalui link `GET /courses/{id}/delete`.  
   *Dampak Pengguna:* Data dapat terhapus tanpa sengaja akibat tautan di-prefetch oleh browser atau bot crawler.  
   *Dampak Penyerang:* Membuka celah CSRF via tag `<img src="/courses/5/delete">` tanpa perlu token validasi.  
   *Solusi:* Mengubah aksi penghapusan menjadi `Route::delete(...)` yang diakses via formulir ber-method `POST` dengan directive `@method('DELETE')` dan proteksi `@csrf`.

7. **Pesan Kesalahan 403 Membocorkan Informasi Sensitif:**  
   Halaman error default menampilkan identitas pemilik dokumen (Information Disclosure).  
   *Solusi:* Membuat tampilan error kustom `resources/views/errors/403.blade.php` yang aman, santun, dan tidak membuka identitas pemilik asli.

---

### Bukti Pengujian Celah IDOR Menggunakan `curl`

**Sebelum Perbaikan (Celah Terbuka — Berhasil Diakses HTTP 200 OK):**
```bash
curl -i -X GET http://kampuslms.test/mahasiswa/submissions/42 \
  -H "Cookie: laravel_session=eyJpdiI6In..."
```
*Respons:*
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<div>Submission ID: 42 | Pemilik: Mahasiswa B | Jawaban: File_Tugas_B.pdf</div>
```

**Setelah Perbaikan (Celah Tertutup — Ditolak HTTP 403 Forbidden):**
```bash
curl -i -X GET http://kampuslms.test/mahasiswa/submissions/42 \
  -H "Cookie: laravel_session=eyJpdiI6In..."
```
*Respons:*
```http
HTTP/1.1 403 Forbidden
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html lang="id">
<head><title>403 - Akses Ditolak | KampusLMS</title></head>
<body>
    <h1>Akses Tidak Diizinkan (HTTP 403 Forbidden)</h1>
    <p>Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.</p>
</body>
</html>
```

---

## BUILD — Struktur Route KampusLMS

Implementasi sistem routing dan otorisasi pada repositori KampusLMS:

1. **Struktur Route Terisolasi Berdasarkan Peran:**  
   Berkas `routes/web.php` disusun rapi menggunakan `Route::middleware('auth')` dan dipisahkan menjadi tiga grup utama:
   - **Grup Admin:** `prefix('admin')->name('admin.')->middleware('role:admin')` untuk pengelolaan akun user, mata kuliah, dan plotting pendaftaran.
   - **Grup Dosen:** `prefix('dosen')->name('dosen.')->middleware('role:dosen')` untuk pengelolaan silabus mata kuliah, materi, penugasan, dan penilaian mahasiswa.
   - **Grup Mahasiswa:** `prefix('mahasiswa')->name('mahasiswa.')->middleware('role:mahasiswa')` untuk pendaftaran mata kuliah mandiri, unduh materi, pengumpulan tugas, dan cek transkrip nilai.

2. **Middleware Dinamis `EnsureUserHasRole`:**  
   Dibuat di `app/Http/Middleware/EnsureUserHasRole.php` menggunakan parameter variadik (`string ...$roles`). Middleware ini membedakan respons bagi pengguna belum login (`401 Unauthorized`) dan pengguna login yang salah peran (`403 Forbidden`). Didaftarkan sebagai alias `'role'` di `bootstrap/app.php`.

3. **Penerapan Penuh Route Model Binding:**  
   Seluruh controller (`CourseController`, `UserController`, `MaterialController`, `AssignmentController`, `SubmissionController`, `GradeController`) telah menggunakan implicit route model binding (contoh: `show(Course $course)` menggantikan `findOrFail($id)`).

4. **Kombinasi `->shallow()` dan `Route::scopeBindings()`:**  
   Rute nested penugasan dan materi dibungkus dengan `Route::scopeBindings()`. Penggunaan `->shallow()` menghasilkan URL yang bersih: URL koleksi tetap `/courses/{course}/assignments`, namun operasi per-item ditarik ke level atas `/assignments/{assignment}` tanpa nesting yang berlebihan.

5. **Proteksi Otorisasi Objek Lapis 1 (Temporary IDOR Protection):**  
   Pada seluruh titik rawan yang dipetakan pada tahap READ, dipasang verifikasi kepemilikan berbasis `abort_unless` yang mengevaluasi relasi user (`user_id`, `lecturer_id`, dan tabel pivot `course_user`) sebelum data dirender.

6. **Halaman 403 Kustom Ramah dan Aman:**  
   Tersedia di `resources/views/errors/403.blade.php` dengan tata letak rapi, pesan yang mendidik, dan tombol navigasi kembali ke dashboard tanpa membocorkan rincian teknis record.

7. **Verifikasi Pengujian Otomatis (*Feature Testing*):**  
   Rangkaian tes otomatis pada `tests/Feature/Minggu5Test.php` memvalidasi bahwa guest dialihkan ke login, role middleware menolak peran yang salah (403), scope bindings menolak assignment yang tidak cocok (404), IDOR protection menolak akses submission antar mahasiswa (403), dan halaman 403 dirender dengan benar.

---

## Checkpoint Minggu 5 — Pembahasan Soal Konseptual

### 1. Apa itu IDOR? Peragakan satu contoh di aplikasi Anda, lalu tunjukkan perbaikannya.

> **Jawaban:**  
> **IDOR (*Insecure Direct Object Reference*)** adalah kerentanan kontrol akses di mana aplikasi web menggunakan input langsung dari pengguna (seperti parameter ID database di URL) untuk mengakses record data tanpa memverifikasi apakah pengguna yang sedang login memiliki hak otorisasi terhadap data tersebut.
>
> **Peragaan Contoh di KampusLMS:**  
> Mahasiswa A membuka tugasnya pada endpoint:  
> `GET /mahasiswa/submissions/10`  
> Jika controller ditulis tanpa otorisasi objek:
> ```php
> public function show(Submission $submission)
> {
>     return view('submissions.show', compact('submission'));
> }
> ```
> Mahasiswa A dapat mengubah angka pada URL menjadi `/mahasiswa/submissions/11`. Laravel akan mencarikan data submission ID 11 di database. Karena tidak ada validasi kepemilikan, Mahasiswa A dapat membaca jawaban dan nilai milik Mahasiswa B.
>
> **Perbaikan:**  
> Tambahkan pemeriksaan otorisasi kepemilikan sebelum view ditampilkan:
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

---

### 2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?

> **Jawaban:**  
> Mengganti ID integer berurutan (`1, 2, 3`) menjadi UUID (`550e8400-e29b-41d4-a716-446655440000`) **bukan solusi otorisasi**, melainkan bentuk **Security through Obscurity** (keamanan semu melalui pengaburan).
>
> UUID hanya membuat identifier sulit ditebak secara acak (*unguessable*), tetapi tidak memecahkan akar masalah otorisasi:
> 1. **UUID Tetap Bisa Bocor:** Nilai UUID dapat bocor melalui log server, riwayat browser (*history*), header referer, tangkapan layar, obrolan grup mahasiswa, atau payload respons API lain.
> 2. **Akses Tetap Terbuka:** Begitu penyerang mendapatkan string UUID korban, ia tetap dapat membuka URL `/submissions/{uuid}` dan sistem akan menyajikan data tersebut jika server tidak mengecek kepemilikan.
>
> Perbaikan IDOR yang benar adalah **verifikasi otorisasi di sisi server**: server wajib mengecek *"Apakah pengguna yang sedang login berhak mengakses objek data ini?"*, terlepas dari apakah kuncinya berupa angka 1 digit atau UUID 36 karakter.

---

### 3. Route model binding menjamin apa, dan tidak menjamin apa?

> **Jawaban:**  
> - **Yang DIJAMIN:**  
>   Route model binding menjamin **keberadaan data (*existence*)** di dalam database. Laravel secara otomatis menjalankan query pencarian berdasarkan kunci (default: `id`) dan memastikan objek record tersebut ditemukan. Jika data tidak ada, Laravel otomatis membatalkan eksekusi dan mengembalikan respon `404 Not Found`.
> - **Yang TIDAK DIJAMIN:**  
>   Route model binding **sama sekali tidak menjamin otorisasi (*authorization*) atau izin akses pengguna**. Model binding tidak mengetahui siapa yang sedang login, peran apa yang dimiliki, dan apakah pengguna tersebut adalah pemilik sah dari data tersebut.

---

### 4. Apa yang dilakukan `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.

> **Jawaban:**  
> `Route::scopeBindings()` menginstruksikan Laravel untuk mengevaluasi relasi hirarkis antar model pada rute bersarang (*nested routes*). Laravel akan membatasi query pencarian model anak (*child*) agar **secara eksplisit terikat pada relasi model induk (*parent*)**.
>
> **Contoh Skenario yang Lolos Tanpa `scopeBindings()`:**  
> Terdapat rute bersarang:  
> `/courses/{course}/assignments/{assignment}`  
> - Mata Kuliah Matematika memiliki `id = 1`.
> - Mata Kuliah Algoritma memiliki `id = 7`.
> - Tugas *"Ujian Akhir Algoritma"* memiliki `id = 99` (anak dari Course 7).
>
> Jika pengguna sengaja atau keliru membuka URL:  
> `/courses/1/assignments/99`  
> - **Tanpa `scopeBindings()`:** Laravel meresolusi `{course}` (menemukan Course 1) dan meresolusi `{assignment}` (menemukan Assignment 99 secara terpisah). Halaman tugas 99 terbuka dengan sukses, padahal tugas tersebut bukan milik Course 1.
> - **Dengan `scopeBindings()`:** Laravel menyusun query model anak melalui relasi model induk: `$course->assignments()->findOrFail($assignmentId)`. Karena tugas 99 memiliki `course_id = 7` (bukan 1), pencarian gagal dan sistem mengembalikan `404 Not Found`.

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
> Sebagian besar tutorial di internet ditulis untuk Laravel versi 10 ke bawah yang menggunakan berkas `app/Http/Kernel.php` (pada properti `$middlewareAliases` atau `$routeMiddleware`).  
> Sejak **Laravel 11 hingga Laravel 12**, arsitektur framework disederhanakan (*Streamlined Application Structure*). Berkas `Kernel.php` telah dihapus sepenuhnya, dan seluruh konfigurasi inti routing, middleware, dan exception handling disatukan di berkas `bootstrap/app.php`.

---

### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?

> **Jawaban:**  
> Karena middleware bekerja pada **level gerbang akses rute makro (*Route-level Authorization*)**, bukan pada **level instansi data (*Record-level Authorization*)**.
>
> **Alur Terjadinya Masalah:**  
> 1. Dosen A login dengan kredensial miliknya (`role = 'dosen'`).  
> 2. Dosen A mengirim request `GET /dosen/courses/5/edit` (di mana mata kuliah ID 5 sebenarnya diampu oleh Dosen B).  
> 3. Middleware `role:dosen` mencegat request dan mengecek: *"Apakah pengguna yang login memiliki role 'dosen'?"*.  
> 4. Karena Dosen A memang seorang dosen, middleware menganggap request valid dan **meloloskannya ke controller**.  
> 5. Middleware tidak mengetahui dan tidak memeriksa kolom `courses.lecturer_id` dari record mata kuliah nomor 5 tersebut.  
>
> Celah ini merupakan **Horizontal Privilege Escalation** (eskalasi hak akses antar pengguna dengan peran yang setara). Untuk mengatasinya:  
> - **Lapis 1 (Minggu 5):** Pengecekan langsung di controller menggunakan `abort_unless($course->lecturer_id === auth()->id(), 403);`.  
> - **Lapis 2 (Minggu 7):** Pemisahan logika otorisasi ke dalam kelas **Laravel Policy** (`$this->authorize('update', $course)`).
