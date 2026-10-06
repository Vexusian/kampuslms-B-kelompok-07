## Wahyu Ramadan 
## 10241074

# Catatan Praktikum Minggu 6 – REST API & Integrasi

---

### Eksplorasi & Implementasi API (Read & Build)

**1. Perubahan pada `bootstrap/app.php` setelah menjalankan `php artisan install:api`**
- Perintah `php artisan install:api` secara otomatis menambahkan konfigurasi pendaftaran berkas rute API pada method `withRouting()` di dalam `bootstrap/app.php`:
  ```php
  ->withRouting(
      web: __DIR__.'/../routes/web.php',
      api: __DIR__.'/../routes/api.php', // ← Didaftarkan otomatis
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
  )
  ```
- Seluruh rute di dalam `routes/api.php` otomatis memiliki prefix default `/api` dan dibungkus oleh grup middleware `api` (stateless).
- Perintah tersebut juga memasang paket **Laravel Sanctum** serta menerbitkan file migrasi tabel token `personal_access_tokens`.
- Pada kode proyek kita, di `bootstrap/app.php` juga ditambahkan penanganan exception kustom (`withExceptions`) agar request yang mengarah ke `api/*` atau meminta JSON (`wantsJson()`) selalu mengembalikan response JSON murni (seperti 401, 403, 422) tanpa melakukan redirect ke web atau merender view Blade HTML.

**2. Pembuatan endpoint `GET /api/v1/courses` sederhana**
- Didaftarkan pada `routes/api.php` di dalam grup prefix `v1` yang dilindungi oleh middleware autentikasi dan rate limiter:
  ```php
  Route::middleware(['auth:sanctum,web', 'throttle:60,1'])->group(function () {
      Route::get('/courses', [CourseController::class, 'index']);
  });
  ```
- Method `index()` di `App\Http\Controllers\Api\CourseController` mengeksekusi data model `Course` dengan scoping sesuai peran pengguna:
  - **Admin:** Mengambil semua mata kuliah.
  - **Dosen:** Hanya mengambil mata kuliah yang diampunya (`where('lecturer_id', $user->id)`).
  - **Mahasiswa:** Hanya mengambil mata kuliah yang diikutinya (`$user->courses()`).
- Data dipanggil dengan eager loading relasi dosen (`with('lecturer')`), eager counting (`withCount(['materials', 'assignments', 'students'])`), lalu dipaginasi dan dikembalikan menggunakan resource:
  ```php
  return CourseResource::collection($courses);
  ```

**3. Perbandingan `CourseController` versi Web vs Versi API**
- **Apa yang SAMA:**
  - Keduanya berinteraksi dengan model Eloquent yang sama (`App\Models\Course`).
  - Menerapkan logika pembatasan data (*scoping*) yang identik berdasarkan role pengguna (Admin, Dosen, Mahasiswa).
  - Menggunakan teknik paginasi data (`paginate(15)`) serta eager loading relasi untuk mencegah pemborosan query.
  - Menerapkan pemeriksaan hak akses dan otorisasi sebelum data disajikan.
- **Apa yang BERBEDA:**
  - **Format Output:** Controller web mengembalikan Blade View HTML (`return view('courses.index', compact('courses'))`), sedangkan controller API mengembalikan JSON Resource (`return CourseResource::collection($courses)`).
  - **Mekanisme Sesi & Autentikasi:** Controller web bersifat *stateful* berbasis session cookie browser (`auth` web guard), sedangkan controller API bersifat *stateless* menggunakan HTTP Bearer Token Sanctum (`Authorization: Bearer <token>`).
  - **Proteksi CSRF:** Web wajib menggunakan token CSRF (`@csrf`) untuk melindungi request mutasi (POST, PUT, DELETE), sedangkan API bebas CSRF karena tidak bergantung pada pengiriman cookie otomatis oleh browser.
  - **Penanganan Error:** Jika terjadi kegagalan otorisasi atau validasi, controller web melakukan redirect kembali (`back()->withErrors(...)`) atau menampilkan halaman error Blade, sedangkan API merespons dengan status code HTTP standar (401, 403, 422) dan body payload JSON.
  - **Penyajian Data:** Web mengirim objek Model/Collection mentah ke template Blade, sedangkan API memfilter data menggunakan API Resource (`CourseResource`) sebagai *whitelist* atribut yang aman dikirim ke klien.

