# Dokumentasi Studi Kasus Debugging & Uji Pencegahan Regresi — KampusLMS

Dokumen ini mencatat tiga studi kasus kesalahan (*bug*) nyata sepanjang semester ini, mengacu pada **Modul Minggu 14 (BUILD Poin 5)**. Setiap kasus mencatat gejala, metode persempitan masalah secara sistematis, akar penyebab, serta *automated feature test* yang memastikan masalah tersebut tidak pernah kembali (*regression testing*).

---

## Studi Kasus 1: Ketiadaan Driver SQLite pada Pengujian Otomatis (`could not find driver`)

* **Gejala:**  
  Saat tim menjalankan perintah pengujian otomatis `php artisan test`, seluruh 53 *feature test* mengalami kegagalan serentak (*mass failure*) dengan pesan galat:  
  `QueryException: could not find driver (Connection: sqlite, Database: :memory:)`. Hanya 1 pengujian unit murni yang lolos.
* **Metode Persempitan Masalah (Penyempitan Sistematis):**
  1. *Reproduksi:* Menjalankan ulang perintah `php artisan test --filter=ExampleTest` untuk mengisolasi apakah masalah terjadi pada semua test atau hanya test tertentu.
  2. *Analisis Konfigurasi:* Memeriksa berkas `phpunit.xml` pada baris `<env name="DB_CONNECTION" value="sqlite"/>` dan `<env name="DB_DATABASE" value=":memory:"/>`. Konfigurasi ini mensyaratkan driver PDO SQLite aktif di runtime PHP.
  3. *Pemeriksaan Lingkungan PHP:* Menjalankan `php -m` pada terminal untuk memeriksa daftar ekstensi PHP yang termuat. Ditemukan bahwa ekstensi `pdo_sqlite` dan `sqlite3` tidak tercantum di dalam daftar modul aktif PHP Laragon.
* **Akar Masalah (*Root Cause*):**  
  Ekstensi `extension=pdo_sqlite` pada berkas konfigurasi `php.ini` di lingkungan lokal Laragon masih dalam status dinonaktifkan (dikomentari dengan tanda titik koma `;`).
* **Solusi & Tindakan:**  
  Mengaktifkan modul `pdo_sqlite` dan `sqlite3` melalui menu *Quick Settings* Laragon (atau menghapus tanda `;` di `php.ini`), kemudian merestart sesi terminal.
* **Test Pencegah Regresi (*Automated Test*):**  
  Seluruh test suite sekarang berjalan menggunakan `Illuminate\Foundation\Testing\RefreshDatabase` di atas SQLite `:memory:`. Jika driver SQLite atau migrasi database rusak, *pipeline* CI di GitHub Actions (`.github/workflows/ci.yml`) akan langsung memblokir PR dan menolak integrasi kode baru.

---

## Studi Kasus 2: Celah Keamanan IDOR pada Pengunduhan Berkas Tugas (`GET /submissions/{submission}`)

* **Gejala:**  
  Mahasiswa A dapat mengunduh berkas jawaban tugas mahasiswa B hanya dengan mengubah ID submission pada tautan unduhan, meskipun mahasiswa A tidak memiliki hubungan apa pun dengan tugas tersebut.
* **Metode Persempitan Masalah (Penyempitan Sistematis):**
  1. *Reproduksi:* Menembak endpoint menggunakan `curl` dengan cookie autentikasi Mahasiswa A:  
     `curl -i -b "kampuslms_session=..." http://localhost:8000/submissions/99/download`
  2. *Penelusuran Kode:* Memeriksa method `download()` pada `app/Http/Controllers/SubmissionController.php`. Ditemukan bahwa controller langsung memanggil `Storage::download($submission->file_path)` tanpa memanggil otorisasi.
  3. *Pemeriksaan Policy:* Memeriksa `app/Policies/SubmissionPolicy.php` method `view()`. Aturan otorisasi sudah ada, tetapi belum dipanggil di controller.
