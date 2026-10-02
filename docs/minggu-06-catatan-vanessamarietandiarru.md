# Catatan Praktikum Minggu 6 — REST API dan Integrasi

- **Nama:** Vanessa Marie Tandiarru
- **NIM:** 10251118
- **Kelas:** Pemrograman Web (Kelas B - Kelompok 07)

---

## 6.3 Read → Break → Fix → Build

### READ — Bandingkan Dua Jalur (Web vs API)

#### 1. Perubahan `bootstrap/app.php` setelah `php artisan install:api`
Perintah `php artisan install:api` mendaftarkan berkas rute API secara otomatis ke dalam konfigurasi aplikasi di [bootstrap/app.php](file:///c:/Users/vanes/laragon/www/kampuslms/bootstrap/app.php):
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // ← Ditambahkan otomatis
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```
Semua rute di dalam `routes/api.php` secara default diberi prefix `/api` dan middleware grup `api` (stateless, tanpa session cookie dan proteksi CSRF).

#### 2. Perbandingan `CourseController` Versi Web vs API
| Aspek | Jalur Web (`CourseController.php`) | Jalur API (`Api/CourseController.php`) |
|---|---|---|
| **Keluaran (Output)** | Mengembalikan HTML view Blade (`view('courses.index', ...)`) | Mengembalikan JSON Resource (`CourseResource::collection(...)`) |
| **Identitas & Sesi** | Stateful: Mengandalkan session cookie di browser | Stateless: Mengandalkan Bearer Token Sanctum per request |
| **Proteksi CSRF** | Wajib menggunakan token CSRF (`@csrf` / middleware CSRF) | Bebas CSRF karena request tidak menggunakan autentikasi cookie otomatis |
| **Penanganan Error** | Redirect kembali (`back()->withErrors(...)`) atau halaman Blade error | Status code HTTP murni (401, 403, 422, 404, 500) dengan payload JSON |
| **Penyajian Data** | Data mentah/Paginator dikirim langsung ke view | Data disaring melalui Resource sebagai *whitelist* kolom yang diizinkan |

#### 3. Perbedaan Request Tanpa vs Dengan Header `Accept: application/json`
- **Tanpa `Accept: application/json`:** Jika terjadi kegagalan validasi atau autentikasi, Laravel menganggap request berasal dari peramban biasa sehingga mencoba melakukan redirect HTTP 302 ke halaman login atau mengembalikan respons HTML.
- **Dengan `Accept: application/json`:** Laravel memaksakan respons JSON terstruktur dengan kode status HTTP yang semestinya (401 Unauthenticated atau 422 Unprocessable Content).

#### 4. Hasil Verifikasi Route API (`php artisan route:list --path=api`)
Semua 15 endpoint kontrak Bagian 5 spesifikasi proyek telah terdaftar:
```text
POST       api/v1/assignments ..................................... Api\AssignmentController@store
PUT|PATCH  api/v1/assignments/{assignment} ....................... Api\AssignmentController@update
DELETE     api/v1/assignments/{assignment} ...................... Api\AssignmentController@destroy
GET|HEAD   api/v1/assignments/{assignment}/submissions .......... Api\AssignmentController@submissions
POST       api/v1/assignments/{assignment}/submissions .......... Api\AssignmentController@storeSubmission
POST       api/v1/auth/login .................................... Api\AuthController@login
POST       api/v1/auth/logout ................................... Api\AuthController@logout
GET|HEAD   api/v1/courses ....................................... Api\CourseController@index
GET|HEAD   api/v1/courses/{course} .............................. Api\CourseController@show
GET|HEAD   api/v1/courses/{course}/assignments .................. Api\CourseController@assignments
GET|HEAD   api/v1/courses/{course}/materials .................... Api\CourseController@materials
GET|HEAD   api/v1/me ............................................ Api\AuthController@me
GET|HEAD   api/v1/notifications ................................. Api\NotificationController@index
POST       api/v1/notifications/{id}/read ....................... Api\NotificationController@markAsRead
PUT        api/v1/submissions/{submission}/grade ................ Api\SubmissionController@grade
```

---

### BREAK — Observasi Tujuh Kerusakan

| # | Percobaan | Hasil Pengamatan Riil | Analisis Keamanan |
|---|---|---|---|
| **1** | Kembalikan `response()->json(User::all())` | Seluruh data pengguna tampil, mencakup kolom `password` (berisi Bcrypt hash), `remember_token`, dan `email_verified_at`. | **Bocor Data Kritis.** Hash password yang bocor memudahkan penyerang melakukan serangan offline *dictionary attack* / *rainbow table*. |
| **2** | Hapus middleware `auth:sanctum` dari route grup | Endpoint `/api/v1/courses` dan `/api/v1/me` bisa diakses siapa pun tanpa header Authorization. | **Broken Authentication.** Data privat perkuliahan dan submission menjadi konsumsi publik. |
| **3** | Request ke endpoint terlindungi dengan token yang telah dihapus di tabel `personal_access_tokens` | Server mengembalikan status **HTTP 401 Unauthorized** dengan pesan `{"message": "Unauthenticated."}`. | Sanctum memvalidasi hash token ke basis data pada setiap request. Begitu record token dihapus, token langsung invalid seketika. |
| **4** | Login sebagai mahasiswa, kirim `POST /api/v1/assignments` | Server mengembalikan status **HTTP 403 Forbidden** dengan pesan `{"message": "Anda tidak memiliki akses ke sumber daya ini."}`. | Membedakan 401 dan 403: Klien sudah terautentikasi (bukan 401), namun perannya sebagai mahasiswa dilarang membuat tugas (403). |
| **5** | Hapus eager loading (`with('lecturer')`) pada `GET /api/v1/courses` | Muncul query `SELECT * FROM users WHERE id = ?` terpisah untuk setiap item course (N+1 query problem). | Pemborosan sumber daya server basis data. Dengan 50 courses, database dieksekusi 51 kali untuk 1 request. |
| **6** | Hapus `throttle:5,1` dari endpoint login, kirim 50 request login berturut-turut | Semua 50 request diproses tanpa jeda oleh server. | Membuka celah brute-force dan credential stuffing secara bebas tanpa mitigasi. |
| **7** | Pesan error login dibuat spesifik: "Email tidak terdaftar" vs "Password salah" | Penguji dapat menebak email mana saja yang terdaftar di basis data LMS hanya dari perbedaan pesan error. | **User Enumeration Vulnerability.** Penyerang dapat memetakan target akun sebelum melancarkan serangan. |

---

### FIX — Perbaikan Repo Cacat

Delapan masalah utama diperbaiki dengan rincian solusi berikut:
1. **Model Mentah:** Seluruh endpoint dibungkus menggunakan kelas API Resource (`UserResource`, `CourseResource`, `MaterialResource`, `AssignmentResource`, `SubmissionResource`, `GradeResource`).
2. **Endpoint Terbuka:** Mengelompokkan seluruh rute sensitif ke dalam middleware group `auth:sanctum`.
3. **Status Code Store:** Method `store` pada pembuatan tugas dan pengumpulan submission diatur mengembalikan status **201 Created** (`setStatusCode(201)`).
4. **Status Code Destroy:** Method `destroy` pada penghapusan tugas diatur mengembalikan status **204 No Content** (`response()->noContent()`).
5. **Kekeliruan 403 vs 401:** Memisahkan penjagaan autentikasi (Sanctum) dengan pengecekan peran/kepemilikan (`abort(403)`).
6. **Rate Limiting Login:** Menambahkan middleware `throttle:5,1` pada rute `POST /api/v1/auth/login`.
7. **User Enumeration:** Menyeragamkan pesan kegagalan login menjadi satu pesan tunggal: `"Email atau kata sandi salah."`.
8. **N+1 Query:** Menerapkan relasi eager loading `with()` pada query koleksi dan `whenLoaded()` pada API Resource.

---

### BUILD — Implementasi API KampusLMS

1. **Pemasangan Sanctum:** `composer require laravel/sanctum` dan migrasi tabel token `personal_access_tokens` berhasil dipasang. Trait `HasApiTokens` aktif di model [User.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Models/User.php).
2. **API Resources Lengkap:** 
   - [UserResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/UserResource.php)
   - [CourseResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/CourseResource.php)
   - [MaterialResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/MaterialResource.php)
   - [AssignmentResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/AssignmentResource.php)
   - [SubmissionResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/SubmissionResource.php)
   - [GradeResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/GradeResource.php)
   - [NotificationResource.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Resources/NotificationResource.php)
3. **Controller API:** Dibangun di dalam namespace `App\Http\Controllers\Api` mencakup [AuthController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/Api/AuthController.php), [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/Api/CourseController.php), [AssignmentController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/Api/AssignmentController.php), [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/Api/SubmissionController.php), dan [NotificationController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/Api/NotificationController.php).
4. **Rate Limiting:** Menggunakan middleware bawaan Laravel:
   - `throttle:5,1` pada login.
   - `throttle:60,1` pada rute umum.
5. **Dokumentasi API:** Tersedia lengkap di berkas [docs/api.md](file:///c:/Users/vanes/laragon/www/kampuslms/docs/api.md) dengan contoh `curl`, struktur parameter, dan contoh respons.
6. **Skrip Uji Otorisasi:** Tersedia pada berkas [scripts/test-api.sh](file:///c:/Users/vanes/laragon/www/kampuslms/scripts/test-api.sh) yang menguji 4 kondisi akses secara otomatis.
7. **Pilihan Jalur Frontend Kelompok (Minggu 7–16):** Kelompok 07 memilih jalur **(b) Blade + Livewire 3** untuk menyajikan antarmuka dinamis dan reaktif tanpa kerumitan build tooling SPA terpisah.

---

## 6.4 Checkpoint Minggu 6 (Panduan Uji Dosen)

### 1. Kenapa mengembalikan model mentah berbahaya? Peragakan kebocorannya.

**Jawaban:**
Mengembalikan model Eloquent mentah (`return response()->json(User::all())`) membocorkan seluruh atribut kolom internal tabel basis data yang tidak masuk ke dalam properti `$hidden`. Kolom sensitif seperti hash password (`password`), token sesi (`remember_token`), token verifikasi email, maupun kolom internal baru otomatis ikut terkirim ke klien. Selain itu, perubahan skema database di masa depan akan langsung merusak kontrak response API tanpa disengaja.

**Peragaan:**
- **Tanpa Resource (Model Mentah):**
  ```json
  [
    {
      "id": 1,
      "name": "Dosen Demo",
      "email": "dosen@kampuslms.test",
      "password": "$2y$12$eX8zW1hL...", // ⚠️ HASH PASSWORD BOCOR
      "remember_token": "a1b2c3d4...",   // ⚠️ TOKEN BOCOR
      "created_at": "2026-09-12T00:00:00.000000Z"
    }
  ]
  ```
- **Dengan API Resource (`UserResource` sebagai Whitelist):**
  ```json
  [
    {
      "id": 1,
      "name": "Dosen Demo",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198001012005011001",
      "created_at": "2026-09-12T00:00:00.000000Z"
    }
  ]
  ```
Hanya data yang didefinisikan secara eksplisit di method `toArray()` yang dikirim ke klien.

---

### 2. Apa beda 401 dan 403? Tunjukkan di API Anda satu contoh masing-masing.

**Jawaban:**
- **HTTP 401 Unauthorized (Unauthenticated):** Klien belum membuktikan identitasnya. Request tidak menyertakan token autentikasi atau token yang diberikan tidak valid / kadaluarsa. Sistem tidak mengenali siapa pengguna ini.
- **HTTP 403 Forbidden (Unauthorized / Hak Akses Ditolak):** Klien sudah terautentikasi (sistem mengenali identitas pengguna), namun pengguna tersebut **tidak memiliki hak izin (otorisasi)** untuk mengakses atau memanipulasi sumber daya yang dituju.

**Contoh Riil di API KampusLMS:**
- **Contoh 401:**
  Memanggil `GET /api/v1/me` tanpa header `Authorization: Bearer <token>`.
  Response:
  ```json
  { "message": "Unauthenticated." } // Status HTTP: 401
  ```
- **Contoh 403:**
  Mahasiswa login dan memiliki token valid, lalu memanggil `POST /api/v1/assignments` (membuat tugas yang merupakan hak dosen).
  Response:
  ```json
  { "message": "Anda tidak memiliki akses ke sumber daya ini." } // Status HTTP: 403
  ```

---

### 3. Kenapa `routes/api.php` tidak ada secara default di Laravel 12? Bagaimana mengaktifkannya?

**Jawaban:**
- **Alasan ketiadaan default:**
  Mulai Laravel 11 dan 12, arsitektur aplikasi dirancang seringan mungkin (*lean application skeleton*). Banyak aplikasi Laravel hanya berupa aplikasi web tradisional berbasis Blade atau aplikasi monolitik yang tidak memerlukan API Sanctum sejak hari pertama. Menghapus berkas `routes/api.php` secara bawaan mengurangi *overhead* pendaftaran rute dan middleware yang tidak digunakan.
- **Cara mengaktifkannya:**
  Jalankan perintah Artisan:
  ```bash
  php artisan install:api
  ```
  Perintah ini secara otomatis:
  1. Memasang paket `laravel/sanctum`.
  2. Menerbitkan berkas migrasi `create_personal_access_tokens_table`.
  3. Membuat berkas [routes/api.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/api.php).
  4. Mendaftarkan konfigurasi `api: __DIR__.'/../routes/api.php'` pada [bootstrap/app.php](file:///c:/Users/vanes/laragon/www/kampuslms/bootstrap/app.php).

---

### 4. Apa fungsi `whenLoaded()`? Apa yang terjadi tanpanya?

**Jawaban:**
- **Fungsi:**
  Method `whenLoaded('relationName')` pada API Resource berfungsi untuk memastikan bahwa data relasi Eloquent hanya disertakan ke dalam serialisasi JSON jika relasi tersebut **telah di-eager load** secara sadar pada query controller (misalnya melalui `Course::with('lecturer')`).
- **Apa yang terjadi tanpanya:**
  Jika kita langsung menulis relasi seperti `'lecturer' => new UserResource($this->lecturer)` tanpa `whenLoaded()`, Eloquent akan mengeksekusi query relasi basis data secara otomatis (*lazy loading*) untuk setiap baris data yang diproses. Jika terdapat 20 mata kuliah, sistem akan mengeksekusi 1 query untuk mengambil mata kuliah + 20 query terpisah untuk mengambil data dosen. Ini adalah **masalah query N+1** yang menyebabkan beban server melonjak drastis saat traffic meningkat.

---

### 5. Kenapa pesan gagal login tidak boleh membedakan email salah dan password salah?

**Jawaban:**
Untuk mencegah kerentanan keamanan **User Enumeration (Pencacahan Pengguna)**. 
Jika API mengembalikan pesan yang berbeda, misalnya:
- *"Email tidak terdaftar"* saat email salah.
- *"Password salah"* saat email benar namun password salah.

Penyerang dapat menjalankan skrip otomatis dengan daftar jutaan kombinasi email untuk memetakan siapa saja pengguna nyata yang terdaftar di sistem. Setelah menemukan email yang valid, penyerang tinggal memfokuskan serangan brute-force atau *credential stuffing* terhadap akun tersebut. 
Oleh karena itu, pesan kesalahan wajib digabung menjadi pesan generik:
`"Email atau kata sandi salah."` dengan kode HTTP 422.

---

### 6. Kenapa endpoint login wajib di-throttle? Berapa nilai yang Anda pakai dan mengapa?

**Jawaban:**
- **Alasan Wajib di-Throttle:**
  Endpoint login adalah rute publik tanpa perlindungan token. Tanpa *rate limiting* (throttle), penyerang dapat menjalankan program otomatis untuk menguji ratusan hingga ribuan kombinasi kata sandi per menit (*brute-force attack* atau *dictionary attack*), yang dapat membobol akun pengguna serta menghabiskan utilisasi CPU server basis data.
- **Nilai yang Digunakan:**
  `throttle:5,1` (maksimal 5 request per 1 menit per alamat IP).
- **Alasan Pemilihan Nilai:**
  Batas 5 kali percobaan dalam 1 menit memberikan ruang toleransi yang wajar bagi pengguna asli yang salah mengetikkan kredensialnya 1–2 kali, namun secara efektif mematikan upaya serangan skrip otomatis karena penyerang akan langsung diblokir oleh Laravel dengan kode **HTTP 429 Too Many Requests** setelah percobaan ke-5.
