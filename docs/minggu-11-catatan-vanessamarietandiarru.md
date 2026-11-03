# Catatan Praktikum Modul Minggu 11 — Optimisasi Performa

- **Nama:** Vanessa Marie Tandiarru
- **NIM:** 10251118
- **Kelas:** Pemrograman Web (Kelas B - Kelompok 07)

---

## 1. Ringkasan Implementasi Bagian BUILD (Minggu 11)

1. **Dokumentasi Pengukuran Performa (`docs/performa.md` & `docs/performance.md`)**:
   - Berisi tabel pengukuran performa sebelum optimisasi (baseline) dan sesudah optimisasi untuk seluruh halaman utama, lengkap dengan analisis selisih query, waktu respon, dan penggunaan memori.

2. **Pencegahan N+1 Otomatis (`Model::preventLazyLoading`)**:
   - Diterapkan pada `app/Providers/AppServiceProvider.php` dengan `Model::preventLazyLoading(! app()->isProduction())`.
   - Menghasilkan exception secara otomatis jika relasi model diakses tanpa eager loading di lingkungan pengembangan/pengujian. Seluruh aplikasi telah diperbaiki dan berjalan tanpa melanggar aturan ini.

3. **Penyelesaian N+1 dengan Eager Loading**:
   - Menggunakan `with()`, `loadMissing()`, dan `withCount()` pada:
     - `AssignmentController@index`: eager load `course`, relasi `submissions` milik user, dan `withCount('submissions')`.
     - `SubmissionController@index`: eager load `course` serta seleksi kolom spesifik untuk relasi siswa.
     - `CourseController@show`: pemanfaatan `loadCount(['students', 'materials', 'assignments'])`.
     - `resources/views/components/layout.blade.php`: optimisasi penghitungan lonceng notifikasi menggunakan `unreadNotifications()->count()`.

4. **Migrasi Penambahan Index Database**:
   - Dibuat melalui berkas migrasi `database/migrations/2026_11_03_000001_add_performance_indexes.php`.
   - Menambahkan index pada kolom yang sering difilter atau diurutkan: `users.role`, `courses.status`, `assignments.status`, `submissions.submitted_at`, `submissions.is_late`, dan `grades.graded_at`. (Catatan: foreign key seperti `assignments.created_by` tidak diindeks manual karena sudah otomatis diindeks oleh engine InnoDB).

5. **Pagination pada Seluruh Halaman Daftar**:
   - Seluruh daftar data menggunakan pagination (`paginate(15)` / `paginate(20)`), termasuk daftar mata kuliah, tugas, materi, submissions, pengguna admin, dan notifikasi. Tidak ada query yang memuat seluruh tabel sekaligus ke memori.

6. **Strategi Caching Dashboard**:
   - Agregasi kartu statistik ringkasan dashboard (total mata kuliah, total mahasiswa, materi, tugas, dan submission) di-cache selama 30 menit menggunakan `Cache::remember()`.
   - Invalidasi otomatis diterapkan melalui model lifecycle event (`saved` & `deleted`) pada model `Course`, `Assignment`, `Material`, dan `Submission`, serta method `enroll`/`unenroll` pada `CourseController`.

7. **Pembersihan Fungsi `env()` di Luar `config/`**:
   - Seluruh pemanggilan `env()` di luar direktori `config/` (seperti pada seeder) telah diganti menjadi `config()`.
   - Berkas konfigurasi telah diuji dengan `php artisan config:cache` tanpa kendala.

8. **Target Kinerja Rekap Nilai**:
   - Halaman rekap nilai berhasil ditekan menjadi **11–12 query** (di bawah target maksimal 15 query).

---

## 2. Jawaban Checkpoint Minggu 11

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?

**Jawaban:**
Halaman terburuk sebelum optimisasi adalah **Dashboard (Dosen & Mahasiswa)** dengan 16 query, disusul **Daftar Tugas** (13 query) dan **Rekap Nilai** (12 query).

**Penyebabnya:**
- Pada Dashboard, setiap kartu metrik statistik menjalankan query agregasi `COUNT(*)` dan `whereHas()` terpisah secara independen setiap kali halaman dibuka tanpa adanya cache.
- Pada Daftar Tugas dan Rekap Nilai, terdapat masalah N+1 query akibat pemanggilan relasi di dalam perulangan view (seperti query count pengumpulan dan lazy loading relasi course pada evaluasi policy).

---

### 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.

