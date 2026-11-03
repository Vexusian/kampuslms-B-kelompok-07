# Dokumentasi Pengukuran Performa KampusLMS (Minggu 11)

Dokumentasi ini digunakan untuk mencatat dan membandingkan performa aplikasi KampusLMS sebelum dan sesudah dilakukan optimisasi (mengatasi N+1 query, optimisasi index database, eager loading, caching, pagination, dan konfigurasi produksi).

---

## 1. Pengukuran Sebelum Optimisasi (Baseline)

| Halaman | Jumlah query | Waktu (ms) | Memori |
|---------|-------------:|-----------:|-------:|
| Daftar mata kuliah (Mahasiswa) | 7 | 1008 | 25 MB |
| Daftar mata kuliah (Dosen) | 7 | 936 | 25 MB |
| Detail mata kuliah (Mahasiswa) | 10 | 950 | 24 MB |
| Detail mata kuliah (Dosen) | 10 | 1017 | 25 MB |
| Daftar tugas (Mahasiswa) | 11 | 934 | 24 MB |
| Daftar tugas (Dosen) | 13 | 957 | 24MB |
| Daftar pengumpulan (Dosen) | 12 | 959 | 25 MB |
| **Rekap nilai** (Mahasiswa) | 12 | 1015 | 24 MB |
| **Rekap nilai** (Dosen) | 11 | 948 | 24 MB |
| Dashboard mahasiswa | 16 | 984 | 24 MB |
| Dashboard dosen | 16 | 1006 | 24 MB |

---

## 2. Pengukuran Setelah Optimisasi

| Halaman | Jumlah query | Waktu (ms) | Memori |
|---------|-------------:|-----------:|-------:|
| Daftar mata kuliah (Mahasiswa) | 7 | 920 | 25 MB |
| Daftar mata kuliah (Dosen) | 7 | 923 | 25 MB |
| Detail mata kuliah (Mahasiswa) | 10 | 911 | 24 MB |
| Detail mata kuliah (Dosen) | 10 | 967 | 24 MB |
| Daftar tugas (Mahasiswa) | 11 | 971 | 24 MB |
| Daftar tugas (Dosen) | 13 | 888 | 24 MB |
| Daftar pengumpulan (Dosen) | 12 | 927 | 25 MB |
| **Rekap nilai** (Mahasiswa) | 12 | 964 | 24 MB |
| **Rekap nilai** (Dosen) | 11 | 915 | 24 MB |
| Dashboard mahasiswa | 11 | 915 | 24 MB |
| Dashboard dosen | 10 | 909 | 24 MB |

---

## 3. Ringkasan Perbandingan (Sebelum vs Sesudah)

| Halaman | Query (Sebelum) | Query (Sesudah) | Penghematan Query | Waktu Sebelum (ms) | Waktu Sesudah (ms) | Memori Sebelum | Memori Sesudah |
|---------|----------------:|----------------:|------------------:|-------------------:|-------------------:|---------------:|---------------:|
| Daftar mata kuliah (Mahasiswa) | 7 | 7 | 0 query | 1008 | 920 | 25 MB | 25 MB |
| Daftar mata kuliah (Dosen) | 7 | 7 | 0 query | 936 | 923 | 25 MB | 25 MB |
| Detail mata kuliah (Mahasiswa) | 10 | 10 | 0 query | 950 | 911 | 24 MB | 24 MB |
| Detail mata kuliah (Dosen) | 10 | 10 | 0 query | 1017 | 967 | 25 MB | 24 MB |
| Daftar tugas (Mahasiswa) | 11 | 11 | 0 query | 934 | 971 | 24 MB | 24 MB |
| Daftar tugas (Dosen) | 13 | 13 | 0 query | 957 | 888 | 24 MB | 24 MB |
| Daftar pengumpulan (Dosen) | 12 | 12 | 0 query | 959 | 927 | 25 MB | 25 MB |
| **Rekap nilai** (Mahasiswa) | 12 | 12 | 0 query | 1015 | 964 | 24 MB | 24 MB |
| **Rekap nilai** (Dosen) | 11 | 11 | 0 query | 948 | 915 | 24 MB | 24 MB |
| Dashboard mahasiswa | 16 | 11 | -5 query | 984 | 915 | 24 MB | 24 MB |
| Dashboard dosen | 16 | 10 | -6 query | 1006 | 909 | 24 MB | 24 MB |

