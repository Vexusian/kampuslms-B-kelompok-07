# DOKUMENTASI KEAMANAN & DAFTAR TITIK RAWAN IDOR — KAMPUSLMS

Dokumen ini merupakan deliverable keamanan resmi untuk **Milestone M2 (Tugas 2 — Minggu 7)** dan **Asesmen Tengah Semester (UTS — Minggu 8)**.

---

## 1. Tabel Titik Rawan IDOR & Penutupannya

| No | Endpoint & Metode | Siapa yang Berhak | Siapa yang Dicegah | Risiko Keamanan (Dampak IDOR) | Cara Menutup Celah (Policy / Query Scope) | Bukti Pengujian |
|:--:|-------------------|-------------------|--------------------|--------------------------------|-------------------------------------------|-----------------|
| **1** | `GET /courses` | Semua peran yang login (Admin, Dosen, Mahasiswa) | Publik/Guest (tanpa login); Peran melihat data peran lain | Mahasiswa dapat melihat mata kuliah yang tidak diikutinya; Dosen melihat mata kuliah dosen lain | **Query Scoping** di `CourseController::index`: `match ($user->role)` mengembalikan hanya `$user->courses()` untuk mahasiswa dan `$user->taughtCourses()` untuk dosen. | `curl -b cookie_mhs http://localhost:8000/courses` (hanya mata kuliah terdaftar yang muncul). |
| **2** | `GET /courses/{course}` | Admin, Dosen pengampu MK tersebut, Mahasiswa yang terdaftar (*enrolled*) | Dosen lain, Mahasiswa luar kelas, Guest | Akses materi dan informasi kelas tanpa terdaftar; pencurian silabus/konten tertutup | **CoursePolicy@view**: Memeriksa `$course->lecturer_id === $user->id` atau `$course->students()->whereKey($user->id)->exists()`. Dipanggil via `Gate::authorize('view', $course)`. | `curl -b cookie_mhs_luar http://localhost:8000/courses/{id}` -> HTTP 403 Forbidden. |
| **3** | `PUT /courses/{course}` | Admin, Dosen pengampu MK tersebut | Dosen lain (Horizontal Escalation), Mahasiswa (Vertical Escalation) | Dosen A mengubah judul, SKS, atau deskripsi mata kuliah milik Dosen B | **CoursePolicy@update**: Memeriksa `$user->role === 'admin' \|\| $course->lecturer_id === $user->id`. Dipanggil via `UpdateCourseRequest::authorize()` dan `Gate::authorize('update', $course)`. | `curl -X PUT -b cookie_dosen_a http://localhost:8000/courses/{id_dosen_b}` -> HTTP 403 Forbidden. |
| **4** | `POST /courses` | Khusus Admin | Dosen, Mahasiswa, Guest | Mahasiswa/Dosen membuat mata kuliah sembarangan tanpa otorisasi akademik | **CoursePolicy@create**: Hanya mengizinkan `$user->role === 'admin'`. Dipanggil via `StoreCourseRequest::authorize()`. | `curl -X POST -b cookie_mhs http://localhost:8000/courses` -> HTTP 403 Forbidden. |
| **5** | `DELETE /courses/{course}` | Khusus Admin | Dosen, Mahasiswa | Dosen atau mahasiswa menghapus mata kuliah dari database | **CoursePolicy@delete**: Memeriksa `$user->role === 'admin'`. Dipanggil via `Gate::authorize('delete', $course)`. | `curl -X DELETE -b cookie_dosen http://localhost:8000/courses/{id}` -> HTTP 403 Forbidden. |
| **6** | `POST /courses/{course}/enroll` | Admin, Dosen pengampu MK | Dosen lain, Mahasiswa | Mahasiswa mendaftarkan diri sendiri tanpa persetujuan; Dosen lain membajak peserta kelas | **CoursePolicy@enroll**: Memeriksa kepemilikan kelas dosen sebelum memproses `attach()`. | `curl -X POST -b cookie_dosen_lain http://localhost:8000/courses/{id}/enroll` -> HTTP 403 Forbidden. |
| **7** | `GET /materials/{material}` | Admin, Dosen pengampu MK, Mahasiswa kelas | Mahasiswa luar kelas, Dosen luar | Kebocoran materi ajar bernilai hak cipta atau dokumen internal perkuliahan | **MaterialPolicy@view**: Memeriksa keanggotaan mahasiswa di mata kuliah materi terkait. | `curl -b cookie_mhs_luar http://localhost:8000/materials/{id}` -> HTTP 403 Forbidden. |
| **8** | `POST /courses/{course}/materials` | Admin, Dosen pengampu MK | Dosen luar, Mahasiswa | Mahasiswa menyuntikkan file materi palsu ke halaman kelas dosen | **MaterialPolicy@create**: Menolak jika bukan admin dan bukan pengampu kelas. | `curl -X POST -b cookie_mhs http://localhost:8000/courses/{id}/materials` -> HTTP 403 Forbidden. |
| **9** | `GET /assignments/{assignment}` | Admin, Dosen pengampu MK, Mahasiswa kelas | Mahasiswa luar kelas, Guest | Mahasiswa kelas lain melihat soal tugas sebelum waktunya | **AssignmentPolicy@view**: Memeriksa keterdaftaran mahasiswa pada relasi `$assignment->course`. | `curl -b cookie_mhs_luar http://localhost:8000/assignments/{id}` -> HTTP 403 Forbidden. |
| **10** | `GET /submissions/{submission}` (Titik Kritis) | Mahasiswa pemilik submission, Dosen pengampu MK, Admin | Mahasiswa lain (Horizontal IDOR) | **Pencurian jawaban/tugas mahasiswa lain**, plagiarisme tugas, kebocoran nilai pribadi | **SubmissionPolicy@view**: Memeriksa ketat `$submission->user_id === $user->id` atau dosen pengampu kelas. Dipanggil via `Gate::authorize('view', $submission)`. | `curl -b cookie_mhs_a http://localhost:8000/submissions/{id_mhs_b}` -> HTTP 403 Forbidden. |
| **11** | `GET /assignments/{assignment}/submissions` | Admin, Dosen pengampu MK | Mahasiswa (seluruh mahasiswa) | Mahasiswa melihat seluruh daftar teman yang sudah mengumpulkan beserta nilainya | **SubmissionPolicy@viewAny**: Menolak peran mahasiswa dan dosen yang bukan pengampu penugasan tersebut. | `curl -b cookie_mhs http://localhost:8000/assignments/{id}/submissions` -> HTTP 403 Forbidden. |
| **12** | `PUT /submissions/{submission}/grade` | Admin, Dosen pengampu MK | Mahasiswa (Vertical Escalation), Dosen lain | Mahasiswa memberi nilai 100 untuk dirinya sendiri; Dosen luar mengacaukan penilaian kelas | **GradePolicy@create / update**: Memeriksa apakah penilai adalah dosen pemilik mata kuliah submission terkait. | `curl -X PUT -b cookie_mhs http://localhost:8000/submissions/{id}/grade` -> HTTP 403 Forbidden. |

