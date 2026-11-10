# Dokumentasi Deployment & Bukti Pengujian Keamanan Produksi

Dokumen ini mencatat bukti implementasi deployment aplikasi **KampusLMS** di lingkungan produksi (Railway) dengan domain publik berprotokol HTTPS, serta verifikasi daftar periksa keamanan (*production security checklist*) menggunakan keluaran perintah `curl` yang sesungguhnya.

---

## 1. Informasi & Spesifikasi Server Produksi

* **Nama Proyek:** KampusLMS (Kelas B - Kelompok 07)
* **Penyedia Layanan (PaaS/Cloud):** Railway Cloud Infrastructure
* **URL Domain Publik (HTTPS):** [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app)
* **Protokol:** HTTPS (Terenkripsi dengan Sertifikat SSL/TLS Otomatis)
* **Lingkungan (*Environment*):** `APP_ENV=production`, `APP_DEBUG=false`
* **Versi PHP:** PHP 8.4.27 (CLI & FPM) dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`
* **Web Server & Reverse Proxy:** `railway-hikari` (Edge Nginx Reverse Proxy with SSL Termination)
* **Basis Data:** MySQL 8.x Terkelola (*Managed Database Engine*)
* **Queue Connection:** Database (`QUEUE_CONNECTION=database`) diproses oleh daemon queue worker

Tautan aplikasi live ini juga telah dicantumkan di bagian paling atas berkas [`README.md`](../README.md) agar mudah ditemukan dan diakses oleh penguji.

---

## 2. Prosedur Deployment dari Nol (Langkah Demi Langkah)

Berikut adalah panduan lengkap menyiapkan lingkungan produksi dari awal (*from scratch*):

1. **Inisialisasi Project di Railway / Server:**
   * Buat service baru yang terhubung ke repositori GitHub `kampuslms-B-kelompok-07`.
   * Lampirkan add-on database MySQL di project yang sama.
2. **Konfigurasi Variabel Lingkungan (*Environment Variables*):**
   Tambahkan variabel lingkungan produksi wajib berikut:
   ```env
   APP_NAME="KampusLMS"
   APP_ENV=production
   APP_KEY=base64:... (dihasilkan melalui php artisan key:generate)
   APP_DEBUG=false
   APP_URL=https://kampuslms-b-kelompok-07-production.up.railway.app

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   ```
3. **Penyusunan Perintah Build & Start (*Build & Start Command*):**
   * **Build Command:**
     ```bash
     composer install --no-dev --optimize-autoloader && npm ci && npm run build
     ```
   * **Start / Release Command:**
     ```bash
     php artisan migrate --force && php artisan optimize && php artisan db:seed --force
     ```
4. **Verifikasi Awal:**
   Buka URL publik, pastikan respon HTTP mengembalikan status `200 OK` dan tidak ada error 500.

---

## 3. Cara Deployment Ulang (Redeployment) & Penggunaan `deploy.sh`

Untuk pembaruan rutin kode aplikasi pada server produksi, repositori telah dilengkapi skrip [`deploy.sh`](../deploy.sh) yang teruji:

### Perintah Eksekusi:
```bash
chmod +x deploy.sh
./deploy.sh
```

### Urutan Logika Skrip `deploy.sh`:
1. `set -e` — Memastikan skrip berhenti seketika bila ada satu perintah yang gagal.
2. `php artisan down` — Mengaktifkan mode maintenance agar pengguna tidak mengalami error saat file diperbarui.
3. `git pull origin main` — Menarik pembaruan kode terbaru.
4. `composer install --no-dev --optimize-autoloader` — Menginstal pustaka PHP produksi.
5. `php artisan migrate --force` — Memperbarui skema database bila ada migrasi baru.
6. `php artisan optimize:clear && php artisan optimize` — Menghapus cache lama dan membuat ulang cache `config`, `routes`, serta `views`.
7. `php artisan queue:restart` — **Wajib!** Merestart proses daemon worker antrean agar membaca kode PHP baru di memori RAM.
8. `php artisan about` — Verifikasi kesehatan internal framework sebelum aplikasi dibuka kembali.
9. `php artisan up` — Menonaktifkan mode maintenance sehingga aplikasi live kembali.

---

## 4. Panduan Membaca Log Produksi & Diagnosis Masalah (Tanpa `APP_DEBUG`)

Pada lingkungan produksi dengan `APP_DEBUG=false`, error 500 sengaja disembunyikan dari layar pengguna agar kode sumber dan kredensial tidak bocor. Berikut adalah panduan membaca log untuk mendiagnosis masalah:

### A. Lokasi Berkas Log Laravel
Seluruh *stack trace* dan pesan pengecualian (*exceptions*) tercatat secara otomatis di:
```bash
storage/logs/laravel.log
```

### B. Perintah Memeriksa Log di Server
1. **Melihat 50 baris error terakhir secara realtime:**
   ```bash
   tail -n 50 -f storage/logs/laravel.log
   ```
2. **Mencari pesan error spesifik:**
   ```bash
   grep -i "error" storage/logs/laravel.log | tail -n 20
   ```
3. **Memantau antrean / background job yang gagal:**
   ```bash
   php artisan queue:failed
   ```
4. **Melalui Antarmuka Dashboard Railway:**
   * Buka Dashboard Railway &rarr; Pilih service `kampuslms` &rarr; Tab **Deploy Logs** atau **Runtime Logs**.
   * Log runtime menampilkan seluruh error level PHP-FPM, Nginx, dan Laravel secara langsung.

---

## 5. Daftar Periksa Keamanan Produksi (Production Security Checklist)

Berikut adalah status pemenuhan daftar periksa keamanan produksi:

| No. | Parameter Keamanan | Kriteria Standar Produksi | Status | Bukti Verifikasi |
|:---:|:---|:---|:---:|:---|
| 1 | **Enkripsi Transit (HTTPS)** | Situs wajib diakses via HTTPS dengan sertifikat SSL/TLS valid. | **LULUS** | Respon header `HTTP/1.1 200 OK` via HTTPS. |
| 2 | **Proteksi Berkas Sensitif (`.env`)** | Akses langsung ke `/.env` wajib ditolak/tidak ditemukan (bukan 200 OK). | **LULUS** | Respon header `HTTP/1.1 404 Not Found`. |
| 3 | **Proteksi Direktori `.git`** | Direktori `.git` tidak boleh terekspos ke publik. | **LULUS** | Respon header `HTTP/1.1 404 Not Found`. |
| 4 | **Keamanan Cookie Sesi** | Cookie sesi wajib memiliki flag `HttpOnly` dan `SameSite`. | **LULUS** | Header `Set-Cookie` memuat `httponly; samesite=lax`. |
| 5 | **Mode Debug Dinonaktifkan** | `APP_DEBUG=false` untuk mencegah kebocoran stack trace error. | **LULUS** | Header `Cache-Control: no-cache, private` dan error disembunyikan. |

---

## 3. Bukti Pengujian Keamanan dengan `curl` (Keluaran Sesungguhnya)

### A. Pengujian Proteksi Berkas Lingkungan (`/.env`)

Pengujian dilakukan untuk membuktikan bahwa berkas rahasia `.env` (berisi database password, app key, dsb.) tidak dapat diakses atau diunduh oleh publik.

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/.env
```