---

## 4. Analisis & Catatan Masalah

### A. Halaman Terburuk Sebelum Optimisasi
- **Halaman**: Dashboard (Dosen & Mahasiswa) dan Rekap Nilai.
- **Penyebab utama (N+1 / Missing Eager Loading / dll)**:
  1. Pada Dashboard: setiap kartu indikator metrik (total mata kuliah, total mahasiswa, materi, tugas, dan submission) menjalankan query agregasi `COUNT(*)` dan `whereHas()` terpisah setiap kali halaman dimuat tanpa adanya mekanisme caching.
  2. Pada Daftar Tugas: terjadi query relasi berulang untuk menghitung berkas submission (`$assignment->submissions()->count()`) serta evaluasi policy yang mengakses `$assignment->course` secara lazy-load di dalam loop penugasan.
  3. Pada Rekap Nilai: rentan mengalami N+1 query jika relasi bertingkat (`submissions`, `student`, dan `grade`) tidak di-load secara kolektif di awal menggunakan eager loading.
- **Hasil EXPLAIN query**:
  ```sql
  -- Sebelum penambahan index:
  EXPLAIN SELECT * FROM users WHERE role = 'dosen';
  -- type: ALL (Full Table Scan membaca seluruh 34 baris tabel)

  -- Sesudah penambahan index (users_role_index):
  EXPLAIN SELECT * FROM users WHERE role = 'dosen';
  -- type: ref | key: users_role_index | rows: 3 (Index Scan terarah)
  ```

### B. Index Tambahan yang Diterapkan
| Tabel | Kolom Index | Query yang Diuntungkan | Biaya/Konsekuensi Penulisan |
|-------|-------------|------------------------|-----------------------------|
| `users` | `role` (`users_role_index`) | Filter role pada dropdown form pembuatan course, pendaftaran enrollment mahasiswa, dan panel kelola user | Sedikit overhead saat penambahan atau pembaruan role user |
| `courses` | `status` (`courses_status_index`) | Filter status mata kuliah (`active`/`draft`), katalog publik, dan penghitungan agregat dashboard | Penulisan sedikit lebih lambat saat pembuatan atau perubahan status kursus |
| `assignments` | `status`, `created_by` | Filter tugas terbit (`published`), otorisasi kepemilikan tugas oleh dosen pembuat | Overhead saat pembuatan dan pembaruan data tugas baru |
| `submissions` | `submitted_at`, `is_late` | Pengurutan daftar pengumpulan tugas dosen (`latest('submitted_at')`) dan filtering keterlambatan | Overhead kecil saat mahasiswa mengunggah/mengumpulkan tugas |
| `grades` | `graded_at` | Audit riwayat penilaian tugas terbaru dan urutan feedback | Overhead kecil saat dosen menyimpan nilai tugas |

### C. Strategi Caching Dashboard
- **Data yang di-cache**: Agregasi metrik ringkasan kartu statistik dashboard per user (`totalCourses`, `activeCourses`, `draftCourses`, `totalLecturers`, `totalStudents`, `totalMaterials`, `totalAssignments`, `totalSubmissions`).
- **Durasi cache & mekanisme invalidasi**:
  - Durasi: 30 menit (`now()->addMinutes(30)`).
  - Mekanisme invalidasi otomatis: Event model lifecycle (`saved` & `deleted`) pada model `Course`, `Assignment`, `Material`, dan `Submission` secara otomatis memanggil `Cache::forget()` untuk membersihkan cache statistik admin, dosen pengampu, dan mahasiswa terkait. Serta metode `enroll` & `unenroll` pada `CourseController` yang membersihkan cache saat pendaftaran kelas berubah.
- **Data yang SENGAJA TIDAK di-cache & alasannya**:
  1. **Daftar Tugas Mendatang / Tenggat Waktu (`recentAssignments`)**: Status pengumpulan dan tenggat waktu wajib akurat secara real-time. Jika mahasiswa baru saja mengumpulkan tugas, status tugas harus langsung berubah tanpa menunggu masa kedaluwarsa cache.
  2. **Nilai Mahasiswa & Feedback (`grades`)**: Nilai bersifat sensitif dan membutuhkan umpan balik langsung setelah dosen menginput nilai.
  3. **Lonceng Notifikasi (`unreadNotifications`)**: Indikator belum dibaca harus selalu mencerminkan status terbaru seketika saat dibaca. 

