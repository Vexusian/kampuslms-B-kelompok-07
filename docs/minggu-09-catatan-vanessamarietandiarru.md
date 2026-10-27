# Catatan Praktikum Modul Minggu 9 — Upload Berkas dan Keamanan Data

- **Nama:** Vanessa Marie Tandiarru
- **NIM:** 10251118
- **Kelas:** Pemrograman Web (Kelas B - Kelompok 07)

---

## 1. Verifikasi Pemenuhan Fitur BUILD (Alur Berkas KampusLMS)

Seluruh 7 poin pada bagian **BUILD Modul Minggu 9** telah terpenuhi di dalam kode:

1. **Unggah Materi oleh Dosen:**
   - **Lokasi Kode:** `app/Http/Controllers/MaterialController.php` (method `store`).
   - **Implementasi:** Menggunakan disk privat (`local` &rarr; `storage/app/private/materials/{course_id}`). Validasi berkas: `mimes:pdf,pptx,docx` dan `max:20480` (20 MB). Tersedia opsi alternatif berupa input `external_url` dengan validasi URL.

2. **Unduh Materi oleh Mahasiswa Terdaftar:**
   - **Lokasi Kode:** `app/Http/Controllers/MaterialController.php` (method `download`) dan `app/Policies/MaterialPolicy.php` (method `download`).
   - **Implementasi:** Diotorisasi dengan `Gate::authorize('download', $material)`. Policy memastikan pengunduh adalah Admin, Dosen pengampu mata kuliah, atau Mahasiswa yang terdaftar pada tabel pivot `course_user`. Berkas dialirkan lewat `Storage::disk('local')->download()`.

3. **Pengumpulan Tugas oleh Mahasiswa:**
   - **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php` (method `store`).
   - **Implementasi:**
     - Menggunakan `Submission::updateOrCreate` berbasis unique composite key `['assignment_id' => $assignment->id, 'user_id' => $user->id]`.
     - Keterlambatan (`is_late`) dihitung di server: `now()->greaterThan($assignment->due_at)`.
     - Menolak pengumpulan jika `!$assignment->allow_late` dan waktu saat ini telah melewati tenggat.
     - Berkas lama dihapus dari disk privat sebelum berkas baru disimpan saat pengumpulan ulang.

4. **Unduh Submission Berkas Tugas:**
   - **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php` (method `download`) dan `app/Policies/SubmissionPolicy.php` (method `download`).
   - **Implementasi:** Diotorisasi dengan `Gate::authorize('download', $submission)`. Hanya pemilik tugas (`$submission->user_id === $user->id`), dosen pengampu mata kuliah, dan admin yang diizinkan mengunduh. Akses lain menghasilkan respon `403 Forbidden`.

5. **Daftar Pengumpulan untuk Dosen (Sudah/Belum Mengumpulkan & Terlambat):**
   - **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php` (method `index`) dan `resources/views/submissions/index.blade.php`.
   - **Implementasi:** Menampilkan ringkasan statistik (total terdaftar, sudah mengumpulkan, belum mengumpulkan), tabel mahasiswa yang sudah mengumpulkan lengkap dengan badge `Terlambat` atau `Tepat Waktu`, serta tabel daftar mahasiswa yang belum mengumpulkan tugas.

6. **Penghapusan Berkas Fisik saat Record Dihapus (Termasuk Kasus Cascade):**
   - **Lokasi Kode:**
     - `app/Models/Submission.php`: method `booted()` menghapus berkas fisik submission dari disk `local`.
     - `app/Models/Material.php`: method `booted()` menghapus berkas materi dari disk `local`.
     - `app/Models/Assignment.php`: method `booted()` menghapus seluruh model `Submission` dan folder direktori `submissions/{assignment_id}` secara rekursif saat penugasan dihapus.
     - `app/Models/Course.php`: method `booted()` menghapus seluruh `Material` dan `Assignment` melalui model Eloquent agar event deleting terpicu saat mata kuliah dihapus.

7. **Tampilan `original_name` dan Ukuran Berkas Terbaca Manusia:**
   - **Lokasi Kode:**
     - Accessor `getFormattedFileSizeAttribute()` pada `app/Models/Material.php` dan `app/Models/Submission.php`.
     - Ditampilkan pada tampilan `resources/views/materials/show.blade.php`, `resources/views/assignments/show.blade.php`, dan `resources/views/submissions/show.blade.php` beserta `submissions/index.blade.php` dengan konversi satuan dinamis (B, KB, MB, GB).

---

## 2. Jawaban Checkpoint Modul Minggu 9

### [1] Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya.

**Jawaban:**
Folder `storage/app/public` terhubung langsung ke `public/storage` melalui symlink sistem (`php artisan storage:link`). Ketika suatu berkas diminta melalui URL publik (misal: `http://localhost/storage/submissions/1/tugas.pdf`), web server (Nginx/Apache) langsung mengirimkan berkas tersebut secara statis tanpa meneruskan request ke runtime PHP/Laravel.

