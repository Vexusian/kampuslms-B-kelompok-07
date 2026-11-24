# Catatan Praktikum Modul Minggu 14 — Debugging dan Testing

- **Nama:** Tresia Uyang
- **NIM:** 10241072
- **Kelas / Kelompok:** Pemrograman Web (Kelas B - Kelompok 07)



## READ — Baca kegagalan (30 menit)

### 1. Eksekusi Seluruh Test Suite (`php artisan test`)
* **Hasil Pengujian:**  
  Sistem mengeksekusi suite pengujian otomatis di atas basis data in-memory SQLite (`:memory:`). Seluruh test lolos dengan rincian:
  * **Total Test:** 76 test (75 Feature Test dan 1 Unit Test)
  * **Total Assertions:** 246+ assertions
  * **Status:** **PASS (100% Berhasil)**
* **Kelompok Pengujian yang Berjalan:**
  1. `Tests\Feature\Minggu14FeatureTest` (22 test — alur kritis Modul 14: auth, CRUD, enrollment, materi, tugas, unduhan IDOR, penilaian, dan notifikasi)
  2. `Tests\Feature\DataExchangeTest` (8 test — ekspor/impor mahasiswa & nilai dosen)
  3. `Tests\Feature\Minggu5Test` (6 test — proteksi middleware role, IDOR submission, custom 403)
  4. `Tests\Feature\Minggu6ApiTest` (12 test — autentikasi Sanctum, throttling, role scoping, CRUD assignment)
  5. `Tests\Feature\Minggu7AuthTest` (16 test — login web, regenerasi session, CRUD otorisasi mata kuliah, IDOR materi & tugas)
  6. `Tests\Feature\NotificationWorkflowTest` (7 test — event `AssignmentPublished`, listener antrean, notifikasi penilaian tugas)
  7. `Tests\Feature\ExampleTest` & `Tests\Unit\ExampleTest` (5 test — response dasar & render dashboard)


### 2. Eksperimen Sengaja Menggagalkan Test & Membaca Stack Trace
* **Tindakan:** Mengubah `tests/Unit/ExampleTest.php` baris 14 dari `$this->assertTrue(true)` menjadi `$this->assertTrue(false)`.
* **Keluaran Terminal:**
  ```text
  FAIL  Tests\Unit\ExampleTest
  ⨯ that true is true

  FAILED  Tests\Unit\ExampleTest > that true is true
  Failed asserting that false is true.

  at tests\Unit\ExampleTest.php:14
     12|     public function test_that_true_is_true(): void
     13|     {
  ➜  14|         $this->assertTrue(false);
     15|     }
  ```
 
 > Keluaran pengujian Laravel (Collision) secara akurat menunjuk lokasi baris kegagalan pertama di kode aplikasi kita (`tests/Unit/ExampleTest.php:14`), bukan menumpuk baris internal framework di `vendor/`. Ini membuktikan bahwa saat mendiagnosis kegagalan test, fokus pembacaan harus selalu diarahkan ke berkas kode milik sendiri.



### 3. Analisis Exception pada Berkas Log (`storage/logs/laravel.log`)

  Saat ekstensi driver SQLite sempat nonaktif di lingkungan lokal, Laravel mencatat riwayat kegagalan lengkap:
  ```text
  [2026-11-23 05:48:58] testing.ERROR: could not find driver (Connection: sqlite, Database: :memory:, SQL: select exists (select 1 from "main".sqlite_master where name = 'migrations' and type = 'table') as "exists") {"exception":"[object] (Illuminate\\Database\\QueryException(code: 0): could not find driver ..."}
  ```
 
  Pada lingkungan produksi dengan `APP_DEBUG=false`, layar pengguna hanya menampilkan HTTP 500 generik tanpa membocorkan kredensial. Log di `storage/logs/laravel.log` adalah sumber kebenaran tunggal untuk menelusuri pesan error asli dan baris awal eksepsi tanpa perlu menyalakan debug.

---

