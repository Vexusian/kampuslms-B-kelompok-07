# Kampus LMS

## Nama Proyek

**Kampus LMS**

Kampus LMS merupakan sistem manajemen pembelajaran (*Learning Management System*) yang dikembangkan menggunakan **Laravel 12.x**. Sistem ini memfasilitasi interaksi antara dosen dan mahasiswa dalam pengelolaan mata kuliah, materi, penugasan, pengumpulan berkas tugas privat, penilaian, serta sistem notifikasi asinkron berbasis event dan antrean (*queue*).

---

## Anggota Kelompok

| No. | Nama | NIM | Peran |
|---|---|---|---|
| 1 | Tresia Uyang | 10241072 | UI/UX |
| 2 | Vanessa Marie Tandiarru | 10251118 | Frontend |
| 3 | Wahyu Ramadan | 10241074 | Backend |
| 4 | Zalfa Putri Sopyandi | 10241076 | Database |

---

## Kebutuhan Sistem (System Requirements)

- **PHP**: ^8.2 atau 8.3+
- **Composer**: ^2.x
- **Basis Data**: MySQL 8.x / MariaDB 11.x (atau SQLite untuk testing)
- **Node.js & npm**: Node 18+ & npm 9+
- **Ekstensi PHP**: `pdo`, `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `curl`

---

## Cara Instalasi & Menjalankan Proyek

1. **Clone Repository:**
   ```bash
   git clone https://github.com/Vexusian/kampuslms-B-kelompok-07.git
   cd kampuslms-B-kelompok-07
   ```

2. **Pasang Dependensi PHP & Frontend:**
   ```bash
   composer install
   npm install
   ```

3. **Salin Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Basis Data & Queue di `.env`:**
   Sesuaikan kredensial database dan pastikan koneksi queue disetel ke `database`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=kampuslms
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=database
   ```

5. **Jalankan Migrasi & Seeder Database:**
   ```bash
   php artisan migrate
   # (Opsional) jika ingin mengisi dummy data:
   # php artisan db:seed
   ```

6. **Tautkan Storage (Symlink Berkas Publik):**
   ```bash
   php artisan storage:link
   ```

7. **Jalankan Aplikasi & Asset Bundler:**
   - Jalankan web server Laravel:
     ```bash
     php artisan serve
     ```
   - Jalankan Vite (di terminal terpisah):
     ```bash
     npm run dev
     ```

---

## Konfigurasi Antrean (Queue) & Background Worker

LMS ini menggunakan arsitektur **Event-Driven Architecture** yang dipadukan dengan **Laravel Database Queue Worker**. Hal ini memastikan pengiriman notifikasi massal (misalnya ke puluhan mahasiswa di dalam satu mata kuliah) berjalan di latar belakang (*background*) tanpa membebani atau memperlambat respon HTTP (*HTTP response time*) bagi dosen.

### 1. Pengaturan `.env`
Koneksi antrean diatur pada berkas `.env`:
```env
QUEUE_CONNECTION=database
```
Nilai ini memberitahu Laravel untuk menyimpan antrean job ke tabel `jobs` di basis data MySQL.

### 2. Tabel Antrean (`jobs`)
Tabel antrean telah disediakan melalui migration resmi Laravel:
```bash
# Membuat migrasi tabel jobs (jika memulai dari scratch):
php artisan queue:table

# Menjalankan migrasi untuk membuat tabel 'jobs', 'failed_jobs', dan 'job_batches':
php artisan migrate
```

### 3. Implementasi `ShouldQueue` pada Komponen Notifikasi
Seluruh listener dan notifikasi mengimplementasikan kontrak `Illuminate\Contracts\Queue\ShouldQueue`:

- **Peristiwa A: Tugas Baru Dipublikasikan**
  - **Event**: `App\Events\AssignmentPublished`
  - **Listener**: `App\Listeners\SendAssignmentPublishedNotification implements ShouldQueue`
  - **Notification**: `App\Notifications\NewAssignmentNotification implements ShouldQueue`
  - **Mekanisme**: Saat dosen mempublikasikan tugas baru, listener dieksekusi secara asinkron di antrean untuk mengambil seluruh mahasiswa terdaftar dan mengirim `NewAssignmentNotification` ke database mereka.

- **Peristiwa B: Nilai Diberikan**
  - **Event**: `App\Events\GradeSubmitted`
  - **Listener**: `App\Listeners\SendGradeSubmittedNotification implements ShouldQueue`
  - **Notification**: `App\Notifications\GradeSubmittedNotification implements ShouldQueue`
  - **Mekanisme**: Saat dosen menyimpan nilai dan umpan balik, listener berjalan di background untuk mengirimkan notifikasi khusus ke mahasiswa pemilik tugas.

- **Ketahanan & Retry Policy**:
  - `public int $tries = 3;` (Job akan dicoba maksimal 3 kali jika terjadi kegagalan jaringan/database).
  - `backoff(): array [10, 30, 60]` (Jeda percobaan ulang eksponensial: 10s, 30s, 60s).
  - `failed()` method mencatat kegagalan permanen ke berkas `storage/logs/laravel.log`.

---

### 4. Cara Menjalankan Background Worker

Agar antrean yang masuk ke tabel `jobs` dapat segera diproses dan notifikasi terkirim, jalankan background worker pada terminal terpisah:

#### A. Mode Standar (Production & Uji Coba)
Menjalankan worker antrean secara berkelanjutan:
```bash
php artisan queue:work
```

#### B. Mode Development / Hot-Reload (Sangat Disarankan saat Koding)
Memuat ulang kode PHP secara otomatis saat Anda mengubah file tanpa perlu me-restart worker:
```bash
php artisan queue:listen
```

#### C. Menjalankan Worker dengan Opsi Spesifik
Menentukan batas percobaan ulang (*tries*), batas waktu timeout (*timeout*), dan koneksi queue:
```bash
php artisan queue:work database --queue=default --tries=3 --timeout=60
```

#### D. Memproses Satu Job Saja (Untuk Debugging/Testing Manual)
```bash
php artisan queue:work --once
```

---

### 5. Manajemen Job Gagal (*Failed Jobs*)

Jika terjadi kendala eksternal yang menyebabkan job gagal setelah 3 kali percobaan:

- **Melihat daftar job yang gagal:**
  ```bash
  php artisan queue:failed
  ```
- **Menjalankan ulang seluruh job yang gagal:**
  ```bash
  php artisan queue:retry all
  ```
- **Menjalankan ulang satu job tertentu berdasarkan ID/UUID:**
  ```bash
  php artisan queue:retry <id_atau_uuid>
  ```
- **Membersihkan / menghapus seluruh riwayat job yang gagal:**
  ```bash
  php artisan queue:flush
  ```

---

## Menjalankan Pengujian Otomatis (Testing)

Proyek ini dilengkapi dengan unit test dan feature test otomatis untuk memverifikasi autentikasi, otorisasi IDOR, pengunggahan berkas privat, serta alur Event, Listener, dan Notification:

```bash
# Menjalankan seluruh pengujian (Feature & Unit)
php artisan test

# Menjalankan pengujian khusus alur Event, Listener, dan Notifikasi
php artisan test --filter=NotificationWorkflowTest
```

---

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
