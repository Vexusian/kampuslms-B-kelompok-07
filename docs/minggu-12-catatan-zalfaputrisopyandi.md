## Catatan Minggu 12

**Nama:** Zalfa Putri Sopyandi  
**NIM:** 10241076  

---

## READ 

### 1. Temukan konfigurasi Nginx untuk situs Anda. Baris mana yang menentukan document root? Ke folder apa ia mengarah?

* **Web Server Proyek Kami:** Di server produksi Railway, aplikasi kami berjalan di dalam kontainer Docker berbasis **Apache** (`php:8.4-apache`). Pengaturan *document root*-nya ditentukan pada berkas `Dockerfile` baris 23–25:
  ```dockerfile
  ENV APACHE_DOCUMENT_ROOT /var/www/html/public
  RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
  ```
* **Jika Menggunakan Nginx:** Baris yang menentukan *document root* ada pada blok `server`:
  ```nginx
  root /var/www/html/public;
  ```
* **Folder Tujuan:** Mengarah ke subfolder **`public/`**.  
  *Alasannya:* Agar hanya file publik (seperti `index.php`, CSS, JS) yang dapat diakses pengunjung, sedangkan file sensitif seperti `.env`, `storage/`, dan `composer.json` terlindungi di luar jangkauan publik.

---

### 2. Jalankan `php artisan about` di server. Bandingkan dengan lokal — apa saja yang berbeda?

Perbedaan utama antara server produksi (Railway) dengan komputer lokal:

| Pengaturan | Server Produksi (Railway) | Komputer Lokal (Laptop) |
|---|---|---|
| **Environment (`APP_ENV`)** | `production` | `local` |
| **Debug Mode (`APP_DEBUG`)** | `false` (wajib mati demi keamanan) | `true` (aktif untuk perbaikan bug) |
| **URL Aplikasi (`APP_URL`)** | `https://kampuslms-b-kelompok-07-production.up.railway.app` (HTTPS) | `http://127.0.0.1:8000` (HTTP) |
| **Koneksi Database** | Cloud MySQL terkelola Railway | MySQL lokal (Laragon/XAMPP) |
| **Status Cache** | `CACHED` (Config, Routes, Views di-cache agar cepat) | `NOT CACHED` (agar perubahan kode langsung aktif) |
| **Session & Cookie** | Driver database dengan flag `secure; httponly` | Driver file lokal |

---

### 3. Jalankan `supervisorctl status`. Berapa worker yang berjalan?

* **Hasil Perintah di Server:**
  ```text
  kampuslms-worker:kampuslms-worker_00   RUNNING   pid 433, uptime 0:00:23
  kampuslms-worker:kampuslms-worker_01   RUNNING   pid 434, uptime 0:00:23
  ```
* **Jumlah Worker:** Ada **2 worker** yang aktif berjalan bersamaan (`numprocs=2` pada konfigurasi `kampuslms-worker.conf`), dengan status keduanya **`RUNNING`**.
* **Fungsinya:** Menjalankan tugas latar belakang (*queue worker*), seperti memproses email dan notifikasi penugasan baru secara otomatis tanpa membuat pengguna menunggu lama saat memuat halaman web.

---

### 4. Buka `storage/logs/laravel.log`. Ada berapa entri? Baca yang terbaru.

* **Jumlah Entri:** Pada file log lokal terdapat riwayat pencatatan error pengembangan.
* **Isi Entri Terbaru:**
  ```text
  [2026-11-04 00:09:28] local.ERROR: Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled. (View: ...\views\courses\index.blade.php)
  ```
* **Penjelasan Log:** Log ini mencatat pencegahan *lazy loading* (N+1 query) oleh framework saat relasi `lecturer` dipanggil di halaman daftar mata kuliah tanpa eager loading. Di server produksi (`APP_DEBUG=false`), setiap kendala sistem akan dicatat rapi ke file log ini tanpa menampilkan pesan error teknis ke layar pengguna.

---

### 5. Jalankan ketiga perintah `curl -I` dari daftar periksa. Catat hasilnya.

Pengujian dilakukan langsung ke domain publik Railway:

1. **Uji Keamanan Berkas `.env`:**
   * Perintah: `curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/.env`
   * **Hasil:** **`HTTP/1.1 404 Not Found`** (Aman, file kredensial dan kata sandi database tidak bisa diakses publik).

2. **Uji Keamanan Berkas Log Sistem:**
   * Perintah: `curl -I https://kampuslms-b-kelompok-07-production.up.railway.app/storage/logs/laravel.log`
   * **Hasil:** **`HTTP/1.1 404 Not Found`** (Aman, log error sistem tidak terekspos ke internet).

3. **Uji Pengalihan Otomatis HTTP ke HTTPS:**
   * Perintah: `curl -I http://kampuslms-b-kelompok-07-production.up.railway.app/`
   * **Hasil:** **`HTTP/1.1 308 Permanent Redirect`** dialihkan ke `https://` (Aman, semua pengunjung diarahkan ke koneksi HTTPS yang terenkripsi).

---

### 6. Buka aplikasi dari HP Anda, bukan laptop. Ada yang rusak?

* **Hasil Pengamatan:** **Tidak ada yang rusak sama sekali.** Aplikasi berjalan responsif, rapi, dan normal.
* **Rincian Pengujian dari HP (Samsung A26 / Chrome & Samsung Internet):**
  1. **Tampilan Responsif:** Desain website otomatis menyesuaikan lebar layar ponsel dengan pas tanpa terpotong.
  2. **Kartu Statistik:** Kartu metrik di dashboard tersusun rapi menjadi 1 kolom vertikal yang nyaman dibaca di layar HP.
  3. **Tabel & Badge:** Tabel daftar mata kuliah dan tugas tidak meluber keluar layar (*tidak ada horizontal scroll yang bocor*), dan badge status (*Aktif* / *Berakhir*) terbaca jelas.
  4. **Navigasi & Tombol:** Menu navigasi, tombol *Detail*, unduh materi, dan tombol pengumpulan tugas mudah ditekan dengan jari (*touch-friendly*).
