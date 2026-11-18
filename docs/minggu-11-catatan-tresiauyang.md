# Catatan Minggu 11

-  Nama: Tresia Uyang
- NIM: 10241072

## 11.3 Read → Break → Fix → Build

### READ — Ukur aplikasi Anda sendiri (40 menit)

Pastikan seeder penuh sudah dijalankan (`migrate:fresh --seed`). Lalu tanpa AI, isi tabel ini di `docs/performa.md`:

#### 1. Urutkan dari yang terburuk.

| No. | Halaman | Query | Waktu | Memori |
|---:|---|---:|---:|---:|
| 1 | Dashboard Dosen | 16 | 1006 ms | 24 MB |
| 2 | Dashboard Mahasiswa | 16 | 984 ms | 24 MB |
| 3 | Daftar Tugas Dosen | 13 | 957 ms | 24 MB |
| 4 | Rekap Nilai Mahasiswa | 12 | 1015 ms | 24 MB |
| 5 | Daftar Pengumpulan Dosen | 12 | 959 ms | 25 MB |
| 6 | Daftar Tugas Mahasiswa | 11 | 934 ms | 24 MB |
| 7 | Rekap Nilai Dosen | 11 | 948 ms | 24 MB |
| 8 | Detail Mata Kuliah Dosen | 10 | 1017 ms | 25 MB |
| 9 | Detail Mata Kuliah Mahasiswa | 10 | 950 ms | 24 MB |
| 10 | Daftar Mata Kuliah Mahasiswa | 7 | 1008 ms | 25 MB |
| 11 | Daftar Mata Kuliah Dosen | 7 | 936 ms | 25 MB |
2. Untuk halaman terburuk, buka Debugbar tab Queries. Temukan query yang **berulang dengan pola sama**. Itu N+1 Anda.
3. Salin satu query terlambat, jalankan `EXPLAIN` di database. Catat kolom `type` dan `rows`.

Halaman rekap nilai hampir pasti yang terburuk. Itu memang disengaja sejak perancangan skema.

### BREAK — Tujuh percobaan (45 menit)

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman | Berapa N+1 tersembunyi yang muncul? |
| 2 | Hapus semua `with()` dari satu controller, ukur ulang | Bandingkan angkanya |
| 3 | Ganti `withCount('students')` menjadi `count($course->students)`, ukur memori | Beda menghitung vs memuat |
| 4 | Hapus index dari `assignments.course_id`, jalankan `EXPLAIN` | `type` berubah menjadi `ALL` |
| 5 | Naikkan seeder menjadi 300 mahasiswa dan 1000 submission, buka rekap nilai | Masalah yang tadinya kecil jadi jelas |
| 6 | Jalankan `config:cache`, lalu panggil `env('APP_NAME')` dari sebuah controller | **Mengembalikan null** — ingat baik-baik |
| 7 | Pasang cache 10 menit pada nilai mahasiswa, ubah nilainya, muat ulang | Data basi yang merugikan pengguna |

Nomor 6 adalah bekal untuk minggu depan. Nomor 7 mengajarkan bahwa cache bukan selalu perbaikan.

### FIX — Repo cacat (30 menit)

Branch `w11` pada repo `kampuslms-broken` berisi halaman rekap nilai dengan **6 masalah**: N+1 pada tiga tingkat relasi, `count()` yang memuat seluruh koleksi, `select *` padahal hanya dua kolom dipakai, index hilang pada kolom yang sering difilter, seluruh data dimuat tanpa pagination, dan `env()` dipanggil di luar `config/`.

Perbaiki, kirim PR. **Deskripsi PR wajib memuat tabel sebelum-sesudah**: jumlah query, waktu, memori.

### BUILD — Optimisasi KampusLMS

1. **`docs/performa.md`** berisi tabel pengukuran sebelum dan sesudah untuk seluruh halaman utama.
2. **`Model::preventLazyLoading()`** aktif di lingkungan non-produksi, dan aplikasi berjalan **tanpa melanggarnya**.
3. Seluruh N+1 diperbaiki dengan `with()` / `withCount()` / `loadMissing()`.
4. **Migrasi tambahan** berisi index yang Anda rancang, dengan komentar menyebut query yang diuntungkan.
5. Pagination di seluruh halaman daftar, tidak ada yang memuat seluruh tabel.
6. Cache pada minimal satu bagian dashboard, lengkap dengan pembersihan otomatis saat data berubah — dan penjelasan di `docs/performa.md` tentang bagian mana yang sengaja **tidak** di-cache dan mengapa.
7. Seluruh pemanggilan `env()` di luar `config/` dihapus, diganti `config()`.
8. Target: halaman rekap nilai turun menjadi **di bawah 15 query**.

---