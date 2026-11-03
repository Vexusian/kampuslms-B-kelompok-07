# Dokumentasi Optimisasi & Pengukuran Performa KampusLMS

Dokumentasi ini mencatat hasil pengujian dan pengukuran performa aplikasi **KampusLMS** sebelum dan sesudah dilakukan optimisasi query Eloquent, penanganan masalah *N+1 query problem*, serta pengaktifan fitur pencegahan lazy loading (`Model::preventLazyLoading()`).

---

## 1. Lingkungan & Kondisi Basis Data Pengujian

Pengukuran dilakukan pada kondisi basis data **penuh** (*populated database*) setelah menjalankan seeder komprehensif (`DatabaseSeeder`), bukan dalam kondisi basis data kosong.

* **Jumlah Akun Pengguna (`users`)**: 35 pengguna (1 Admin, 3 Dosen pengampu, 30+ Mahasiswa, 1 Mahasiswa Demo).
* **Jumlah Mata Kuliah (`courses`)**: 5 mata kuliah aktif dan draft dengan relasi dosen pengampu.
* **Jumlah Penugasan (`assignments`)**: 25 tugas (terbagi dalam kategori aktif, lewat deadline/past, dan draft).
* **Jumlah Pengumpulan Tugas (`submissions`)**: 230 data submission mahasiswa lengkap dengan berkas dan catatan teks.
* **Jumlah Penilaian (`grades`)**: 140+ data nilai dengan skor dan umpan balik (*feedback*).
* **Stack**: PHP 8.5 / Laravel 12 / MySQL 8 (Laragon) / Windows.
* **Alat Pengukuran**: Profiling HTTP Kernel (`DB::getQueryLog()`, `hrtime()`, `memory_get_peak_usage()`, serta kesesuaian metrik Barryvdh Laravel Debugbar / Telescope).

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

### Mekanisme Kerja:
Ketika `Model::preventLazyLoading(true)` aktif, setiap kali relasi Eloquent diakses secara dinamis tanpa dilakukan *eager loading* terlebih dahulu (`with()` atau `load()`), Laravel akan langsung melempar exception:
`Illuminate\Database\Eloquent\LazyLoadingViolationException: Attempted to lazy load [relation] on model [App\Models\Model] but lazy loading is disabled.`

---

## 3. Masalah yang Ditemukan Sebelum Optimisasi

Saat `Model::preventLazyLoading(true)` pertama kali diaktifkan pada kondisi basis data penuh, ditemukan beberapa pelanggaran lazy loading dan inefisiensi query SQL:

1. **Daftar Tugas Dosen (`/dosen/courses/{course}/assignments`)**:
   * **N+1 Query**: Pada view `resources/views/assignments/index.blade.php`, setiap baris tugas memanggil `$assignment->submissions()->count()`, sehingga memicu kueri `COUNT(*)` terpisah untuk setiap tugas yang tampil (25+ query).
   * **Lazy Loading Violation**: Pengecekan otorisasi `@can('update', $assignment)` dan `@can('viewAny', [Submission::class, $assignment])` mengakses `$assignment->course`. Karena `course` tidak di-eager load oleh controller, Laravel langsung melempar `LazyLoadingViolationException` dan menyebabkan status **HTTP 500**.

2. **Daftar Tugas Mahasiswa (`/mahasiswa/courses/{course}/assignments`)**:
   * **N+1 Query**: Setiap baris tugas mengeksekusi `$assignment->submissions()->where('user_id', auth()->id())->first()` secara individual untuk mengecek status kumpul mahasiswa (mencapai 21 kueri).

3. **Detail Tugas (`/assignments/{assignment}`)**:
   * **Lazy Loading Violation**: Halaman detail memanggil `$assignment->course->lecturer->name`, `$assignment->course->students`, dan `$mySubmission->grade`. Karena controller hanya memuat `['course', 'submissions.student']`, relasi `grade`, `lecturer`, dan `students` mengalami lazy loading violation saat diakses mahasiswa/dosen.

4. **Navigasi Global (`components/layout.blade.php`)**:
   * Pengecekan lonceng notifikasi pada navbar memanggil `auth()->user()->unreadNotifications->count()` dua kali berturut-turut, memuat seluruh model notifikasi ke memori dan berisiko memicu lazy loading.

