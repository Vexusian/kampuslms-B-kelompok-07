# Dokumentasi Optimisasi & Pengukuran Performa KampusLMS

Dokumentasi ini mencatat hasil pengujian dan pengukuran performa aplikasi **KampusLMS** sebelum dan sesudah dilakukan optimisasi query Eloquent, penanganan masalah *N+1 query problem*, serta pengaktifan fitur pencegahan lazy loading (`Model::preventLazyLoading()`).

---

## 1. Lingkungan & Kondisi Basis Data Pengujian

Pengukuran dilakukan pada kondisi basis data **penuh** (*populated database*) setelah menjalankan seeder komprehensif (`DatabaseSeeder`), bukan saat basis data masih kosong:

* **Jumlah Akun Pengguna (`users`)**: 35 pengguna (1 Admin, 3 Dosen pengampu, 30+ Mahasiswa, 1 Mahasiswa Demo).
* **Jumlah Mata Kuliah (`courses`)**: 5 mata kuliah aktif dan draft dengan relasi dosen pengampu.
* **Jumlah Penugasan (`assignments`)**: 25 tugas (terbagi dalam kategori aktif, lewat batas waktu/past, dan draft).
* **Jumlah Pengumpulan Tugas (`submissions`)**: 230 data submission mahasiswa lengkap dengan berkas dan catatan teks.
* **Jumlah Penilaian (`grades`)**: 140+ data nilai dengan skor dan umpan balik (*feedback*).
* **Stack**: PHP 8.5 / Laravel 12 / MySQL 8 (Laragon) / Windows.
* **Metode Pengukuran**: Profiling HTTP Kernel Laravel (`DB::getQueryLog()`, `hrtime()`, `memory_get_peak_usage()`, serta kesesuaian metrik Barryvdh Laravel Debugbar / Telescope).

---

## 2. Implementasi Pengaman: `Model::preventLazyLoading()`

Fitur pengaman bawaan Laravel diaktifkan pada lingkungan non-produksi (local dan testing) di dalam `app/Providers/AppServiceProvider.php`:

```php
namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Mencegah N+1 Lazy Loading pada lingkungan local & testing
        Model::preventLazyLoading(! app()->isProduction());
    }
}
```

Fitur ini secara otomatis melempar exception `LazyLoadingViolationException` jika relasi Eloquent diakses secara dinamis tanpa dilakukan *eager loading* terlebih dahulu (`with()` atau `load()`).

---

## 3. Masalah Sebelum Optimisasi & Solusi yang Diterapkan

1. **Daftar Tugas Dosen (`/dosen/courses/{course}/assignments`)**:
   * *Masalah*: Memicu 33 query karena memanggil `$assignment->submissions()->count()` di setiap baris tugas dan melempar `LazyLoadingViolationException` pada pengecekan otorisasi policy `$assignment->course`.
   * *Solusi*: Menggunakan `with('course')` dan `withCount('submissions')` pada `AssignmentController::index()` serta membaca atribut virtual `$assignment->submissions_count` pada Blade.
2. **Daftar Tugas Mahasiswa (`/mahasiswa/courses/{course}/assignments`)**:
   * *Masalah*: Memicu 21 query karena mengeksekusi query pencarian submission per tugas secara individual (`$assignment->submissions()->where(...)->first()`).
   * *Solusi*: Meng-eager load relasi dengan filter pengguna `with(['submissions' => fn($q) => $q->where('user_id', $user->id)])` dan mengambilnya dari memori koleksi (`$assignment->submissions->first()`).
3. **Dashboard Mahasiswa & Dosen (`/dashboard`)**:
   * *Masalah*: Mengeksekusi query berulang ke tabel `courses` untuk menghitung total mata kuliah, kursus aktif, kursus draft, dan daftar kursus terbaru.
   * *Solusi*: Mengambil data kursus dosen/mahasiswa dalam satu kali penarikan query berelasi `with('lecturer')` dan menghitung statistik secara in-memory melalui koleksi Laravel.