**4. Perbedaan pemanggilan endpoint API tanpa header `Accept: application/json` vs dengan header tersebut**
- **Tanpa Header `Accept: application/json`:**
  - Klien tidak menyatakan format respon yang diinginkan. Ketika dipanggil melalui browser (yang secara bawaan mengirim `Accept: text/html,...`), jika terjadi kegagalan autentikasi (401) atau validasi (422), Laravel secara default akan menganggap request berasal dari peramban web dan mencoba melakukan HTTP 302 Redirect ke halaman login atau merender view Blade HTML error.
- **Dengan Header `Accept: application/json`:**
  - Klien secara tegas meminta balasan dalam format JSON. Laravel mematikan perilaku redirect web dan secara konsisten mengembalikan respon JSON murni beserta status code HTTP yang sesuai (401 Unauthenticated, 403 Forbidden, atau 422 Unprocessable Content).
- **Penyesuaian Khusus pada Kode Kita:**
  - Pada `bootstrap/app.php`, kita telah mengonfigurasi exception handler dengan kondisi `if ($request->is('api/*') || $request->wantsJson())`. Dengan aturan ini, seluruh request pada endpoint `/api/*` dijamin selalu mengembalikan JSON (termasuk pesan error 401 dan 403), bahkan jika diakses langsung lewat address bar browser.

**5. Pencocokan hasil `php artisan route:list --path=api` dengan kontrak di spesifikasi**
- Berdasarkan **Bagian 5 Kontrak API** pada `modules/01-spesifikasi-proyek-kampuslms.md`, seluruh 15 endpoint wajib telah diimplementasikan secara lengkap dan terdaftar pada sistem:

| Method | Endpoint Kontrak Spesifikasi | Handler Controller di Kode Proyek | Kesesuaian Kontrak |
|---|---|---|---|
| POST | `/api/v1/auth/login` | `Api\AuthController@login` | Sesuai (Publik, throttle:5,1) |
| POST | `/api/v1/auth/logout` | `Api\AuthController@logout` | Sesuai (Auth token) |
| GET | `/api/v1/me` | `Api\AuthController@me` | Sesuai (Profil + role via UserResource) |
| GET | `/api/v1/courses` | `Api\CourseController@index` | Sesuai (Scoped per role + paginasi) |
| GET | `/api/v1/courses/{course}` | `Api\CourseController@show` | Sesuai (Detail + count relasi) |
| GET | `/api/v1/courses/{course}/materials` | `Api\CourseController@materials` | Sesuai (Daftar materi MK) |
| GET | `/api/v1/courses/{course}/assignments` | `Api\CourseController@assignments` | Sesuai (Daftar tugas MK, filter status) |
| POST | `/api/v1/assignments` | `Api\AssignmentController@store` | Sesuai (Dosen pemilik MK, status 201) |
| PUT/PATCH | `/api/v1/assignments/{assignment}` | `Api\AssignmentController@update` | Sesuai (Dosen pemilik) |
| DELETE | `/api/v1/assignments/{assignment}` | `Api\AssignmentController@destroy` | Sesuai (Dosen pemilik, status 204) |
| GET | `/api/v1/assignments/{assignment}/submissions` | `Api\AssignmentController@submissions` | Sesuai (Dosen melihat semua, Mahasiswa melihat miliknya) |
| POST | `/api/v1/assignments/{assignment}/submissions` | `Api\AssignmentController@storeSubmission` | Sesuai (Mahasiswa terdaftar, multipart file) |
| PUT | `/api/v1/submissions/{submission}/grade` | `Api\SubmissionController@grade` | Sesuai (Upsert: 201 baru, 200 update) |
| GET | `/api/v1/notifications` | `Api\NotificationController@index` | Sesuai (Paginasi notifikasi user) |
| POST | `/api/v1/notifications/{id}/read` | `Api\NotificationController@markAsRead` | Sesuai (Tandai notifikasi dibaca) |

*Catatan Tambahan:* Pada rute API kita juga menambahkan endpoint `GET /api/v1/submissions/{submission}` (`Api\SubmissionController@show`) agar mahasiswa pemilik tugas, dosen pengampu, atau admin dapat melihat detail satu submission secara spesifik dalam format JSON `SubmissionResource`.

---

### Keamanan & Checkpoint Praktikum