5. **Dashboard (`DashboardController.php`)**:
   * Menjalankan kueri berulang pada relasi mata kuliah dosen/mahasiswa (`$user->taughtCourses()->count()`, `where('status', 'active')->count()`, `where('status', 'draft')->count()`, `pluck('id')`, dan `get()`), yang sebenarnya bisa dioptimasi dalam sekali penarikan koleksi memori.

---

## 4. Solusi Optimisasi yang Diterapkan

1. **Eager Loading & `withCount` pada `AssignmentController::index()`**:
   ```php
   $user = auth()->user();

   $query = $course->assignments()
       ->with('course')
       ->withCount('submissions');

   if ($user && $user->role === 'mahasiswa') {
       $query->with(['submissions' => function ($q) use ($user) {
           $q->where('user_id', $user->id);
       }]);
   }

   $assignments = $query->latest()->paginate(15);
   ```
   Pada Blade, `$assignment->submissions()->count()` diganti dengan atribut virtual `$assignment->submissions_count`, dan status kumpul mahasiswa diambil dari koleksi eager-loaded `$assignment->submissions->first()`.

2. **Eager Loading Lengkap pada `AssignmentController::show()`**:
   ```php
   $assignment->load([
       'course.lecturer',
       'course.students',
       'submissions.student',
       'submissions.grade',
   ]);
   ```

3. **Pemuatan Aman pada `SubmissionController::index()`**:
   ```php
   $assignment->loadMissing('course');
   ```

4. **Optimisasi Agregasi pada `DashboardController::index()`**:
   Mengambil data kursus terkait dalam satu query koleksi yang telah di-eager load dengan dosen pengampu (`with('lecturer')`), kemudian menghitung statistik status secara in-memory menggunakan method collection Laravel.

5. **Optimisasi Notifikasi Navbar (`layout.blade.php`)**:
   Mengganti pemanggilan properti relasi dinamis dengan query agregat `auth()->user()->unreadNotifications()->count()` yang dihitung satu kali per rendering halaman.

---

## 5. Tabel Pengukuran Performa (Sebelum vs Sesudah)

Berikut adalah tabel pengukuran metrik performa untuk seluruh halaman utama aplikasi pada basis data penuh:

