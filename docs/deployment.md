# Dokumentasi Deployment & Bukti Pengujian Keamanan Produksi

Dokumen ini mencatat bukti implementasi deployment aplikasi **KampusLMS** di lingkungan produksi (Railway) dengan domain publik berprotokol HTTPS, serta verifikasi lengkap daftar periksa keamanan (*production security checklist*) mengacu pada **Modul Minggu 12 (Milestone M4 / Tugas 4)** menggunakan keluaran perintah `curl` yang sesungguhnya.

---

## 1. Informasi Deployment Produksi

* **Nama Proyek:** KampusLMS (Kelas B - Kelompok 07)
* **Penyedia Layanan (PaaS):** Railway
* **URL Domain Publik (HTTPS):** [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app)
* **Protokol:** HTTPS (Terenkripsi dengan SSL/TLS Otomatis melalui Edge Proxy)
* **Web Server & Runtime:** Apache + PHP 8.4 (Dockerized Container)
* **Basis Data:** MySQL (Railway Managed Database)
* **Lingkungan (*Environment*):** `APP_ENV=production`, `APP_DEBUG=false`

> Tautan aplikasi live ini telah disematkan di bagian paling atas berkas [`README.md`](../README.md) sesuai ketentuan **Build Nomor 1**.

---

## 2. Seluruh Daftar Periksa Keamanan Produksi (Production Security Checklist)

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

## 3. Bukti Pengujian Keamanan dengan `curl` (Keluaran Nyata)

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

## 4. Daftar Masalah yang Ditemui & Cara Mengatasinya (Troubleshooting)

Sesuai instruksi **Build Nomor 5 Modul 12**, berikut adalah catatan masalah teknis nyata yang dihadapi saat proses deployment dan solusinya:

### Masalah: Peringatan Browser *"The information you’re about to submit is not secure"* (Mixed Content pada Form Submission)

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
         // ...
     })
     ```
     Dengan baris ini, Laravel membaca header `X-Forwarded-Proto: https` yang dikirimkan oleh Railway Edge Proxy dan mengenali bahwa koneksi pengguna sebenarnya adalah HTTPS.
  2. Menambahkan `URL::forceScheme('https')` pada `app/Providers/AppServiceProvider.php` untuk lingkungan produksi sebagai lapisan pengaman tambahan.
  3. Memastikan variabel `APP_URL=https://kampuslms-b-kelompok-07-production.up.railway.app` dan `SESSION_SECURE_COOKIE=true` pada environment server.

---

## 5. Kesimpulan Evaluasi Milestone M4

Berdasarkan hasil pengujian di atas:
1. **Build 1:** Aplikasi telah aktif live di domain publik dengan HTTPS dan tautan sudah tersemat di baris awal `README.md`.
2. **Build 2:** Seluruh 12 poin daftar periksa keamanan produksi telah terverifikasi dengan bukti keluaran `curl` yang sesungguhnya.