* **Akar Masalah (*Root Cause*):**  
  Ketiadaan pemanggilan baris otorisasi `Gate::authorize('view', $submission)` pada method `download()` di controller.
* **Solusi & Tindakan:**  
  Menambahkan pemanggilan eksplisit `Gate::authorize('view', $submission)` sebelum proses penyerahan berkas ke pengguna, sehingga pengguna luar kelas langsung dihempas dengan respons `HTTP 403 Forbidden`.
* **Test Pencegah Regresi (*Automated Test*):**  
  Dibuatkan feature test otomatis di berkas `tests/Feature/Minggu14FeatureTest.php` pada method `test_unduhan_submission_ditolak_untuk_bukan_pemilik()`:
  ```php
  public function test_unduhan_submission_ditolak_untuk_bukan_pemilik(): void
  {
      Storage::fake('local');
      $dosen = User::factory()->dosen()->create();
      $studentOwner = User::factory()->mahasiswa()->create();
      $studentOther = User::factory()->mahasiswa()->create();
      $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
      $course->students()->attach([$studentOwner->id, $studentOther->id], ['enrolled_at' => now()]);

      $assignment = Assignment::factory()->create(['course_id' => $course->id]);
      $filePath = "submissions/{$assignment->id}/jawaban.pdf";
      Storage::disk('local')->put($filePath, 'konten tugas privat');

      $submission = Submission::create([
          'assignment_id' => $assignment->id,
          'user_id' => $studentOwner->id,
          'file_path' => $filePath,
          'original_filename' => 'jawaban.pdf',
          'submitted_at' => now(),
      ]);

      // Mahasiswa lain mencoba mengunduh submission milik studentOwner -> Wajib 403 Forbidden
      $response = $this->actingAs($studentOther)->get("/submissions/{$submission->id}/download");
      $response->assertStatus(403);
  }
  ```

---

## Studi Kasus 3: Peringatan N+1 Query & Lazy Loading Violation pada Dashboard Mata Kuliah

* **Gejala:**  
  Halaman dashboard dosen dan mahasiswa mengalami pelambatan waktu muat dan melempar eksepsi `Illuminate\Database\LazyLoadingViolationException` pada mode pengembangan lokal.
* **Metode Persempitan Masalah (Penyempitan Sistematis):**
  1. *Reproduksi:* Mengakses halaman `/dashboard` setelah mengaktifkan `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider.php`.
  2. *Inspeksi Query SQL:* Memasang listener `DB::listen()` di `AppServiceProvider.php` untuk mencatat query yang berjalan ke `storage/logs/laravel.log`. Ditemukan query `SELECT * FROM users WHERE id = ?` tereksekusi berulang-ulang sebanyak jumlah mata kuliah yang tampil di halaman.
  3. *Penelusuran Template:* Memeriksa `resources/views/dashboard.blade.php`. Ditemukan pemanggilan `$course->lecturer->name` dan `$course->students->count()` di dalam perulangan `@foreach`.
* **Akar Masalah (*Root Cause*):**  
  Controller mengambil data mata kuliah menggunakan `Course::get()` tanpa menggunakan teknik *Eager Loading* (`with()`), sehingga setiap iterasi baris memicu query SQL terpisah ke tabel relasi (masalah klasik $N+1$).
* **Solusi & Tindakan:**  
  Memodifikasi query di `DashboardController.php` dan `CourseController.php` dengan menambahkan eager loading dan penghitungan agregat langsung di database:
  ```php
  Course::with(['lecturer'])->withCount('students')->get();
  ```
* **Test Pencegah Regresi (*Automated Test*):**  
  Diverifikasi secara otomatis melalui feature test `test_dashboard_renders_course_data_properly` pada `tests/Feature/ExampleTest.php` dan `test_courses_index_scoped_by_role_for_student` pada `tests/Feature/Minggu7AuthTest.php` yang memverifikasi bahwa data mata kuliah berhasil dirender tanpa memicu pelanggaran lazy loading.