**Keluaran Terminal Sesungguhnya:**
```http
HTTP/1.1 404 Not Found
Cache-Control: no-cache, private
Content-Type: text/html; charset=utf-8
Date: Tue, 10 Nov 2026 16:44:37 GMT
Server: railway-hikari
x-powered-by: PHP/8.4.27
x-railway-request-id: QGoLvrnvSwWEz4GHwUFZXw
x-hikari-trace: sin1.98a6
x-railway-edge: sin1
Connection: keep-alive
```

> **Analisis:** Server mengembalikan kode status **`HTTP/1.1 404 Not Found`**. Hal ini membuktikan bahwa web server (Nginx/Apache/Caddy di Railway) telah mengarahkan root direktori secara tepat ke folder `/public`, sehingga berkas `.env` yang berada di luar folder public terlindungi sepenuhnya dari akses publik.

---

### B. Pengujian Ketersediaan Aplikasi Live & Proteksi Header Sesi (`/`)

Pengujian dilakukan untuk memastikan domain produksi dapat diakses dengan protokol HTTPS yang aman dan mengembalikan header keamanan sesi yang sesuai.

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/
```

**Keluaran Terminal Sesungguhnya:**
```http
HTTP/1.1 200 OK
Cache-Control: no-cache, private
Content-Type: text/html; charset=utf-8
Date: Tue, 10 Nov 2026 16:44:56 GMT
Server: railway-hikari
Set-Cookie: XSRF-TOKEN=eyJpdiI6InhkMlI1OGZ6VDRkQ2lMa3MwN0EwOUE9PSIsInZhbHVlIjoiZy9DVXl2Wmlmb2NzM0k2MndSTGRUUnNBenlIOEhYa3BxUlJNSUo1UEdFdG1kWFc2by9xMHVqd2ZFYTZRWFd0c2p4QkdrOFN1bjB0dEIyZ0oxanJOTDFiN3dIUndHSnlCMnZnMVhMYW45TkdBZ2IzZjg2TnVUeElsa3lKRzZwQ2ciLCJtYWMiOiJmZTYxZGJhZDEwMWFlOTMwZTY1YzYxMzQ3OTRkOWU0MGY2ODdmMDU3MThiYjI3YmI3ZjA5MDQ2Yjk4YTM2ZTYyIiwidGFnIjoiIn0%3D; expires=Tue, 10 Nov 2026 18:44:56 GMT; Max-Age=7200; path=/; samesite=lax
Set-Cookie: kampuslms_session=eyJpdiI6IlRUUkFmSTFCWVg4UHpMV2I2VGdjY3c9PSIsInZhbHVlIjoiTFFVcnM4b1Jidkk2VytZR2ZvSjF3eWVUTytFUitiU3BpaFRudEY4dXdpaXdYdC8veXhyNHpxUllraVZrNVZjenRLa0JKT1hpci9NTmJiRCthU3ZDYW40WW9aTjNqL3k3Wnp3UTZybGVSSWtlbzc0RnBxZXcxZDVPdDkyQUMyZHEiLCJtYWMiOiJlN2U4NTJjZmU5OWVlMTJlMDRkM2FhNTY4MTQxYWUyYzExMWRhOGY3ODQ3OGE5M2ZhOGVkZGU2OTg5YTA4MzYwIiwidGFnIjoiIn0%3D; expires=Tue, 10 Nov 2026 18:44:56 GMT; Max-Age=7200; path=/; httponly; samesite=lax
vary: Accept-Encoding
x-powered-by: PHP/8.4.27
x-railway-request-id: -v7FFKq4TmOQKmtCjq4OvQ
x-hikari-trace: sin1.98a6
x-railway-edge: sin1
Connection: keep-alive
```

> **Analisis:** 
> 1. Status **`HTTP/1.1 200 OK`** menandakan bahwa aplikasi berhasil berjalan secara live (*healthy*).
> 2. Cookie `kampuslms_session` dilengkapi atribut keamanan `httponly` (mencegah pencurian session cookie melalui serangan Cross-Site Scripting / XSS) dan `samesite=lax` (memitigasi risiko Cross-Site Request Forgery / CSRF).

---

## 4. Verifikasi Fungsionalitas Akun Demo di Produksi (Milestone M4 - Poin 7)

Seluruh akun demo telah di-seed ke basis data produksi menggunakan `DatabaseSeeder` (`php artisan db:seed`) dan diverifikasi secara langsung pada domain publik [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app). Pengujian dilakukan untuk memastikan ketiga peran (*roles*) dapat melakukan autentikasi dan mengakses hak otorisasi masing-masing secara benar:

| Peran (*Role*) | Email Akun Demo | Kata Sandi | Status Login | Hasil Verifikasi Fungsionalitas |
|:---:|---|:---:|:---:|---|
| **Admin** | `admin@kampuslms.test` | `password` | **Berhasil (200 OK)** | Akses dashboard manajemen admin, mampu mengelola akun pengguna (*users*), serta memantau data master mata kuliah. |
| **Dosen** | `dosen@kampuslms.test` | `password` | **Berhasil (200 OK)** | Menampilkan kelas yang diampu (misal: Course seeder), melihat materi, membuat penugasan (*assignment*), dan mengakses daftar submission tugas mahasiswa untuk penilaian. |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `password` | **Berhasil (200 OK)** | Menampilkan mata kuliah yang diikuti (*enrolled courses*), mengakses dan mengunduh materi privat, serta membuka halaman pengumpulan tugas untuk submission. |

> **Catatan Pengujian:**  
> Ketiga peran diuji dengan alur login & logout lengkap pada browser produksi. Tidak ditemukan error 403 (*unauthorized/forbidden*) atau 500 (*internal server error*) pada alur kerja utama masing-masing peran.

---

## 5. Pengujian Lintas Perangkat / Uji Mobile (Milestone M4 - Poin 8)

Pengujian dilakukan dari berbagai perangkat *smartphone* milik anggota kelompok menggunakan jaringan seluler publik (di luar jaringan lokal pengembang) untuk membuktikan aksesibilitas publik dan responsivitas antarmuka (*mobile responsiveness*):

| No. | Penguji (Anggota) | Perangkat & Sistem Operasi | Peramban (*Browser*) | Fitur yang Diuji| Status & Hasil Pengamatan | Lampiran |
|:---:|---|---|---|---|---|---|
| 1 | **Vanessa Marie Tandiarru** | Apple iPhone 13 (iOS) | Safari Mobile | Halaman Login, Navigasi Menu, Tabel Tugas Mahasiswa | **LOLOS (Terverifikasi Screenshot)**: Layout proporsional, navbar terstruktur rapi, tabel tugas memuat status *"Sudah Dikumpulkan"*, tidak ada horizontal scroll bocor. | ![alt text](image.png) |
| 2 | **Wahyu Ramadan / Tim ** | Realme Note 60 (Android) | Google Chrome Mobile | Dashboard Dosen, 4 Kartu Metrik, Tabel Matkul Ampuan, Penugasan Terbaru | **LOLOS (Terverifikasi Screenshot)**: 4 kartu metrik (*Mata Kuliah: 2, Peserta: 24, Materi & Tugas: 15, Pengajar: 1*) tampil utuh, tombol *"Detail"* & *"Edit"* presisi, alur penugasan & *"Akses Cepat"* berjalan sempurna. | ![alt text](image-1.png) |
| 3 | **Tresia Uyang** | Xiaomi Redmi Note 12 (Android 13) | Google Chrome Mobile | Alur Mahasiswa, Detail Tugas & Form Upload Berkas | **LOLOS (Terverifikasi Screenshot)**: Tombol aksi mudah ditekan (*touch-friendly*), dialog pemilihan berkas (*file picker*) ponsel berfungsi lancar untuk unggahan tugas. |  ![alt text](image-3.png)|
| 4 | **Zalfa Putri Sopyandi** | Infinix Note 30 (Android 13) | Google Chrome Mobile | Responsivitas Halaman Detail Mata Kuliah & Feedback Nilai | **LOLOS (Terverifikasi Screenshot)**: Elemen alert, badges status tugas, dan feedback nilai tertata rapi sesuai *viewport* layar ponsel. | ![alt text](image-2.png) ![alt text](image-4.png) |

> **Analisis Pengujian Mobile:**  
> Aplikasi KampusLMS telah memenuhi standar *responsive web design* (RWD) menggunakan Tailwind CSS. Seluruh interaksi penting (login, navigasi menu, membaca materi, pengumpulan tugas) dapat dieksekusi dengan baik pada peramban seluler tanpa kerusakan komponen visual.

---

## 6. Kesimpulan & Status Pemenuhan Milestone M4

Seluruh kriteria deployment, audit keamanan, dan verifikasi akhir untuk Milestone M4 telah terpenuhi:
1. **Domain Publik & HTTPS Aktif:** Aplikasi aktif dan dapat diakses publik di [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app) dengan sertifikat SSL/TLS valid. Tautan telah dicantumkan di `README.md`.
2. **Daftar Periksa Keamanan Terpenuhi:** Berkas sensitif (`.env`, `.git`) terbukti tidak dapat diakses publik (`404 Not Found`), mode debug `APP_DEBUG=false`, dan cookie sesi terproteksi atribut `HttpOnly` serta `SameSite=lax`.
3. **Akun Demo Berfungsi Penuh (Poin 7):** Akun Admin, Dosen Demo, dan Mahasiswa Demo terbukti dapat digunakan untuk login dan mengoperasikan fitur sesuai perannya.
4. **Pengujian Lintas Perangkat Tuntas (Poin 8):** Aplikasi telah diuji dari *smartphone* anggota kelompok (iOS & Android) melalui jaringan publik dengan hasil tampilan responsif dan seluruh fungsi berjalan optimal.




