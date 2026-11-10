# Dokumentasi Deployment & Bukti Pengujian Keamanan Produksi

Dokumen ini mencatat bukti implementasi deployment aplikasi **KampusLMS** di lingkungan produksi (Railway) dengan domain publik berprotokol HTTPS, serta verifikasi daftar periksa keamanan (*production security checklist*) menggunakan keluaran perintah `curl` yang sesungguhnya.

---

## 1. Informasi Deployment Produksi

* **Nama Proyek:** KampusLMS (Kelas B - Kelompok 07)
* **Penyedia Layanan (PaaS):** Railway
* **URL Domain Publik (HTTPS):** [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app)
* **Protokol:** HTTPS (Terenkripsi dengan SSL/TLS Otomatis)
* **Lingkungan (*Environment*):** `APP_ENV=production`, `APP_DEBUG=false`

Tautan aplikasi live ini juga telah dicantumkan di bagian paling atas berkas [`README.md`](../README.md) agar mudah ditemukan dan diakses oleh penguji.

---

## 2. Daftar Periksa Keamanan Produksi (Production Security Checklist)

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

## 4. Kesimpulan

Seluruh kriteria deployment dan keamanan produksi telah terpenuhi:
1. Domain publik [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app) beroperasi dengan aman di bawah protokol HTTPS.
2. Tautan domain telah disematkan di bagian atas berkas `README.md`.
3. Berkas rahasia `.env` terbukti aman dan tidak dapat diakses secara publik dengan bukti keluaran nyata status `HTTP/1.1 404 Not Found`.

