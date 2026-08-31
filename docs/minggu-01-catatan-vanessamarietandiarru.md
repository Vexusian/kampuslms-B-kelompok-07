# Catatan Praktikum 1

Nama: Vanessa Marie Tandiarru  
NIM: 10251118

## READ — Bedah Instalansi
### 1. Buka ```public/index.php```. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.
Berkas ```public/index.php``` merupakan _entry point_ aplikasi Laravel yang menerima request dari pengguna. Berkas ini memuat _autoloader Composer_, memeriksa _maintenance mode_, kemudian melakukan _bootstrap_ aplikasi melalui ```bootstrap/app.php```. Setelah itu, request HTTP ditangkap menggunakan ```Request::capture()``` dan diteruskan ke aplikasi Laravel untuk diproses.

### 2. Buka ```bootstrap/app.php```. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.
- Route: bagian ```->withRouting()``` yang menentukan file dan konfigurasi _routing_ aplikasi, termasuk ```routes/web.php```.  
- Middleware: bagian ```->withMiddleware(function (Middleware $middleware): void { ... })``` yang digunakan untuk mengatur _middleware_ aplikasi.
- Exception: bagian ```->withExceptions(function (Exceptions $exceptions): void { ... })``` yang digunakan untuk konfigurasi penanganan exception/error.

### 3. Buka ```routes/web.php```. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang _browser_, pastikan berubah.


### 4. Jalankan ```php artisan route:list```. Cocokkan keluarannya dengan ```routes/web.php```.

## BREAK — Rusak dengan Sengaja


## FIX — Perbaiki Proyek yang Cacat


## BUILD — Fondasi Proyek Kelompok