---

## 2. Panduan Jawaban Interview Keamanan (Minggu 7 & UTS)

### Pertanyaan 1: Kenapa `@can` di Blade TIDAK CUKUP untuk mengamankan aplikasi?
* **Jawaban:** 
  Direktif `@can` di Blade adalah komponen presentasi frontend (UI). Fungsinya semata-mata untuk **kenyamanan pengguna** (User Experience) agar tombol aksi tidak tampak jika pengguna tidak berhak. 
  Namun, `@can` sama sekali **tidak melindungi URL endpoint di server**. Penyerang dapat langsung mengirimkan HTTP request (misalnya `POST`, `PUT`, `DELETE` via `curl`, Postman, atau DevTools) ke URL target. 
  Oleh karena itu, keamanan sejati **wajib berada di backend** dengan memanggil `Gate::authorize(...)` di dalam controller atau Form Request. Tombol di Blade disembunyikan untuk estetika, controller mengunci akses untuk keamanan.

### Pertanyaan 2: Apa perbedaan Autentikasi dan Otorisasi? Tunjukkan contohnya di kode KampusLMS.
* **Autentikasi (*Authentication*):** Membuktikan **siapa Anda** (identitas). 
  * *Contoh di kode:* Proses login di `AuthController::store()` yang memanggil `Auth::attempt(['email' => ..., 'password' => ...])` dan menghasilkan session/cookie terautentikasi.
