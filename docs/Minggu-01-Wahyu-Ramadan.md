## Wahyu Ramadan 
## 10241074

Catatan Praktikum 1

1. Buka `public/index.php`. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.

Pada `public/index.php` memiliki fungsi sebagai titik masuk utama ketika terdapat http request yang masuk ke aplikasi di laravel. kemudian disini, Laravel mengecek apakah web sedang perbaikan/maintenance, lalu mengaktifkan semua modul pendukung agar siap digunakan.  Setelah siap, berkas ini menerima permintaan pengunjung, memprosesnya ke sistem, dan hasilnya ditampilkan pada layar pengunjung.

2. Buka `bootstrap/app.php`. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.

 ```php 
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- bagian yang mengurus route `wihtRouting(...)` digunakan sebagai penentu jalan seperti `routes/web.php` atau `routes/console.php`

- bagian yang digunakan dalam mengurus middleware `withMiddleware(...)` digunakan sebagai pengatur akses

- dan bagian yang digunakan dalam mengatur exeption atau error handling yakni `withExeptions(...)`

3.  Buka `routes/web.php`. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.

pada berkas `routes/web.php` disini diberikan sebuah berkas yang berisi halaman welcome dan route yang menuju `resources/views/welcome.blade.php`, jika ingin mengubah tampilan awal view maka diubah isi pada berkas `resources/views/welcome.blade.php` dan merefresh halaman browser agar tampilannya menyesuaikan

4. Jalankan php artisan route:list. Cocokkan keluarannya dengan routes/web.php.


setelah menjalankan `php artisan route:list` didapatkan `GET|HEAD /` yang cocok dengan `routes/web.php` halaman utama yang kita atur di file dapat terdeteksi. 