### 4. Monitoring Query SQL dengan `DB::listen()`
Memasang listener `DB::listen` pada `app/Providers/AppServiceProvider.php`:
  ```php
  \Illuminate\Support\Facades\DB::listen(function ($query) {
      \Illuminate\Support\Facades\Log::info("SQL Query: {$query->sql} [Time: {$query->time}ms]");
  });
  ```
 
  Setiap query SQL terekam secara terperinci di log beserta nilai parameter (*bindings*) dan durasi eksekusinya (`ms`). Hal ini mempermudah identifikasi masalah query duplikat ($N+1$) dan kueri berat yang membutuhkan indeks performa.


## 2. BUILD — Test suite KampusLMS

### 1. **Pemenuhan Syarat Jumlah Test Suite (Minimal 20 Feature Test):**  
   Implementasi kode suite pengujian baru **`tests/Feature/Minggu14FeatureTest.php`** yang memuat **22 feature test**. Seluruh test mencakup 8 kriteria spesifikasi Modul 14:
   - *Autentikasi:* Login berhasil, gagal, proteksi guest, logout, dan throttling 5 kali percobaan.
   - *CRUD Mata Kuliah:* Sukses oleh admin, ditolak untuk dosen/mahasiswa, update kelas dosen sendiri vs kelas dosen lain.
   - *Enrollment:* Mahasiswa hanya melihat kelas terdaftar, dosen pengampu enroll mahasiswa.
   - *Unggah Materi:* Sukses oleh dosen pengampu, ditolak untuk dosen lain.
   - *Pengumpulan Tugas:* Sukses tepat waktu, ditolak setelah deadline jika `allow_late` false, ditolak untuk yang tidak terdaftar, dan pencegahan submit ganda.
   - *Unduhan Berkas Privat:* Unduhan materi dan unduhan submission ditolak untuk bukan pemilik (proteksi IDOR).
   - *Penilaian:* Sukses oleh dosen pengampu, ditolak untuk dosen lain.
   - *Notifikasi:* Event ter-dispatch saat tugas dibuat dan saat nilai diberikan.
   Kontribusi saya (Tresia) adalah melakukan verifikasi dan pengetesan lokal suite `Minggu14FeatureTest` (22 passed) bersama seluruh rangkaian test sebelumnya, sehingga total pengujian mencapai 76 test (100% hijau).

### 2. **Sinkronisasi Matriks Keamanan (`docs/keamanan.md`):**  
   Tabel 12 Titik Rawan IDOR telah diselaraskan dengan menautkan setiap endpoint ke method uji otomatis spesifik pada `tests/Feature/Minggu14FeatureTest.php` (seperti `test_unduhan_submission_ditolak_untuk_bukan_pemilik`, `test_crud_mata_kuliah_ditolak_untuk_dosen_dan_mahasiswa`, `test_penilaian_ditolak_untuk_dosen_lain`, dll) yang menjamin celah tersebut tertutup permanen.

### 3. **Pemanfaatan Fake Facade:**  
   * `Storage::fake('local')` digunakan pada pengujian upload & download materi dan submission di `Minggu14FeatureTest` (baris 154, 270, 291).
   * `Event::fake([AssignmentPublished::class])` dan `Event::fake([GradeSubmitted::class])` digunakan untuk menguji alur notifikasi tugas dan penilaian di `Minggu14FeatureTest` (baris 386, 404).

### 4. **Eksekusi Pengujian pada CI (GitHub Actions):**  
   Konfigurasi [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) telah diperbarui dengan ekstensi `sqlite, pdo_sqlite` dan langkah `php artisan test` untuk menjalankan seluruh 75 test suite secara otomatis pada setiap Pull Request.

### 5. **Dokumentasi Investigasi Bug Nyata (`docs/debugging.md`):**  
   Menyusun dokumentasi 3 kasus bug nyata (Missing SQLite Driver, Kerentanan IDOR Download Berkas, dan N+1 Lazy Loading) lengkap dengan metode persempitan masalah secara sistematis dan test otomatis pencegah regresinya yang merujuk pada `Minggu14FeatureTest`.

