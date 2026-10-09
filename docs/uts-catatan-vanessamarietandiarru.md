# CATATAN ASESMEN TENGAH SEMESTER (UTS) — KAMPUSLMS

- **Mahasiswa:** Vanessa Marie Tandiarru (NIM: 10251118)
- **Kelas / Kelompok:** Pemrograman Web (Kelas B - Kelompok 07)
- **Mata Kuliah:** SI2514024 Pemrograman Web
- **Framework:** Laravel 12 (PHP 8.2+)
- **Dokumen Acuan:** [04-modul-minggu-08-uts.md](file:///c:/Users/vanes/laragon/www/kampuslms/modules/04-modul-minggu-08-uts.md) & [01-spesifikasi-proyek-kampuslms.md](file:///c:/Users/vanes/laragon/www/kampuslms/modules/01-spesifikasi-proyek-kampuslms.md)

---

## DAFTAR ISI

1. [Bagian 1 — Skenario Demo Alur Tiga Peran (7 Menit)](#bagian-1--skenario-demo-alur-tiga-peran-7-menit)
2. [Bagian 2 — Code Walkthrough & Bank Pertanyaan UTS (8 Menit)](#bagian-2--code-walkthrough--bank-pertanyaan-uts-8-menit)
3. [Bagian 3 — Audit Hak Akses (Tabel 3 Spesifikasi) & Uji IDOR](#bagian-3--audit-hak-akses-tabel-3-spesifikasi--uji-idor)
4. [Bagian 4 — Checklist Rubrik Penilaian (Nilai 4 Sangat Baik)](#bagian-4--checklist-rubrik-penilaian-nilai-4-sangat-baik)

---

## BAGIAN 1 — SKENARIO DEMO ALUR TIGA PERAN (7 Menit)

### Persiapan Demo
Reset database ke status bersih:
```bash
php artisan migrate:fresh --seed
```

Kredensial Seeder ([DatabaseSeeder.php](file:///c:/Users/vanes/laragon/www/kampuslms/database/seeders/DatabaseSeeder.php)):
- **Admin:** `admin@kampuslms.test` / `password`
- **Dosen:** `dosen@kampuslms.test` / `password`
- **Mahasiswa:** `mahasiswa@kampuslms.test` / `password`

---

### Langkah Demo Peran

#### 1. Peran Admin (2 Menit)
1. **Login:** Masuk di `/login` dengan akun Admin.
2. **Kelola User (Opsional):** Buka `/admin/users` untuk melihat daftar akun sistem (fitur kelola pengguna admin).
3. **Buat Mata Kuliah:** Buka `/courses/create`, isi kode (`IF301`), nama (`Rekayasa Perangkat Lunak Terapan`), SKS (`3`), pilih Dosen Pengampu (`Dosen Demo`), status (`active`), lalu simpan.
4. **Enrollment Mahasiswa:** Masuk ke detail `/courses/{id}`, pilih `Mahasiswa Demo` pada panel peserta kelas, klik **Daftarkan ke Kelas**.
5. **CRUD Materi & Tugas oleh Admin:** Sesuai Tabel 3 Spesifikasi, Admin berhak membuat materi/tugas baru langsung dari tombol di halaman detail kelas (`/dosen/courses/{id}/materials/create` dan `/dosen/courses/{id}/assignments/create`).
6. **Logout.**

#### 2. Peran Dosen (2.5 Menit)
1. **Login:** Masuk dengan akun Dosen Demo.
2. **Verifikasi Scoping:** Pada `/courses`, Dosen hanya melihat mata kuliah miliknya sendiri.
3. **Unggah Materi:** Masuk ke kelas `IF301` → klik **Tambah Materi** (`/dosen/courses/{id}/materials/create`), isi judul & uraian materi, simpan.
4. **Buat Tugas:** Klik **Tambah Tugas** (`/dosen/courses/{id}/assignments/create`), isi judul, instruksi, tenggat waktu (*due date*), simpan.
5. **Koreksi & Nilai Tugas (Grading):** Buka tugas → klik **Lihat Pengumpulan** (`/dosen/assignments/{id}/submissions`), beri nilai dan umpan balik pada jawaban mahasiswa.
6. **Logout.**

#### 3. Peran Mahasiswa (2.5 Menit)
1. **Login:** Masuk dengan akun Mahasiswa Demo.
2. **Verifikasi Scoping:** Pada `/courses`, Mahasiswa hanya melihat kelas tempat ia terdaftar (`IF301`).
3. **Akses Materi:** Buka kelas `IF301` → buka materi yang dibuat Dosen.
4. **Kumpulkan Tugas:** Buka tugas `IF301` → isi form pengumpulan / jawaban → klik **Kumpulkan Tugas**.
5. **Lihat Nilai:** Mahasiswa dapat melihat nilai yang diberikan Dosen pada tugasnya.
6. **Uji Otorisasi Spontan:** Akses manual `/courses/{id}/edit` atau rute dosen di address bar browser → server menolak dengan **HTTP 403 Forbidden**.

---

## BAGIAN 2 — CODE WALKTHROUGH & BANK PERTANYAAN UTS (8 Menit)

---

### Q1: Tunjukkan migrasi yang Anda tulis. Jelaskan setiap constraint di dalamnya.

**Berkas:** [database/migrations/2026_09_12_000002_create_courses_table.php](file:///c:/Users/vanes/laragon/www/kampuslms/database/migrations/2026_09_12_000002_create_courses_table.php)

```php
Schema::create('courses', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->string('name');
    $table->text('description')->nullable();
    $table->unsignedTinyInteger('sks');
    $table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
    $table->timestamps();
});
```

**Penjelasan Constraint:**
- `id()`: Primary key `BIGINT UNSIGNED AUTO_INCREMENT`.
- `unique()`: Kode mata kuliah unik di database untuk mencegah duplikasi data akademik.
- `nullable()`: Kolom deskripsi bersifat opsional.
- `unsignedTinyInteger('sks')`: Tipe 1-byte (0–255), hemat memori dan sesuai rentang SKS (1–6).
- `constrained('users')->restrictOnDelete()`: FK ke `users.id`. Mencegah penghapusan akun dosen jika masih tercatat mengampu mata kuliah aktif.
- `enum('status', ...)->default('draft')`: Membatasi status hanya pada nilai yang valid; *default* `draft` agar kelas tidak aktif sebelum siap.

---

### Q2: Kenapa `course_user` punya unique composite? Peragakan tanpa itu.

**Berkas:** [database/migrations/2026_09_12_000003_create_course_user_table.php](file:///c:/Users/vanes/laragon/www/kampuslms/database/migrations/2026_09_12_000003_create_course_user_table.php#L21)

```php
$table->unique(['course_id', 'user_id']);
```

- **Alasan:** Menjamin satu mahasiswa hanya terdaftar tepat **satu kali** di mata kuliah yang sama.
- **Dampak Tanpa Constraint:**
  1. *Double-click* atau *race condition* menyebabkan baris duplikat.
  2. Perhitungan jumlah mahasiswa (`COUNT`) dan rekap nilai menjadi salah.
- **Peragaan Tanpa Constraint:**
  Panggil `$course->students()->attach($studentId)` dua kali berturut-turut di Tinker:
  - *Tanpa unique:* Data masuk 2 baris (nama mahasiswa muncul ganda di tabel peserta).
  - *Dengan unique:* Database menolak dengan `QueryException: 1062 Duplicate entry`.

---

### Q3: Apa itu mass assignment? Tunjukkan apa yang mencegahnya di kode Anda.

- **Definisi:** Kerentanan di mana pengguna menyisipkan parameter berbahaya pada HTTP request (contoh: `role=admin`) dan sistem langsung memasukkannya ke database lewat `Model::create($request->all())`.
- **Pencegahan di Kode:**
  1. **Whitelist Model (`$fillable`):** Pada [app/Models/User.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Models/User.php#L24-L29), kolom `role` **tidak dicantumkan**.
  2. **Form Request (`$request->validated()`):** Pada [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L94), pemanggilan `Course::create($request->validated())` hanya mengambil field yang lolos validasi `rules()`.

---

### Q4: Kenapa `role` tidak ada di `$fillable`? Di mana ia diisi?

- **Alasan:** Mencegah *Privilege Escalation* (penyerang mengubah dirinya menjadi admin dengan menyelipkan field `role=admin`).
- **Lokasi Pengisian Terkontrol:**
  1. **Di Controller:** Pada [app/Http/Controllers/UserController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/UserController.php#L58):
     ```php
     $user = new User($validated);
     $user->role = 'mahasiswa'; // Penetapan eksplisit oleh server
     $user->save();
     ```
  2. **Di Seeder / Factory:** Ditetapkan terprogram lewat state factory (`User::factory()->admin()->create()`).

---

### Q5: Tunjukkan satu Policy yang Anda tulis. Jelaskan tiap barisnya.

**Berkas:** [app/Policies/CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L25-L58)

```php
public function view(User $user, Course $course): bool
{
    if ($user->role === 'admin') {
        return true; // 1. Admin berhak melihat seluruh mata kuliah
    }

    if ($user->role === 'dosen') {
        return $course->lecturer_id === $user->id; // 2. Dosen hanya melihat MK miliknya
    }

    if ($user->role === 'mahasiswa') {
        return $course->students()->where('users.id', $user->id)->exists(); // 3. Mahasiswa terdaftar
    }

    return false; // 4. Default deny untuk role lain
}

public function update(User $user, Course $course): bool
{
    return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
}
```

---

### Q6: Kenapa `@can` di Blade tidak cukup? Peragakan.

- **Alasan:** `@can` hanya mengontrol antarmuka (UI). `@can` hanya menyembunyikan tombol di browser pengguna, tetapi **tidak melindungi endpoint server**.
- **Peragaan Serangan:**
  Dosen B mengirim HTTP PUT langsung via curl/Postman ke mata kuliah Dosen A:
  ```bash
  curl -X POST http://localhost:8000/courses/1 -d "_method=PUT" -d "name=Hack" -b cookies.txt
  ```
- **Pertahanan:** Server memanggil `Gate::authorize('update', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L139), sehingga request langsung ditolak dengan status **HTTP 403 Forbidden**.

---

### Q7: Apa beda 401 dan 403? Tunjukkan masing-masing satu di API Anda.

- **HTTP 401 Unauthorized (Unauthenticated):** Identitas pengguna tidak diketahui / belum login (token hilang atau kadaluarsa).
  - *Contoh API:* Request `GET /api/v1/me` tanpa bearer token → `{"message": "Unauthenticated."}` (HTTP 401).
- **HTTP 403 Forbidden:** Identitas sah, tetapi pengguna **tidak berhak** mengakses resource tersebut.
  - *Contoh API:* Mahasiswa memanggil `POST /api/v1/assignments` → `{"message": "Anda tidak memiliki akses..."}` (HTTP 403).

---

### Q8: Kenapa `$request->validated()` lebih aman daripada `$request->all()`?

- `$request->validated()` menggunakan prinsip **whitelisting**: hanya mengembalikan atribut yang terdaftar dan lolos validasi pada Form Request.
- `$request->all()` mengembalikan semua data input mentah dari client (termasuk token CSRF, field spoofing method, dan parameter berbahaya yang tidak divalidasi).

---

### Q9: Kenapa filter pencarian ada di query string, bukan session?

**Implementasi:** [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L70) (`paginate(15)->withQueryString()`).

1. **Shareable & Bookmarkable:** URL pencarian (`/courses?q=web&status=active&page=2`) dapat di-bookmark dan dibagikan ke orang lain dengan hasil yang identik.
2. **Navigasi Riwayat Browser:** Tombol Back dan Forward bekerja normal tanpa merusak status filter.
3. **Multi-Tab:** Membuka filter berbeda pada tab berbeda tidak saling menimpa.
4. **Stateless HTTP GET:** Sesuai standar arsitektur RESTful.

---

### Q10: Kenapa `session()->regenerate()` dipanggil setelah login?

**Berkas:** [app/Http/Controllers/AuthController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AuthController.php#L48)

- **Fungsi:** Mencegah serangan **Session Fixation**.
- **Mekanisme:** Penyerang yang menyematkan ID sesi kosong sebelum korban login tidak akan dapat membajak akun korban, karena Laravel menghancurkan ID sesi lama dan menerbitkan ID sesi baru yang acak segera setelah kredensial terverifikasi.

---

### Q11: Di mana middleware didaftarkan pada Laravel 12? Kenapa beda dari tutorial?

- **Lokasi Laravel 12:** Didaftarkan secara deklaratif di [bootstrap/app.php](file:///c:/Users/vanes/laragon/www/kampuslms/bootstrap/app.php#L14-L26):
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]);
  })
  ```
- **Alasan Beda:** Pada Laravel versi lama (≤ 10), middleware didaftarkan di `app/Http/Kernel.php`. Di Laravel 11 & 12, file `Kernel.php` dihapus untuk mengadopsi struktur aplikasi yang lebih ringkas (*slim application skeleton*).

---

### Q12: Tunjukkan bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

1. **Rule Unik di [UpdateCourseRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/UpdateCourseRequest.php#L26):**
   - *Saran AI Awal:* `'code' => 'required|unique:courses,code'` (Error saat menyimpan tanpa mengubah kode MK karena bentrok dengan ID sendiri).
   - *Perubahan Mandiri:* Menggunakan `Rule::unique('courses', 'code')->ignore($courseId)` agar record yang sedang diedit dikecualikan dari pengecekan unik.
2. **Query Scoping di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L39-L44):**
   - *Saran AI Awal:* Mengambil semua data dengan `Course::all()` lalu disaring menggunakan PHP Collection `filter()`.
   - *Perubahan Mandiri:* Menggantinya menjadi query scoping di level database SQL (`match ($user->role)`) untuk menghemat RAM dan mencegah kebocoran data.
3. **Penyelarasan Otorisasi Laravel 12:**
   - *Saran AI Awal:* Memanggil `$this->authorize()` di Controller.
   - *Perubahan Mandiri:* Diubah menjadi `Gate::authorize()` karena method bawaan tersebut ditiadakan di Controller default Laravel 12.

---

## BAGIAN 3 — AUDIT HAK AKSES (TABEL 3 SPESIFIKASI) & UJI IDOR

### 1. Matriks Hak Akses Terkini di Repositori

Berdasarkan [modules/01-spesifikasi-proyek-kampuslms.md](file:///c:/Users/vanes/laragon/www/kampuslms/modules/01-spesifikasi-proyek-kampuslms.md) (Tabel 3):

| Aksi Sesuai Spesifikasi | Admin | Dosen | Mahasiswa | Status & Proteksi di Kode |
|---|:---:|:---:|:---:|---|
| **CRUD pengguna & role** | ✔ | — | — | Dilindungi `role:admin` pada `/admin/users` ([routes/web.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/web.php#L63)). |
| **CRUD mata kuliah** | ✔ | — | — | [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php): `create` & `delete` khusus Admin. |
| **Kelola enrollment** | ✔ | ✔ (MK sendiri) | — | [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php): `enroll` mengizinkan Admin & Dosen pengampu MK. |
| **CRUD materi** | ✔ | ✔ (MK sendiri) | lihat/unduh | [MaterialPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/MaterialPolicy.php): Admin & Dosen pengampu. Mahasiswa hanya melihat. |
| **CRUD tugas** | ✔ | ✔ (MK sendiri) | lihat | [AssignmentPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/AssignmentPolicy.php): Admin & Dosen pengampu. Mahasiswa hanya melihat. |
| **Mengumpulkan tugas** | — | — | ✔ (MK diikuti) | [SubmissionPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/SubmissionPolicy.php): Khusus Mahasiswa yang terdaftar. |
| **Memberi nilai** | — | ✔ (MK sendiri) | — | [GradePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/GradePolicy.php): `create/update/delete` HANYA Dosen pengampu (Admin ditolak). |
| **Melihat nilai orang lain** | ✔ | ✔ (MK sendiri) | ✘ | [GradePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/GradePolicy.php): `view` mengizinkan Admin & Dosen pengampu. |

**Penyelarasan Kode Terkini:**
- **Rute Web Materi & Tugas:** Dibuka untuk `role:admin,dosen` di [routes/web.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/web.php#L69).
- **Integritas DB Assignment:** [AssignmentController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AssignmentController.php) otomatis mengisi `created_by = auth()->id()` dan memetakan field `instructions`.
- **Aturan Grading:** [GradePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/GradePolicy.php) dan API submission memastikan Admin tidak dapat memberi nilai (khusus Dosen pengampu MK sendiri).

---

### 2. Uji Keamanan IDOR: Titik Rawan #10 (`GET /submissions/{id}`)

- **Skenario Risiko:** Mahasiswa A mencoba mengintip jawaban / nilai milik Mahasiswa B dengan mengubah ID di URL.
- **Pengujian:**
  ```bash
  curl -i http://localhost:8000/submissions/2 -b "laravel_session=<cookie_mahasiswa_a>"
  ```
- **Pertahanan di Kode:** [SubmissionPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/SubmissionPolicy.php#L32-L48) memverifikasi `$submission->user_id === $user->id` atau kepemilikan pengampu dosen.
- **Hasil Pengujian:** Permintaan ditolak dengan status **HTTP 403 Forbidden**.
- **Panduan Lengkap cURL 12 Titik Rawan:** Rincian seluruh command cURL, penjelasan baris demi baris, dan letak kode penahannya tersedia di [modules/05-panduan-uji-keamanan-curl.md](file:///c:/Users/vanes/laragon/www/kampuslms/modules/05-panduan-uji-keamanan-curl.md).

---

## BAGIAN 4 — CHECKLIST RUBRIK PENILAIAN (NILAI 4 SANGAT BAIK)

- [x] **Fungsionalitas Demo (25%):** `migrate:fresh --seed` berjalan mulus; seluruh alur Admin, Dosen, dan Mahasiswa dapat diperagakan tanpa hambatan.
- [x] **Penerapan Konsep (25%):** Skema database terstruktur rapi dengan constraint SQL lengkap; otorisasi konsisten di tingkat Form Request, Gate/Policy, dan Route Middleware; scoping data dilakukan di level database SQL.
- [x] **Pemahaman Individu (30%):** Mampu menerangkan setiap baris kode, arsitektur keputusan, hingga modifikasi kode berbantuan AI.
- [x] **Keamanan (20%):** Uji tembus IDOR tertolak (HTTP 403); rate limiting login 5x/menit aktif di Web & API; seluruh 39 skenario otomatis lulus 100% (**39 passed** di `php artisan test`).
