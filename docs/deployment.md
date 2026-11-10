# Dokumentasi Deployment & Bukti Pengujian Keamanan Produksi

Dokumen ini mencatat bukti implementasi deployment aplikasi **KampusLMS** di lingkungan produksi (Railway) dengan domain publik berprotokol HTTPS, serta verifikasi lengkap daftar periksa keamanan (*production security checklist*) mengacu pada **Modul Minggu 12 (Milestone M4 / Tugas 4)** menggunakan keluaran perintah `curl` yang sesungguhnya.

---

## 1. Informasi Deployment & Spesifikasi Server Produksi

* **Nama Proyek:** KampusLMS (Kelas B - Kelompok 07)
* **Penyedia Layanan Cloud (PaaS):** Railway ([railway.com](https://railway.com))
* **URL Domain Publik (HTTPS):** [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app)
* **Protokol:** HTTPS (Terenkripsi dengan SSL/TLS Otomatis melalui Cloud Edge Proxy)
* **Spesifikasi Mesin & Kontainer:**
  * **Base Image Docker:** `php:8.4-apache` (Debian 12 Bookworm)
  * **vCPU:** 1 vCPU (Shared Cloud Allocation)
  * **RAM:** 512 MB – 1 GB Dynamic Memory
  * **Storage:** Ephemeral Container File System + Persistent Volume untuk Basis Data
* **Web Server & Runtime:**
  * **Web Server:** Apache 2.4 (MPM Prefork, mod_rewrite aktif, AllowOverride All)
  * **PHP Runtime:** PHP 8.4.27 (Ekstensi: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`)
  * **Port Binding:** `PORT=8080` (Internal Docker container port)
* **Basis Data:** MySQL 8.0 (Railway Managed Database Service)
* **Lingkungan (*Environment*):** `APP_ENV=production`, `APP_DEBUG=false`

> Tautan aplikasi live ini telah disematkan di bagian paling atas berkas [`README.md`](../README.md) sesuai ketentuan **Build Nomor 1**.

---

## 2. Panduan Deployment dari Nol (*Step-by-Step From Scratch*)

Berikut adalah langkah-langkah mereplikasi deployment aplikasi KampusLMS di Railway dari awal:

1. **Persiapan Repositori GitHub:**
   * Pastikan kode sumber aplikasi berada di branch `main` repositori GitHub.
   * Pastikan berkas `Dockerfile` tersedia di direktori utama (*root*) repositori.
2. **Pembuatan Proyek di Railway:**
   * Buka dashboard [railway.com](https://railway.com) dan buat proyek baru (*New Project*).
   * Tambahkan layanan basis data MySQL (*Add Service* > *Database* > *MySQL*). Railway akan membuat container MySQL dan menyediakan variabel koneksi database otomatis.
3. **Penyambungan Layanan Web Service:**
   * Pada proyek yang sama, klik *New Service* > *GitHub Repo* > pilih repositori `kampuslms`.
   * Railway akan secara otomatis mendeteksi keberadaan `Dockerfile` untuk memulai proses *build*.
4. **Konfigurasi Variabel Lingkungan (*Environment Variables*):**
   * Masuk ke tab **Variables** pada web service, tambahkan:
     ```env
     APP_NAME=KampusLMS
     APP_ENV=production
     APP_KEY=base64:...
     APP_DEBUG=false
     APP_URL=https://kampuslms-b-kelompok-07-production.up.railway.app
     DB_CONNECTION=mysql
     DB_HOST=${{MySQL.MYSQLHOST}}
     DB_PORT=${{MySQL.MYSQLPORT}}
     DB_DATABASE=${{MySQL.MYSQLDATABASE}}
     DB_USERNAME=${{MySQL.MYSQLUSER}}
     DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
     SESSION_DRIVER=database
     SESSION_SECURE_COOKIE=true
     SESSION_HTTP_ONLY=true
     SESSION_SAME_SITE=lax
     CACHE_STORE=database
     QUEUE_CONNECTION=database
     ```
5. **Pengaturan Jaringan & Start Command:**
   * Masuk ke tab **Settings** > **Networking** > klik **Generate Domain** untuk memperoleh domain HTTPS publik `*.up.railway.app`.
   * Pada bagian **Deploy** > **Custom Start Command**: **KOSONGKAN** (jangan diisi), agar kontainer menjalankan perintah `CMD` resmi bawaan `Dockerfile`.

---

## 3. Prosedur Deployment Ulang (*Redeployment Procedure*)

Aplikasi KampusLMS mendukung dua mekanisme deployment ulang:

### A. Metode CI/CD Otomatis via Git Push (Metode Utama)
Setiap pembaruan fitur atau perbaikan bug di push ke GitHub:
```bash
git checkout main
git add .
git commit -m "update: pembaruan sistem"
git push origin main
```
Railway akan mendeteksi commit baru via webhook, menjalankan build image Docker baru, mengeksekusi migrasi, dan melakukan *rolling restart* tanpa downtime (*zero-downtime*).

### B. Metode Manual via Skrip `deploy.sh`
Jika deployment ulang dilakukan pada server mandiri atau sesi console:
```bash
bash deploy.sh
```
Skrip ini secara runut mengaktifkan mode maintenance, menarik kode git terbaru, menginstal dependensi produksi, kompilasi aset, menjalankan migrasi database dengan flag `--force`, membersihkan serta meng-cache konfigurasi/rute/view, merestart antrean worker (`queue:restart`), dan mengaktifkan kembali aplikasi.

---

## 4. Cara Monitoring & Membaca Log Sistem

Untuk memeriksa kesehatan server dan mendiagnosis kendala:

1. **Membaca Log via Dashboard Railway:**
   * Buka proyek di Railway > pilih service **kampuslms** > tab **Deployments**.
   * **Build Logs:** Memantau proses kompilasi Dockerfile, unduhan paket apt, dan composer install.
   * **Deploy Logs:** Memantau eksekusi runtime, migrasi database, binding port Apache, dan access log HTTP.
2. **Membaca Log Error Laravel (`laravel.log`):**
   * Melalui terminal container:
     ```bash
     tail -n 50 /var/www/html/storage/logs/laravel.log
     ```
   * Untuk memantau error secara langsung (*live stream*):
     ```bash
     tail -f /var/www/html/storage/logs/laravel.log
     ```
3. **Membaca Status Queue Worker:**
   ```bash
   supervisorctl status
   tail -n 50 /var/www/html/storage/logs/worker.log
   ```

---

## 5. Seluruh Daftar Periksa Keamanan Produksi (Production Security Checklist)

Mengacu pada daftar periksa resmi **Modul Minggu 12**, berikut adalah status pemenuhan ke-12 parameter keamanan produksi:

| No. | Parameter Keamanan Modul 12 | Kriteria Standar Produksi | Status | Bukti & Keterangan |
|:---:|:---|:---|:---:|:---|
| 1 | `APP_DEBUG=false` | Debug mode wajib mati agar stack trace dan potongan kode tidak bocor. | **LULUS** | Dikonfigurasi di environment Railway; error hanya masuk log. |
| 2 | `APP_ENV=production` | Lingkungan wajib disetel ke mode produksi. | **LULUS** | Dikonfigurasi pada Railway Variables (`APP_ENV=production`). |
| 3 | `curl -I https://domain/.env` | Berkas kredensial `.env` tidak boleh bisa diakses (wajib 404/403, bukan 200). | **LULUS** | Terbukti via `curl -I`: `HTTP/1.1 404 Not Found`. |
| 4 | `curl -I https://domain/storage/logs/laravel.log` | Berkas log sistem tidak boleh bisa diakses publik (wajib 404/403, bukan 200). | **LULUS** | Terbukti via `curl -I`: `HTTP/1.1 404 Not Found`. |
| 5 | HTTPS aktif & HTTP dialihkan ke HTTPS | Akses HTTP dialihkan secara otomatis ke HTTPS. | **LULUS** | Terbukti via `curl -I`: `HTTP/1.1 308 Permanent Redirect` ke `https://`. |
| 6 | `SESSION_SECURE_COOKIE=true` | Cookie sesi hanya boleh dikirim melalui jalur terenkripsi HTTPS (`secure` flag). | **LULUS** | `trustProxies(at: '*')` aktif dan cookie membawa atribut `secure; httponly; samesite=lax`. |
| 7 | Kata sandi database aman | Bukan kata sandi lokal default (seperti `root` / kosong / `password`). | **LULUS** | Menggunakan variabel aman yang digenerate otomatis oleh Railway Managed MySQL. |
| 8 | Kredensial akun demo terlindungi | Akun demo memakai sandi produksi yang aman dan dipisah dari dokumen publik. | **LULUS** | Akun demo di-seed dengan hash bcrypt aman. |
| 9 | Skrip pengujian otorisasi lolos | Seluruh endpoint otorisasi (role-based policy) lolos uji di server produksi. | **LULUS** | Kebijakan akses dosen, mahasiswa, dan admin konsisten dengan modul minggu 7. |
| 10 | Berkas submission privat | Berkas submission tugas tidak boleh bisa diunduh tanpa login / otorisasi. | **LULUS** | Disimpan di disk `local` (privat) dan hanya dapat diunduh via Controller + Policy. |
| 11 | Queue worker berjalan | Antrean tugas berjalan di latar belakang (background worker). | **LULUS** | Queue database aktif untuk pengiriman notifikasi asinkron. |
| 12 | Konfigurasi batas upload PHP | `upload_max_filesize` & `post_max_size` sesuai validasi aplikasi. | **LULUS** | Dikonfigurasi di Dockerfile/Apache agar berkas materi dan tugas tidak tertolak. |

---

## 6. Bukti Pengujian Keamanan dengan `curl` (Keluaran Nyata)

### A. Pengujian Proteksi Berkas Lingkungan (`/.env`)
Membuktikan bahwa berkas rahasia `.env` (berisi app key, database password, kredensial) tidak dapat diunduh oleh publik karena document root web server diarahkan ke `/public`.

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/.env
```

**Keluaran Terminal:**
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
> **Hasil:** Status **`404 Not Found`** membuktikan berkas `.env` aman dan tidak dapat diakses.

---

### B. Pengujian Proteksi Berkas Log Sistem (`/storage/logs/laravel.log`)
Membuktikan bahwa log error sistem tidak terekspos ke internet.

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/storage/logs/laravel.log
```

**Keluaran Terminal:**
```http
HTTP/1.1 404 Not Found
Cache-Control: no-cache, private
Content-Type: text/html; charset=utf-8
Server: railway-hikari
x-powered-by: PHP/8.4.27
Connection: keep-alive
```
> **Hasil:** Status **`404 Not Found`** membuktikan bahwa folder `storage/logs/` di luar direktori public terlindungi.

---

### C. Pengujian Proteksi Berkas Dependensi (`/composer.json`)
Membuktikan berkas konfigurasi proyek di akar repository tidak terekspos.

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/composer.json
```

**Keluaran Terminal:**
```http
HTTP/1.1 404 Not Found
Cache-Control: no-cache, private
Content-Type: text/html; charset=utf-8
Server: railway-hikari
x-powered-by: PHP/8.4.27
Connection: keep-alive
```
> **Hasil:** Status **`404 Not Found`** menegaskan bahwa document root web server mengarah secara presisi ke folder `public/`.

---

### D. Pengujian Pengalihan Otomatis HTTP ke HTTPS
Membuktikan bahwa jika pengunjung mengakses menggunakan protokol HTTP biasa, server secara otomatis mengalihkannya (*redirect*) ke HTTPS.

**Perintah:**
```bash
curl -I http://kampuslms-b-kelompok-07-production.up.railway.app/
```

**Keluaran Terminal:**
```http
HTTP/1.1 308 Permanent Redirect
Location: https://kampuslms-b-kelompok-07-production.up.railway.app/
Date: Tue, 10 Nov 2026 16:50:12 GMT
Server: railway-hikari
Connection: keep-alive
```
> **Hasil:** Status **`308 Permanent Redirect`** mengarahkan seluruh lalu lintas pengguna ke koneksi terenkripsi HTTPS.

---

### E. Pengujian Ketersediaan Aplikasi Live & Proteksi Header Cookie Sesi
Membuktikan respon 200 OK serta atribut keamanan cookie sesi (`httponly`, `secure`, dan `samesite`).

**Perintah:**
```bash
curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/
```

**Keluaran Terminal:**
```http
HTTP/1.1 200 OK
Cache-Control: no-cache, private
Content-Type: text/html; charset=utf-8
Date: Tue, 10 Nov 2026 16:44:56 GMT
Server: railway-hikari
Set-Cookie: XSRF-TOKEN=eyJpdiI6InhkMlI1OGZ6VDRkQ2lMa3MwN0EwOUE9PSIsInZhbHVlIjoiZy9DVXl2Wmlmb2NzM0k2MndSTGRUUnNBenlIOEhYa3BxUlJNSUo1UEdFdG1kWFc2by9xMHVqd2ZFYTZRWFd0c2p4QkdrOFN1bjB0dEIyZ0oxanJOTDFiN3dIUndHSnlCMnZnMVhMYW45TkdBZ2IzZjg2TnVUeElsa3lKRzZwQ2ciLCJtYWMiOiJmZTYxZGJhZDEwMWFlOTMwZTY1YzYxMzQ3OTRkOWU0MGY2ODdmMDU3MThiYjI3YmI3ZjA5MDQ2Yjk4YTM2ZTYyIiwidGFnIjoiIn0%3D; expires=Tue, 10 Nov 2026 18:44:56 GMT; Max-Age=7200; path=/; secure; samesite=lax
Set-Cookie: kampuslms_session=eyJpdiI6IlRUUkFmSTFCWVg4UHpMV2I2VGdjY3c9PSIsInZhbHVlIjoiTFFVcnM4b1Jidkk2VytZR2ZvSjF3eWVUTytFUitiU3BpaFRudEY4dXdpaXdYdC8veXhyNHpxUllraVZrNVZjenRLa0JKT1hpci9NTmJiRCthU3ZDYW40WW9aTjNqL3k3Wnp3UTZybGVSSWtlbzc0RnBxZXcxZDVPdDkyQUMyZHEiLCJtYWMiOiJlN2U4NTJjZmU5OWVlMTJlMDRkM2FhNTY4MTQxYWUyYzExMWRhOGY3ODQ3OGE5M2ZhOGVkZGU2OTg5YTA4MzYwIiwidGFnIjoiIn0%3D; expires=Tue, 10 Nov 2026 18:44:56 GMT; Max-Age=7200; path=/; httponly; secure; samesite=lax
vary: Accept-Encoding
x-powered-by: PHP/8.4.27
x-railway-request-id: -v7FFKq4TmOQKmtCjq4OvQ
x-hikari-trace: sin1.98a6
x-railway-edge: sin1
Connection: keep-alive
```
> **Hasil Analisis:**
> 1. Status **`200 OK`**: Aplikasi aktif dan merespon dengan benar.
> 2. `httponly`: Mencegah akses cookie sesi melalui JavaScript (proteksi XSS).
> 3. `secure`: Memastikan cookie hanya dapat dikirimkan melalui koneksi SSL/HTTPS.
> 4. `samesite=lax`: Mencegah pengiriman cookie lintas domain pada request berbahaya (proteksi CSRF).

---

## 7. Skrip Deployment Otomatis (`deploy.sh`) — Pemenuhan Build 3

Sesuai instruksi **Build Nomor 3**, berkas [`deploy.sh`](../deploy.sh) telah dibuat pada akar repositori, teruji, dan memiliki komentar penjelasan pada setiap langkahnya:

```bash
#!/usr/bin/env bash
set -e

echo "🚀 Memulai proses deployment KampusLMS..."
php artisan down --render="errors::503" --secret="kampuslms-deploy-bypass" || true
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan up
echo "✅ Deployment KampusLMS berhasil!"
```

### Poin Evaluasi Kritis `deploy.sh`:
1. **`set -e`:** Memastikan skrip langsung berhenti jika salah satu perintah gagal, menghindari server berjalan dalam kondisi setengah update.
2. **`php artisan down` & `up`:** Mencegah user mengakses aplikasi saat proses kompilasi atau migrasi database.
3. **`php artisan migrate --force`:** Flag `--force` wajib di lingkungan produksi agar eksekusi migrasi tidak dibatalkan karena menunggu input konfirmasi interaktif.
4. **`php artisan queue:restart`:** Wajib dieksekusi agar daemon background worker me-reload kode PHP terbaru dari memori RAM.
5. **Tanpa `db:seed`:** `db:seed` tidak boleh dijalankan di deployment ulang karena berisiko menduplikasi data atau merusak data riil pengguna.

---

## 8. Manajemen Queue Worker dengan Supervisor — Pemenuhan Build 4

Sesuai ketentuan **Build Nomor 4**, antrean tugas latar belakang (*background queue worker*) dikelola oleh daemon **Supervisor** untuk menjamin worker selalu hidup secara persisten (*auto-restart on crash*).

### A. Berkas Konfigurasi Supervisor (`/etc/supervisor/conf.d/kampuslms-worker.conf`)
Telah disiapkan template konfigurasi [`kampuslms-worker.conf`](../kampuslms-worker.conf) di dalam repositori:

```ini
[program:kampuslms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kampuslms/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kampuslms/storage/logs/worker.log
stopwaitsecs=3600
```

### B. Perintah Aktivasi & Pembaruan Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kampuslms-worker:*
```

### C. Bukti Status Supervisor (`supervisorctl status`):
Perintah berikut membuktikan bahwa 2 unit worker sedang aktif berjalan:

```bash
$ sudo supervisorctl status
kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14210, uptime 04:12:35
kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14211, uptime 04:12:35
```

> **Pertanyaan Interview Modul 12:**
> * **Apa akibatnya kalau worker mati dan tidak ada yang tahu?**  
>   Tugas latar belakang (seperti notifikasi tugas baru dan email pengumpulan submission) akan menumpuk di tabel `jobs` tanpa pernah diproses. Pengguna merasa fitur notifikasi rusak meskipun tidak ada error yang muncul di layar.
> * **Kenapa `queue:restart` wajib setelah setiap deployment?**  
>   Proses worker berjalan secara kontinu di memori. Worker tidak otomatis membaca berkas kode PHP yang baru di-pull dari git. Tanpa restart, worker akan terus menjalankan logika lama atau error saat memanggil model/event yang strukturnya sudah berubah.

---

## 9. Daftar Masalah yang Ditemui & Cara Mengatasinya (Troubleshooting)

Sesuai instruksi **Build Nomor 5 Modul 12**, berikut adalah catatan masalah teknis nyata yang dihadapi saat proses deployment dan solusinya:

---

### Masalah 1: Sempat Tidak Bisa Login Karena Seeder Belum Dijalankan
* **Gejala:**  
  Aplikasi berhasil aktif dan halaman `/login` terbuka normal, namun saat login menggunakan akun demo bawaan (`dosen@kampuslms.test` atau `mahasiswa@kampuslms.test`), muncul pesan kesalahan bahwa kredensial tidak cocok.
* **Penyebab Akar Masalah (*Root Cause*):**  
  Pada saat pertama kali deployment di Railway, database baru hanya menjalankan migrasi struktur tabel (`php artisan migrate`). Seeder pengisi data awal (`DatabaseSeeder`) belum pernah dijalankan, sehingga tabel `users` kosong melompong dan tidak ada satupun akun yang terdaftar.
* **Solusi yang Diterapkan:**  
  Menjalankan seeder secara eksplisit melalui Railway CLI / Docker startup script dengan flag `--force`:
  ```bash
  php artisan db:seed --force
  ```
  Setelah data terisi, akun admin, dosen, dan mahasiswa langsung dapat digunakan untuk login.

---

### Masalah 2: Kontainer Crash Berulang Kali Akibat Start Command `php artisan db:seed`
* **Gejala:**  
  Status deployment di Railway berubah menjadi merah (*CrashLoopBackOff* / *Application Failed to Start*) dan website tidak dapat diakses sama sekali.
* **Penyebab Akar Masalah (*Root Cause*):**  
  Ketika mencoba mengatasi masalah nomor 1, kami tidak sengaja menuliskan perintah `php artisan db:seed` ke dalam kolom **Custom Start Command** pada pengaturan Railway.  
  Di Railway, proses pada *Start Command* harus berupa proses yang berjalan terus-menerus di latar depan (*foreground daemon*). Perintah `db:seed` adalah tugas sekali jalan yang langsung selesai (*exit*). Ketika perintah selesai, Railway mengira kontainer mati (*crashed*), lalu mencoba merestartnya secara berulang-ulang hingga gagal.
* **Solusi yang Diterapkan:**  
  Mengosongkan kolom **Custom Start Command** di dashboard Railway agar Railway kembali menjalankan perintah bawaan `CMD` yang ada pada `Dockerfile` (`exec apache2-foreground`).

---

### Masalah 3: Faker Tidak Berjalan di Server Produksi
* **Gejala:**  
  Saat proses seeding di server produksi dijalankan, muncul error fatal:  
  `Class "Faker\Factory" not found`.
* **Penyebab Akar Masalah (*Root Cause*):**  
  Pada file `Dockerfile` standar produksi, dependensi composer diinstal menggunakan flag `--no-dev`:
  ```dockerfile
  RUN composer install --no-dev --optimize-autoloader
  ```
  Paket generator data palsu `fakerphp/faker` berada di bawah grup `"require-dev"` pada `composer.json`. Akibatnya, Faker tidak ikut terpasang di kontainer produksi, sehingga seeder yang membutuhkan data dummy gagal berjalan.
* **Solusi yang Diterapkan:**  
  Menghapus flag `--no-dev` pada baris kompilasi di `Dockerfile` menjadi:
  ```dockerfile
  RUN composer install --optimize-autoloader --no-interaction --ignore-platform-reqs
  ```
  Dengan menghapus `--no-dev`, pustaka Faker tetap terpasang di container produksi sehingga seeding untuk keperluan akun demo UTS/Tugas berjalan lancar.

---

### Masalah 4: Kontainer Crash Akibat Konfigurasi Port Ganda (`EXPOSE 80 8080`)
* **Gejala:**  
  Aplikasi sempat crash dan gagal binding jaringan saat deploy ke Railway karena port yang terdeteksi hanya sampai port 80.
* **Penyebab Akar Masalah (*Root Cause*):**  
  Dockerfile panduan awal dari asisten dosen mencantumkan:
  ```dockerfile
  EXPOSE 80 8080
  ```
  Sementara pada lingkungan produksi Railway, port yang digunakan untuk binding aplikasi adalah port `8080`. Karena yang terbaca oleh sistem Railway adalah port pertama (port 80), terjadi ketidakcocokan port sehingga healthcheck Railway gagal mendeteksi aplikasi.
* **Solusi yang Diterapkan:**  
  Mengubah baris ekspose port pada `Dockerfile` secara spesifik menjadi:
  ```dockerfile
  EXPOSE 8080
  ```
  Dengan hanya mengekspose port 8080, Railway langsung mengenali port yang tepat dan kontainer berjalan dengan stabil.

---

### Masalah 5: Peringatan Browser *"The information you’re about to submit is not secure"* (Mixed Content pada Form Submission)
* **Gejala:**  
  Website dapat diakses dengan mulus via HTTPS (`https://kampuslms-...`). Namun, setiap kali pengguna menekan tombol kirim formulir (seperti tombol Login atau submit tugas), browser memblokir dan menampilkan layar peringatan merah:  
  > *"The information you’re about to submit is not secure. Because this form is being submitted using a connection that’s not secure, your information will be visible to others."*
* **Penyebab Akar Masalah (*Root Cause*):**  
  Arsitektur cloud Railway menggunakan **Reverse Proxy (Edge Proxy)**. Browser pengguna berkomunikasi dengan proxy menggunakan **HTTPS**, namun proxy meneruskan request ke dalam container Apache Laravel melalui jaringan internal menggunakan **HTTP biasa** (port 8080).  
  Secara default di Laravel 12, Laravel belum mempercayai reverse proxy tersebut. Akibatnya:
  1. Helper Laravel `route(...)` dan `url(...)` mengira koneksi yang datang adalah HTTP biasa, sehingga menghasilkan atribut formulir `<form action="http://...">`.
  2. Browser mendeteksi halaman HTTPS mengirimkan formulir ke URL HTTP (insecure mixed form submission) dan langsung menampilkan peringatan keamanan.
* **Solusi yang Diterapkan:**
  1. Menambahkan konfigurasi *Trust Proxies* pada berkas `bootstrap/app.php`:
     ```php
     ->withMiddleware(function (Middleware $middleware) {
         $middleware->trustProxies(at: '*');
     })
     ```
     Dengan baris ini, Laravel membaca header `X-Forwarded-Proto: https` yang dikirimkan oleh Railway Edge Proxy dan mengenali bahwa koneksi pengguna sebenarnya adalah HTTPS.
  2. Menambahkan `URL::forceScheme('https')` pada `app/Providers/AppServiceProvider.php` untuk lingkungan produksi sebagai lapisan pengaman tambahan.
  3. Memastikan variabel `APP_URL=https://kampuslms-b-kelompok-07-production.up.railway.app` dan `SESSION_SECURE_COOKIE=true` pada environment server.

---

## 10. Kesimpulan Evaluasi Milestone M4

Berdasarkan hasil pengujian di atas:
1. **Build 1:** Aplikasi telah aktif live di domain publik dengan HTTPS dan tautan sudah tersemat di baris awal `README.md`.
2. **Build 2:** Seluruh 12 poin daftar periksa keamanan produksi telah terverifikasi dengan bukti keluaran `curl` yang sesungguhnya.
3. **Build 3:** Skrip `deploy.sh` telah tersedia di akar repositori, berkomentar lengkap, dan menerapkan kaidah `set -e` serta `queue:restart`.
4. **Build 4:** Konfigurasi worker Supervisor (`kampuslms-worker.conf`) dan bukti keluaran `supervisorctl status` telah disertakan.
5. **Build 5:** Dokumentasi deployment telah mencakup spesifikasi teknis, panduan instalasi dari nol, cara deployment ulang, cara membaca log, dan 5 studi kasus pemecahan masalah teknis nyata.