**6. Kenapa mengembalikan model mentah berbahaya?**
- Mengembalikan model Eloquent langsung (`return response()->json(User::all())`) akan membocorkan seluruh atribut kolom internal tabel basis data yang tidak terdaftar di `$hidden`.
- Kolom sensitif seperti hash password (`password`), token sesi (`remember_token`), email verification token, dan kolom internal lainnya ikut terkirim secara terbuka ke publik.
- Selain itu, perubahan skema kolom database di kemudian hari akan langsung merusak kontrak response API. Penggunaan API Resource (`UserResource`, `CourseResource`, dsb.) berfungsi sebagai *whitelist* ketat yang memastikan hanya data yang diizinkan yang dikirimkan ke klien.

**7. Perbedaan HTTP 401 Unauthorized dan 403 Forbidden**
- **HTTP 401 Unauthorized (Unauthenticated):** Klien belum membuktikan identitasnya atau token autentikasi tidak disertakan / tidak valid. Sistem belum mengetahui siapa pengirim request tersebut.
  - *Contoh riil:* Mengakses `GET /api/v1/me` tanpa header `Authorization: Bearer <token>`. Respon: `{"message": "Unauthenticated."}` dengan status HTTP 401.
- **HTTP 403 Forbidden (Unauthorized / Access Denied):** Klien sudah terautentikasi dan identitasnya dikenali oleh sistem, namun pengguna tersebut **tidak memiliki hak akses (otorisasi)** untuk melakukan aksi tersebut.
  - *Contoh riil:* Mahasiswa yang login mencoba membuat tugas baru via `POST /api/v1/assignments`. Respon: `{"message": "Anda tidak memiliki akses ke sumber daya ini."}` dengan status HTTP 403.

**8. Kenapa `routes/api.php` tidak ada secara default di Laravel 12?**
- Laravel 11 dan 12 mengusung konsep *lean application skeleton* (arsitektur aplikasi yang sangat ramping).
- Banyak aplikasi Laravel dibangun murni sebagai aplikasi web Blade tanpa memerlukan REST API sejak awal. Menghapus `routes/api.php` secara default mengurangi beban overhead pendaftaran middleware dan routing yang tidak diperlukan.
- Untuk mengaktifkannya, cukup jalankan perintah:
  ```bash
  php artisan install:api
  ```
  Perintah ini otomatis menginstal Sanctum, menerbitkan migrasi token, membuat `routes/api.php`, dan mendaftarkannya ke `bootstrap/app.php`.

**9. Fungsi `whenLoaded()` pada API Resource**
- `whenLoaded('relationName')` memastikan data relasi Eloquent hanya dimasukkan ke dalam serialisasi JSON jika relasi tersebut telah di-eager load secara eksplisit pada controller (misal: `Course::with('lecturer')`).
- Tanpa `whenLoaded()`, pemanggilan `$this->lecturer` di dalam resource akan memicu *lazy loading* otomatis di background untuk setiap baris data. Jika terdapat 20 mata kuliah, akan dijalankan 1 query utama ditambah 20 query terpisah ke tabel users, yang mengakibatkan **masalah query N+1** dan membebani server basis data.

**10. Kenapa pesan gagal login tidak boleh membedakan email salah dan password salah?**
- Membedakan pesan kesalahan login (misalnya: *"Email tidak terdaftar"* vs *"Password salah"*) membuka celah kerentanan **User Enumeration (Pencacahan Pengguna)**.
- Penyerang dapat menjalankan skrip otomatis dengan daftar email untuk memetakan akun mana saja yang terdaftar di sistem LMS. Setelah menemukan target valid, penyerang tinggal memfokuskan serangan brute-force atau credential stuffing.
- Oleh karena itu, pesan kesalahan wajib disatukan menjadi pesan generik: `"Email atau kata sandi salah."` dengan status kode HTTP 422.

**11. Kenapa endpoint login wajib di-throttle?**
- Endpoint login adalah rute publik yang tidak memerlukan token. Tanpa *rate limiting* (throttle), penyerang dapat menjalankan *brute-force* atau *dictionary attack* ribuan kali per menit untuk menebak kata sandi pengguna serta membebani CPU server.
- Pada kode proyek, kita memasang `throttle:5,1` (maksimal 5 request per 1 menit per IP).
- Nilai ini memberikan toleransi wajar jika pengguna salah mengetik kata sandi 1–2 kali, namun efektif menghentikan bot otomatis karena request ke-6 akan langsung ditolak dengan kode status **HTTP 429 Too Many Requests**.