Akibatnya:
- Middleware `auth` dilewati.
- Pengecekan session login dilewati.
- Otorisasi `Gate` dan `SubmissionPolicy` tidak pernah dieksekusi.

**Peragaan Kebocoran:**
1. Berkas submission ditaruh pada disk publik.
2. Buka browser pada mode Incognito (penyamaran) tanpa melakukan autentikasi/login sama sekali.
3. Masukkan URL statis berkas tersebut di bilah alamat browser.
4. Berkas submission langsung terunduh secara bebas oleh publik tanpa autentikasi maupun otorisasi.

---

### [2] Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?

**Jawaban:**
Penyimpanan berkas menggunakan `$file->store()` secara default menghasilkan nama acak (hash acak 40 karakter) dan path folder terpisah. Nama asli berkas pengguna disimpan terpisah pada kolom `original_filename` hanya untuk keperluan tampilan.

Serangan dan masalah yang dicegah:
1. **File Overwrite Collision:** Mencegah berkas milik mahasiswa pertama tertimpa ketika mahasiswa kedua mengunggah berkas dengan nama yang identik (misalnya `tugas.pdf`).
2. **URL / Resource Enumeration Attack:** Mencegah penyerang menebak URL berkas milik pengguna lain dengan pola nama yang berurutan atau umum.
3. **Remote Code Execution (RCE) & Double Extension Attack:** Mencegah eksekusi script berbahaya jika pengguna mengunggah berkas seperti `shell.php.pdf` atau berkas yang berpotensi dieksekusi langsung oleh server.
4. **Directory / Path Traversal Attack:** Mencegah manipulasi nama berkas yang mengandung karakter `../` untuk menulis berkas di luar folder penyimpanan yang ditentukan.

---

### [3] Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?

**Jawaban:**
- **Validasi `extensions`:** Hanya mencocokkan string ekstensi teks di akhir nama berkas berdasarkan pola nama (string matching).
- **Validasi `mimes`:** Membaca header biner internal berkas (*magic bytes*) menggunakan ekstensi PHP `fileinfo` (`finfo`) untuk memverifikasi tipe MIME konten yang sebenarnya, bukan sekadar nama ekstensinya.
- **Yang lebih aman:** **`mimes`**. 
- **Alasan:** Penyerang dapat dengan mudah mengubah nama script berbahaya `webshell.php` menjadi `webshell.pdf`. Jika hanya menggunakan validasi `extensions:pdf`, berkas tersebut akan lolos. Namun jika menggunakan validasi `mimes:pdf`, Laravel memeriksa *magic bytes* berkas dan mendeteksi bahwa tipe MIME berkas adalah `text/x-php` atau `text/plain`, sehingga unggahan langsung ditolak.

---

### [4] `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?

**Jawaban:**
- `max:5120` berarti **5 Megabyte (5 MB)**, karena aturan validasi ukuran berkas di Laravel dihitung dalam satuan **Kilobyte (KB)**:
  $$\frac{5120 \text{ KB}}{1024} = 5 \text{ MB}$$
- **Hal yang membatalkan batas ini di level server:**
  1. Konfigurasi `php.ini`:
     - `upload_max_filesize`: Batas maksimal ukuran satu berkas yang diterima PHP.
     - `post_max_size`: Batas maksimal total muatan HTTP POST yang diterima PHP (harus lebih besar dari `upload_max_filesize`).
  2. Konfigurasi Web Server:
     - Directive `client_max_body_size` pada Nginx (default 1 MB jika tidak disetel).
     - Directive `LimitRequestBody` pada Apache.
  Jika `upload_max_filesize` di `php.ini` disetel `2M`, unggahan berkas berukuran 3 MB akan langsung digugurkan oleh server sebelum request sempat diproses oleh Laravel, sehingga validasi `max:5120` tidak pernah dieksekusi.