* **Otorisasi (*Authorization*):** Menentukan **apa yang boleh Anda lakukan** terhadap sumber daya tertentu (hak akses & kepemilikan).
  * *Contoh di kode:* Pemeriksaan di `CourseController::update()` yang memanggil `Gate::authorize('update', $course)`. Dosen yang sudah terautentikasi tetap ditolak (403) jika mencoba mengubah mata kuliah yang bukan miliknya.

### Pertanyaan 3: Kenapa kata sandi di-hash bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?
* **Jawaban:** 
  * **Hash bersifat satu arah (*one-way mathematical function*)**: Setelah kata sandi di-hash (misalnya menggunakan algoritma Bcrypt di Laravel), teks aslinya tidak dapat dibalikkan (*irreversible*). Hal ini melindungi pengguna: jika database bocor, penyerang tidak dapat langsung membaca kata sandi asli pengguna.
  * **Enkripsi bersifat dua arah (*two-way*)**: Dapat didekripsi kembali menjadi teks asli jika memiliki kuncinya (*decryption key*). Jika server diretas, kunci enkripsi bisa dicuri dan seluruh password terbuka.
  * **Konsekuensi untuk fitur "Lupa Password":** Sistem tidak bisa mengirimkan kata sandi lama kepada pengguna karena sistem pun tidak mengetahuinya. Yang dilakukan sistem adalah mengirimkan token reset unik yang mengizinkan pengguna membuat kata sandi baru.

### Pertanyaan 4: Apa fungsi `session()->regenerate()` saat login?
* **Jawaban:** 
  Mencegah serangan **Session Fixation**. Pada serangan ini, penyerang menanamkan ID session tertentu pada browser korban sebelum login. Jika aplikasi tidak meregenerasi ID session saat korban berhasil login, penyerang yang memegang ID session lama tersebut akan ikut masuk sebagai pengguna yang terotentikasi. `session()->regenerate()` mengganti ID session lama dengan ID session baru yang acak tepat setelah otentikasi berhasil.

### Pertanyaan 5: Kenapa daftar mata kuliah tidak boleh diambil semua lalu disaring di view?
* **Jawaban:** 
  1. **Kebocoran Data (Data Leakage):** Mengambil seluruh data dari database (`Course::all()`) berarti data rahasia sudah terambil ke memori aplikasi. Jika terjadi *dumping*, error debug, atau serialisasi API, data tersebut bocor ke pihak yang tidak berhak.
  2. **Beban Kinerja (Performance & Memory Exhaustion):** Mengambil 10.000 baris data dari database hanya untuk menampilkan 10 baris di view memboroskan memori RAM server dan bandwidth database secara masif.
  3. **Penyaringan Database (Query Scoping):** Menggunakan klausa SQL `WHERE` (`$user->courses()` atau `$user->taughtCourses()`) memastikan bahwa server database hanya mengembalikan data yang sah untuk pengguna tersebut.

### Pertanyaan 6: Kenapa memakai `Gate::authorize()` dan bukan `$this->authorize()` di Laravel 12?
* **Jawaban:** 
  Di Laravel 11 dan 12, kelas dasar `App\Http\Controllers\Controller` sekarang kosong secara default, dan trait `Illuminate\Foundation\Auth\Access\AuthorizesRequests` **tidak lagi disertakan**. Jika memanggil `$this->authorize()`, aplikasi akan melempar error fatal `Call to undefined method`. 
  `Gate::authorize(...)` adalah facade bawaan yang selalu tersedia, tidak memerlukan trait tambahan, dan dapat digunakan secara konsisten baik di dalam controller, job, maupun closure.
