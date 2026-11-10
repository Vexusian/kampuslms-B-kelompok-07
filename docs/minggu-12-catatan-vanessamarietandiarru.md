# Catatan Praktikum Modul Minggu 12 — Deployment Produksi & Keamanan

- **Nama:** Vanessa Marie Tandiarru
- **NIM:** 10251118
- **Kelas:** Pemrograman Web (Kelas B - Kelompok 07)
- **Tautan Produksi (Live):** [https://kampuslms-b-kelompok-07-production.up.railway.app](https://kampuslms-b-kelompok-07-production.up.railway.app)

---

## 1. Ringkasan Kontribusi & Implementasi Bagian BUILD (Minggu 12)

Dalam pembagian tugas Milestone M4 (Minggu 12), saya bertanggung jawab secara khusus pada pengujian fungsionalitas akun demo di lingkungan produksi (Poin 7) serta pengujian responsivitas aplikasi dari perangkat mobile (Poin 8):

1. **Verifikasi Fungsionalitas Akun Demo di Server Produksi (Poin 7):**
   - Menjalankan pengujian autentikasi terhadap 3 akun demo yang telah di-seed (`php artisan db:seed`) di server produksi Railway:
     - **Admin (`admin@kampuslms.test` / `password`):** Verifikasi dashboard admin dan manajemen data user.
     - **Dosen Demo (`dosen@kampuslms.test` / `password`):** Berhasil login dan memverifikasi dashboard dosen yang mengampu 2 kelas aktif dengan total 24 peserta kelas.
     - **Mahasiswa Demo (`mahasiswa@kampuslms.test` / `password`):** Berhasil login dan memverifikasi 5 mata kuliah terdaftar serta daftar penugasan dengan status *"Sudah Dikumpulkan"*.

2. **Pengujian Lintas Perangkat / Mobile pada Vivo V23e (Poin 8):**
   - Menguji aplikasi secara langsung dari peramban **Google Chrome Mobile (Android)** melalui jaringan seluler publik (di luar jaringan lokal pengembang).
   - **Hasil Pengujian Antarmuka:**
     - Header navigasi (*Dashboard*, *Mata Kuliah*, *Tentang*, *Notifikasi*) proporsional dan tidak mengalami tumpang tindih.
     - Kartu statistik dashboard secara adaptif menyusun menjadi tata letak satu kolom vertikal (*single-column*) yang ergonomis untuk layar ponsel.
     - Tabel mata kuliah dan tabel pengumpulan tugas tertata rapi tanpa menyebabkan kebocoran gulir horizontal (*no horizontal overflow*), dan lencana status (*badges*) berwarna kontras serta mudah dibaca.
     - Seluruh tombol aksi (*Kelas Saya*, *Detail*, *Logout*) memiliki ukuran area sentuh yang nyaman (*touch-friendly*).
   - Lampiran bukti tangkapan layar telah tersimpan di `docs/images/image.jpeg`.

3. **Troubleshooting Masalah Keamanan Reverse Proxy & Form Submission:**
   - Menemukan dan mengatasi kendala peringatan peramban *"The information you're about to submit is not secure"* pada form login.
   - Masalah disebabkan oleh terminasi SSL pada reverse proxy Railway yang meneruskan request ke Laravel via HTTP internal.
   - Diatasi bersama tim dengan menambahkan `$middleware->trustProxies(at: '*')` pada `bootstrap/app.php` dan `URL::forceScheme('https')` pada `AppServiceProvider.php` untuk lingkungan produksi.

---

## 2. Jawaban Checkpoint Minggu 12 — Gerbang Interview Tugas 4

### 1. Buka situs Anda, penguji akan menjalankan `curl -I https://[domain]/.env`. Harus 404 atau 403, tidak boleh 200. Kenapa?

**Jawaban:**
Jika perintah `curl -I` terhadap `/.env` mengembalikan status `200 OK`, berarti berkas rahasia `.env` dapat diunduh bebas oleh siapa pun di internet. Hal ini merupakan kebocoran data fatal (*critical security vulnerability*) karena memuat kredensial sensitif seperti `APP_KEY`, kata sandi database produksi, serta konfigurasi sesi. Pada web server yang dikonfigurasi dengan benar, *document root* diarahkan ke folder `/public`, sehingga berkas `.env` berada di luar jangkauan publik dan server mengembalikan status **`404 Not Found`** (atau `403 Forbidden`).

---

### 2. Apa yang terjadi kalau `APP_DEBUG=true` di produksi? Peragakan di lokal, jelaskan risikonya di produksi.

**Jawaban:**
- **Yang Terjadi:** Ketika terjadi pengecualian (*unhandled exception*) atau error 500, Laravel akan menampilkan halaman debug Ignition interaktif lengkap.
- **Risiko di Produksi:** Halaman debug membocorkan *source code* aplikasi, path direktori server, query SQL mentah beserta parameternya, variabel environment, nilai cookie sesi, serta *stack trace* fungsi secara terperinci. Informasi ini memberi jalan pintas bagi penyerang untuk merancang eksploitasi lebih lanjut. Di server produksi, `APP_DEBUG` wajib selalu bernilai `false`.

---

### 3. Kenapa document root harus mengarah ke `public/`? Apa yang bocor kalau tidak?

**Jawaban:**
Folder `public/` sengaja dirancang oleh arsitektur Laravel sebagai satu-satunya gerbang masuk (*entry point*) publik yang hanya memuat `index.php`, file `.htaccess`, dan aset statis (CSS/JS). Jika document root salah mengarah ke *root* proyek:
- Berkas rahasia `.env` dapat diunduh langsung.
- Berkas dependensi `composer.json` dan `composer.lock` bocor (penyerang mengetahui seluruh versi pustaka dan celah keamanannya).
- Riwayat error dan aktivitas aplikasi di `storage/logs/laravel.log` dapat dibaca secara publik.

---

### 4. Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?

**Jawaban:**
- **Keluaran Aktual di Server (Railway Console):**
  ```text
  root@46680c9c6602:/var/www/html# supervisorctl status
  kampuslms-worker:kampuslms-worker_00   RUNNING   pid 433, uptime 0:00:23
  kampuslms-worker:kampuslms-worker_01   RUNNING   pid 434, uptime 0:00:23
  ```
  *(Tangkapan layar terminal telah dilampirkan pada `docs/images/supervisor.png`).*
- **Akibat Jika Worker Mati Tanpa Diketahui:**
  Seluruh job antrean latar belakang (*background jobs*)—seperti pengiriman email notifikasi penugasan baru kepada puluhan mahasiswa—akan tertahan di tabel `jobs` database dan tidak pernah diproses. Dosen mengira notifikasi sudah terkirim, padahal mahasiswa tidak pernah menerimanya (*silent failure*).

---

### 5. Kenapa `queue:restart` wajib setelah setiap deployment?

**Jawaban:**
*Queue worker* Laravel bekerja sebagai proses *long-running daemon* di memori RAM. Worker memuat seluruh kode aplikasi ke RAM pada saat proses pertama kali dijalankan. Ketika kita melakukan deployment kode baru (misal mengubah logika notifikasi atau model), worker yang sedang berjalan di latar belakang **tidak akan otomatis membaca perubahan file di disk**. Tanpa menjalankan `php artisan queue:restart`, worker akan terus mengeksekusi versi kode lama yang tersimpan di memori RAM.

---

### 6. Setelah `config:cache`, apa yang terjadi pada `env()` di luar `config/`? Sudahkah kode Anda bersih dari itu?

**Jawaban:**
- **Yang Terjadi:** Begitu `config:cache` dijalankan, Laravel menggabungkan seluruh konfigurasi ke berkas cache `bootstrap/cache/config.php` dan berhenti mem-parsing berkas `.env` pada saat runtime. Pemanggilan `env('KEY')` di luar folder `config/` (seperti di Controller, Model, atau Blade) akan mengembalikan nilai **`null`**, yang memicu bug tak terduga.
- **Status Kode Kami:** Kode kami sudah bersih dari pemanggilan `env()` di luar direktori `config/`. Seluruh variabel diakses melalui fungsi `config('nama_konfigurasi')` sebagaimana telah kami bersihkan sejak praktikum Minggu 11.

---

### 7. Buka `docs/deployment.md`. Ceritakan satu masalah yang Anda temui dan bagaimana Anda menemukan penyebabnya.

**Jawaban:**
*"Saat pengujian awal form login di server produksi HTTPS, peramban memunculkan dialog peringatan keamanan 'The information you're about to submit is not secure'. Kami menganalisis penyebabnya melalui fitur Inspect Element pada tag form di peramban, dan mendapati bahwa atribut `action` form berprotokol `http://` bukan `https://`. Penyebab utamanya adalah terminasi SSL terjadi di layer reverse proxy Railway, sehingga container internal Laravel menerima request via HTTP dan mengira skema web adalah HTTP. Kami memperbaikinya dengan menambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` dan `URL::forceScheme('https')` di `AppServiceProvider.php`."*

---

### 8. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

**Jawaban:**
*"Kami menggunakan bantuan AI untuk menyusun kerangka awal skrip `deploy.sh` dan tabel pengujian keamanan `curl`. Bagian yang kami sesuaikan dan ubah secara mandiri adalah parameter health check pada akhir skrip `deploy.sh`: AI awalnya menyarankan melakukan curl HTTP sederhana ke localhost, namun di lingkungan PaaS (Railway) atau VPS dengan port internal dinamis, kami mengubahnya menjadi perintah verifikasi framework berbasis CLI `php artisan about > /dev/null 2>&1` yang lebih andal untuk memastikan koneksi database dan bootstrap framework Laravel benar-benar sehat sebelum keluar dari mode maintenance."*
