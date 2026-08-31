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
_Route_ yang menghasilkan halaman selamat datang adalah ```Route::get('/', function () { return view('welcome'); });```. _Route_ tersebut menangani _request_ GET pada URL / dan mengembalikan view```welcome```, yang berasal dari ```resources/views/welcome.blade.php```. Setelah teks pada view diubah dan browser dimuat ulang, halaman menampilkan teks yang telah diperbarui.

### 4. Jalankan ```php artisan route:list```. Cocokkan keluarannya dengan ```routes/web.php```.
Perintah ```php artisan route:list``` menampilkan daftar _route_ yang terdaftar pada aplikasi Laravel. _Route_ / dengan method ```GET|HEAD``` dapat dicocokkan dengan ```Route::get('/', ...)``` yang terdapat pada ```routes/web.php```. Hal ini menunjukkan bahwa route yang ditulis pada ```routes/web.php``` telah terdaftar dan dikenali oleh Laravel.

## BREAK — Rusak dengan Sengaja


## FIX — Perbaiki Proyek yang Cacat


## BUILD — Fondasi Proyek Kelompok