**Jawaban:**
- **Titik Pengujian:** Halaman Daftar Tugas (`assignments.index`).
- **Kondisi Tanpa Eager Loading (`with()` dimatikan):**
  Saat menampilkan 15 data tugas, setiap baris melakukan query tambahan untuk memeriksa policy `$assignment->course` dan query `$assignment->submissions()->count()`. Total query melonjak menjadi **30+ query** (1 query utama penugasan + N query course + N query count submission).
- **Kondisi Dengan Eager Loading (`with()` aktif):**
  Dengan menyertakan `with('course')` dan `withCount('submissions')`, Laravel hanya menjalankan total **2 query batch** di awal (`SELECT * FROM assignments` dan query hitung berelasi dengan klausa `IN (...)`). Jumlah query tetap konstan berapa pun jumlah baris yang ditampilkan.

---

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?

**Jawaban:**
- **`withCount('students')`**:
  Menghitung jumlah data langsung di level database melalui subquery SQL (`SELECT COUNT(*) ...`). Data yang dikirimkan ke aplikasi PHP hanya berupa satu nilai integer kolom agregat (`students_count`).
- **`count($course->students)`**:
  Menjalankan query SQL untuk mengambil seluruh baris data dan seluruh kolom dari tabel `users` yang berelasi, menginstansiasi objek model Eloquent untuk setiap baris mahasiswa, lalu menghitung ukuran koleksi array tersebut di memori PHP.
- **Alasan yang kedua boros:**
  Menghabiskan banyak memori (RAM) server dan bandwidth database karena memuat seluruh data baris yang tidak dibutuhkan hanya demi mendapatkan satu angka jumlah baris.

---

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.

**Jawaban:**
Index ditambahkan melalui migrasi `2026_11_03_000001_add_performance_indexes.php`, di antaranya:
- `users.role` (`users_role_index`)
- `courses.status` (`courses_status_index`)
- `assignments.status` (`assignments_status_index`) *(kolom `created_by` tidak diindeks manual karena sudah memiliki indeks bawaan foreign key)*
- `submissions.submitted_at` dan `submissions.is_late`
- `grades.graded_at`

**Bukti Pengujian dengan EXPLAIN:**
Query SQL:
```sql
EXPLAIN SELECT * FROM users WHERE role = 'dosen';
```

Hasil eksekusi:
- Kolom `key`: `users_role_index` (index terpakai).
- Kolom `type`: `ref` (pencarian berbasis referensi indeks, bukan `ALL` / full table scan).
- Kolom `rows`: `3` (database hanya membaca 3 baris data yang relevan dari total seluruh baris tabel).

---

### 5. Sebutkan satu data di aplikasi Anda yang **tidak boleh** di-cache. Kenapa?

**Jawaban:**
- **Data:** Status Pengumpulan dan Tenggat Waktu Tugas Mahasiswa (`recentAssignments` / `submissions`).
- **Alasan:**
  Data ini bersifat kritis dan harus real-time. Jika status pengumpulan di-cache (misalnya 10 menit), mahasiswa yang baru saja mengunggah tugas akan tetap melihat status "Belum Kumpul", atau tugas yang sudah lewat tenggat waktu masih terlihat aktif. Hal ini dapat menimbulkan kesalahan informasi dan komplain mahasiswa. Data sensitif lain yang tidak boleh di-cache mencakup nilai tugas (`grades`) dan lonceng notifikasi (`unreadNotifications`).

---

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?

**Jawaban:**
- **Yang Terjadi:**
  Ketika perintah `php artisan config:cache` dieksekusi, Laravel menggabungkan seluruh file konfigurasi menjadi satu file cache di `bootstrap/cache/config.php`. Setelah dicache, file `.env` tidak akan dibaca lagi oleh framework pada saat runtime request. Akibatnya, seluruh pemanggilan fungsi `env('KEY')` di luar folder `config/` akan mengembalikan nilai `null`.
- **Pentingnya untuk Minggu Depan (Deployment):**
  Di server produksi (Minggu 12), `config:cache` wajib dijalankan demi kecepatan eksekusi aplikasi. Jika masih ada pemanggilan `env()` di controller, route, view, atau service, aplikasi akan langsung mengalami error atau salah membaca variabel lingkungan di server produksi, meskipun di lingkungan lokal berjalan normal. Oleh karena itu, semua variabel lingkungan harus diakses melalui wrapper `config('app.key')`.

