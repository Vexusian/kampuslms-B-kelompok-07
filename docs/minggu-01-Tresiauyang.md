# **Catatan Pratikum**

Nama : Tresia Uyang 

NIM  : 10241072

## 1. Buka public/index.php. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.

Berkas public/index.php merupakan bagian pertama yang menerima permintaan dari pengguna untuk membuka aplikasi Laravel. Berkas ini memeriksa apakah aplikasi sedang dalam masa perbaikan dan memuat file yang diperlukan untuk menjalankan aplikasi. Setelah itu, permintaan pengguna diterima dan diteruskan ke aplikasi Laravel untuk diproses.

 ## 2. Buka `bootstrap/app.php.` Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.

- `withRouting()`  mengurus route atau jalur halaman yang digunakan aplikasi, seperti routes/web.php dan routes/console.php.
- `withMiddleware()` mengurus proses tambahan yang dijalankan sebelum permintaan pengguna diproses oleh aplikasi.
- `withExceptions()` mengurus penanganan error yang terjadi dalam aplikasi.

## 3. Buka routes/web.php. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.

Halaman selamat datang diatur oleh route `Route::get('/', function () { return view('welcome'); });`. Route tersebut menerima permintaan GET pada alamat / dan menampilkan halaman `welcome` yang berada di file `resources/views/welcome.blade.php`. Setelah isi teks pada halaman tersebut diubah dan browser dimuat ulang, perubahan teks akan langsung terlihat.

## 4. Jalankan `php artisan route:list`. Cocokkan keluarannya dengan isi `routes/web.php`.

Perintah `php artisan route:list` digunakan untuk melihat daftar route yang sudah terdaftar dalam aplikasi Laravel. Pada hasilnya, route `/` dengan metode `GET|HEAD` sesuai dengan kode `Route::get('/', ...)` yang terdapat di `routes/web.php`. Hal ini membuktikan bahwa route yang dibuat pada `routes/web.php` berhasil didaftarkan dan dikenali oleh Laravel.