4. **Navigasi Notifikasi Global (`components/layout.blade.php`)**:
   * *Solusi*: Mengoptimasi pemanggilan jumlah notifikasi yang belum dibaca menggunakan agregat satu kali kueri `$unreadNotificationsCount = auth()->user()->unreadNotifications()->count()`.

---

## 4. Tabel Pengukuran Sebelum Optimisasi

Tabel berikut menunjukkan hasil pengukuran performa sebelum optimisasi (dengan kondisi basis data penuh):

| Halaman | Jumlah query | Waktu (ms) | Memori |
|---------|-------------:|-----------:|-------:|
| Daftar mata kuliah (Dosen) | 10 | 163.09 | 30.55 MB |
| Daftar mata kuliah (Mahasiswa) | 8 | 25.18 | 30.69 MB |
| Detail mata kuliah (Dosen) | 11 | 42.70 | 30.98 MB |
| Detail mata kuliah (Mahasiswa) | 11 | 30.52 | 31.06 MB |
| Daftar tugas (Dosen) | 33 | 2976.47 | 37.18 MB |
| Daftar tugas (Mahasiswa) | 21 | 68.65 | 37.18 MB |
| Daftar pengumpulan (dosen) | 13 | 58.19 | 31.46 MB |
| **Rekap nilai** (Dosen) | 13 | 65.03 | 32.35 MB |
| **Rekap nilai** (Mahasiswa) | 13 | 39.44 | 32.35 MB |
| Dashboard mahasiswa | 16 | 44.53 | 37.17 MB |
| Dashboard dosen | 17 | 46.67 | 37.17 MB |

> **Catatan Sebelum Optimisasi:** Pada halaman *Daftar tugas (Dosen)*, waktu eksekusi melonjak tinggi (2.976,47 ms) karena sebelum perbaikan eager loading, halaman tersebut memicu exception `LazyLoadingViolationException` (HTTP 500) saat `Model::preventLazyLoading()` aktif.

---

## 5. Tabel Pengukuran Sesudah Optimisasi

Tabel berikut menunjukkan hasil pengukuran performa sesudah dilakukan optimisasi query Eloquent dan eager loading (pada kondisi basis data penuh yang sama):

| Halaman | Jumlah query | Waktu (ms) | Memori |
|---------|-------------:|-----------:|-------:|
| Daftar mata kuliah (Dosen) |  |  |  |
| Daftar mata kuliah (Mahasiswa) |  |  |  |
| Detail mata kuliah (Dosen) |  |  |  |
| Detail mata kuliah (Mahasiswa) |  |  |  |
| Daftar tugas (Dosen) |  |  |  |
| Daftar tugas (Mahasiswa) | |  |  |
| Daftar pengumpulan (dosen) | |  |  |
| **Rekap nilai** (Dosen) |  |  |  |
| **Rekap nilai** (Mahasiswa) | |  |  |
| Dashboard mahasiswa |  |  |  |
| Dashboard dosen |  |  |  |

---

## 6. Ringkasan Perbandingan & Peningkatan Performa

| Halaman | Perubahan Query | Persentase Efisiensi Query | Dampak Waktu & Status |
| :--- | :---: | :---: | :--- |
| **Daftar tugas (Dosen)** |  |  | |
| **Daftar tugas (Mahasiswa)** |  |  |  |
| **Dashboard dosen** |  |  |  |
| **Dashboard mahasiswa** |  |  |  |
| **Halaman Lainnya** |  |  | |

---

## 7. Verifikasi Pengujian

* **Kriteria Selesai**: Seluruh halaman utama dapat dibuka oleh Dosen maupun Mahasiswa tanpa memicu `LazyLoadingViolationException` (semua mengembalikan status HTTP 200 OK).
* **Automated Unit & Feature Tests**: Seluruh test suite dijalankan via `php artisan test` dengan hasil **46 passed (159 assertions)** pada mode `testing` dengan `Model::preventLazyLoading()` aktif.