---

### [5] Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.

**Jawaban:**
Pencegahan diimplementasikan pada dua lapisan:

1. **Penyimpanan Privat:**
   Berkas disimpan pada disk privat `local` (`storage/app/private/submissions/...`) tanpa symlink publik, sehingga berkas tidak memiliki URL statis dan mutlak harus diakses melalui Controller.

2. **Otorisasi Policy pada Controller:**
   Pada [app/Http/Controllers/SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php) method `download()`:
   ```php
   public function download(Submission $submission)
   {
       Gate::authorize('download', $submission);

       if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
           abort(404, 'Berkas submission tidak ditemukan pada disk penyimpanan privat.');
       }

       return Storage::disk('local')->download(
           $submission->file_path,
           $submission->original_filename ?? basename($submission->file_path)
       );
   }
   ```
   Panggilan `Gate::authorize('download', $submission)` mengarah ke [app/Policies/SubmissionPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/SubmissionPolicy.php) method `view()`:
   ```php
   public function view(User $user, Submission $submission): bool
   {
       if ($user->role === 'admin') {
           return true;
       }

       // Mahasiswa hanya boleh melihat/mengunduh berkas miliknya sendiri
       if ($submission->user_id === $user->id) {
           return true;
       }

       // Dosen pengampu mata kuliah bersangkutan
       $course = $submission->assignment?->course;
       if ($user->role === 'dosen' && $course && $course->lecturer_id === $user->id) {
           return true;
       }

       return false; // Mahasiswa lain langsung ditolak dengan HTTP 403 Forbidden
   }
   ```

---

### [6] Kenapa `is_late` dihitung di server, bukan dikirim dari browser?

**Jawaban:**
Data dari browser merupakan *untrusted input* yang dapat dimanipulasi sepenuhnya oleh pengguna melalui Network tab, script JavaScript, cURL, maupun Postman. Jika status keterlambatan atau waktu pengiriman diambil dari klien:
1. Mahasiswa dapat memundurkan jam sistem operasi pada perangkat mereka sebelum mengunggah.
2. Mahasiswa dapat memodifikasi payload HTTP POST secara manual dengan menyisipkan `'is_late': false`.

Dengan menghitung status keterlambatan langsung di server melalui komparasi waktu server terhadap tenggat basis data:
```php
$isLate = $assignment->due_at ? now()->greaterThan($assignment->due_at) : false;
```
integritas data terjamin dan manipulasi di sisi klien tidak berpengaruh terhadap status penilaian.

---

## 3. Telaah Penelusuran Berkas (READ)

1. **Path Lengkap Berkas Fisik:**
   Tersimpan pada root disk lokal privat:
   `c:\Users\vanes\laragon\www\kampuslms\storage\app\private\submissions\{assignment_id}\{hash_acak}.pdf`
2. **Perbandingan `file_path` di Database vs Lokasi Fisik:**
   - Database hanya menyimpan path relatif terhadap disk: `submissions/1/xxxxx.pdf`.
   - Lokasi fisik riil: `storage/app/private/submissions/1/xxxxx.pdf`.
   Laravel mengabstraksikan root disk sehingga jika konfigurasi storage dipindahkan (misal ke AWS S3), baris database tidak perlu diubah.
3. **Akses Langsung via Browser:**
   Menghasilkan `404 Not Found` karena folder `storage/app/private` tidak memiliki symbolic link ke direktori `public` web server.
4. **Perbedaan Disk `local` vs `public` di `config/filesystems.php`:**
   - Disk `local`: Root mengarah ke `storage_path('app/private')`. Tidak dapat diakses langsung melalui URL publik browser.
   - Disk `public`: Root mengarah ke `storage_path('app/public')` dengan URL prefix `/storage`. Dapat diakses langsung oleh publik setelah menjalankan `php artisan storage:link`.
5. **Fungsi `php artisan storage:link`:**
   Membuat symlink sistem dari `public/storage` ke `storage/app/public`. Berkas privat (materi dan tugas) dilarang disimpan di sana karena web server akan menyajikan berkas secara langsung tanpa validasi autentikasi dan otorisasi Laravel.