| Halaman yang Diukur | Hak Akses | Metrik | Sebelum Optimisasi | Sesudah Optimisasi | Keterangan & Dampak |
| :--- | :--- | :--- | :---: | :---: | :--- |
| **Daftar Mata Kuliah** (`/courses`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 10 query<br>163.09 ms<br>30.55 MB<br>**Tidak** | 10 query<br>160.91 ms<br>30.58 MB<br>**Tidak** | Stabil, kueri telah terskop dan `lecturer` sudah di-eager load sejak awal. |
| **Daftar Mata Kuliah** (`/courses`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 8 query<br>25.18 ms<br>30.69 MB<br>**Tidak** | 8 query<br>23.38 ms<br>30.73 MB<br>**Tidak** | Cepat dan efisien, hanya memuat mata kuliah yang di-enroll mahasiswa. |
| **Detail Mata Kuliah** (`/courses/{id}`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 11 query<br>42.70 ms<br>30.98 MB<br>**Tidak** | 11 query<br>45.38 ms<br>30.97 MB<br>**Tidak** | Memuat relasi dosen, materi, tugas, dan daftar peserta kelas. |
| **Detail Mata Kuliah** (`/courses/{id}`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 11 query<br>30.52 ms<br>31.06 MB<br>**Tidak** | 11 query<br>34.62 ms<br>31.05 MB<br>**Tidak** | Memuat materi dan penugasan kelas mahasiswa secara terproteksi. |
| **Daftar Tugas** (`courses/{id}/assignments`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | **33 query**<br>**2.976,47 ms**<br>37.18 MB<br><strong style="color:red;">YA (HTTP 500)</strong> | **9 query**<br>**42.23 ms**<br>31.22 MB<br><strong style="color:green;">Tidak (HTTP 200)</strong> | **Penurunan Query 72.7%**. Error Lazy Loading `[course]` berhasil diselesaikan dengan `with('course')` & `withCount('submissions')`. |
| **Daftar Tugas** (`courses/{id}/assignments`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | **21 query**<br>68.65 ms<br>37.18 MB<br>**Tidak** | **11 query**<br>**41.28 ms**<br>31.24 MB<br>**Tidak** | **Penurunan Query 47.6%**. Menghilangkan N+1 query pencarian submission per tugas. |
| **Detail Tugas** (`assignments/{id}`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 13 query<br>50.80 ms<br>37.18 MB<br>**Tidak** | 13 query<br>41.73 ms<br>31.51 MB<br>**Tidak** | Eager loading peserta, pengumpulan, dan nilai submission. |
| **Detail Tugas** (`assignments/{id}`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 12 query<br>**1.599,99 ms**<br>37.79 MB<br><strong style="color:red;">YA (HTTP 500)</strong> | 14 query<br>**38.33 ms**<br>31.71 MB<br><strong style="color:green;">Tidak (HTTP 200)</strong> | Error Lazy Loading `[grade]` pada submission mahasiswa terselesaikan dengan eager load `submissions.grade`. |
| **Daftar Pengumpulan / Submission** (`assignments/{id}/submissions`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 13 query<br>58.19 ms<br>31.46 MB<br>**Tidak** | 13 query<br>46.24 ms<br>31.95 MB<br>**Tidak** | Paginasi 15 submission dengan eager load data mahasiswa dan nilai per berkas. |
| **Detail Pengumpulan** (`submissions/{id}`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 12 query<br>40.76 ms<br>37.79 MB<br>**Tidak** | 12 query<br>30.87 ms<br>31.95 MB<br>**Tidak** | Memuat berkas submission, riwayat nilai, dan informasi tugas & mahasiswa. |
| **Rekap Nilai** (`courses/{id}/grades`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 13 query<br>65.03 ms<br>32.35 MB<br>**Tidak** | 13 query<br>74.35 ms<br>32.88 MB<br>**Tidak** | Menampilkan rekapitulasi seluruh nilai tugas dari puluhan mahasiswa kelas. |
| **Rekap Nilai** (`courses/{id}/grades`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | 13 query<br>39.44 ms<br>32.35 MB<br>**Tidak** | 13 query<br>43.36 ms<br>32.88 MB<br>**Tidak** | Tersaring khusus nilai milik mahasiswa yang bersangkutan (Anti-IDOR). |
| **Dashboard** (`/dashboard`) | Dosen | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | **17 query**<br>46.67 ms<br>37.17 MB<br>**Tidak** | **13 query**<br>**34.84 ms**<br>32.88 MB<br>**Tidak** | **Pengurangan 4 query** via optimasi agregasi in-memory collection. |
| **Dashboard** (`/dashboard`) | Mahasiswa | Query SQL<br>Waktu (ms)<br>Memori (MB)<br>Lazy Loading Error | **16 query**<br>44.53 ms<br>37.17 MB<br>**Tidak** | **13 query**<br>**31.72 ms**<br>32.88 MB<br>**Tidak** | **Pengurangan 3 query** via optimasi agregasi in-memory collection. |

---

## 6. Verifikasi & Pengujian Otomatis

Seluruh pengujian fungsional dan integritas otorisasi aplikasi diverifikasi melalui PHPUnit:
* **Perintah Uji**: `php artisan test`
* **Hasil**: **46 passed (159 assertions)**, 0 failed.
* **Konfirmasi Lazy Loading**: Semua pengujian dijalankan pada lingkungan `testing` di mana `Model::preventLazyLoading(true)` aktif. Seluruh alur (Login, Autentikasi, Course CRUD, Scope Bindings, IDOR Protection, Assignment, File Submission, dan Notifikasi) lulus 100% tanpa ada `LazyLoadingViolationException`.

---

## 7. Kesimpulan

1. Fitur `Model::preventLazyLoading(! app()->isProduction())` telah aktif secara penuh pada `AppServiceProvider.php`.
2. Seluruh halaman utama aplikasi dapat diakses dengan lancar tanpa error lazily loaded.
3. Masalah *N+1 query* pada modul penugasan berhasil dipangkas drastis (hingga penurunan kueri **72.7%** pada daftar tugas dosen dan **47.6%** pada daftar tugas mahasiswa), yang secara signifikan mempercepat respon server dan menghemat penggunaan sumber daya CPU/Memori.

