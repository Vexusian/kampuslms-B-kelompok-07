## Catatan Minggu 1

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

### READ 

### 1. Buka `public/index.php`. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.  
**Jawab:**  
Berkas `public/index.php` adalah pintu gerbang utama yang menyambut setiap permintaan dari browser pengguna. Berkas ini berisi seluruh file inti Laravel dan menghidupkan mesin aplikasi agar siap bekerja. `public/index.php` meneruskan permintaan tersebut untuk diproses oleh sistem Laravel, lalu mengirimkan hasilnya kembali ke layar pengguna.

### 2. Buka bootstrap/app.php. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.  
**Jawab:**   
- **Mengurus Route (Rute/URL)**: Diurus oleh bagian `->withRouting(...)`. Fungsinya: Memberi tahu Laravel di mana file berisi daftar alamat URL (seperti `routes/web.php`) berada, sehingga Laravel tahu harus menampilkan halaman apa jika pengguna mengakses alamat tertentu.
- **Mengurus Middleware**: Diurus oleh bagian `->withMiddleware(...)`. Fungsinya: Mengatur "pos keamanan" atau filter. Middleware bertugas memeriksa permintaan pengguna sebelum diproses lebih lanjut (misalnya: mengecek apakah pengguna sudah login atau belum).
- **Mengurus Exception (Penanganan Error)**: Diurus oleh bagian `->withExceptions(...)`. Fungsinya: Mengatur bagaimana Laravel harus bereaksi jika terjadi error atau crash (misalnya: menampilkan halaman error 404 yang cantik, atau mencatat log error ke dalam file).

### 3. Buka routes/web.php. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.  
**Jawab:**  
a. Router yang menghasilkan halaman selamat datang yaitu pada berkas `routes/web.php` pada bagian:
```php
Route::get('/', function () {
    return view('welcome');
});
```

b. Buka berkas `resources/views/welcome.blade.php`. Bagian kode ini:
```php
</header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium">Mari kita mulai!</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">Laravel has an incredibly rich ecosystem. <br>We suggest starting with the following.</p>
                    <ul class="flex flex-col mb-4 lg:mb-6">
```

c. Ubah baris `<h1 class="mb-1 font-medium">Let's get started</h1>` pada bagian `Let's get started`. Misal menjadi 'Mari kita mulai!`
```php
    </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium">Mari kita mulai!</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">Laravel has an incredibly rich ecosystem. <br>We suggest starting with the following.</p>
                    <ul class="flex flex-col mb-4 lg:mb-6">
```

d. Simpan berkas tersebut.  
e. Buka browser, lalu refresh halaman proyek Laravel. Tampilan akan berubah sesuai yang diinginkan. Hail tampilan sebelum di ubah yaitu `Let's get started` dan hasil tampilan setelah diubah yaitu `Mari kita mulai!`. 

### 4. Jalankan php artisan route:list. Cocokkan keluarannya dengan isi routes/web.php.  
**Jawab:**  
Saat menjalankan perintah tersebut di terminal:
- Baris dengan URI / dan Method `GET|HEAD` merupakan cerminan langsung dari `Route::get('/', ...)` yang ada di dalam berkas `routes/web.php`.
- Route lainnya yang muncul (seperti `storage/...` atau `up`) adalah route bawaan Laravel yang terdaftar secara internal untuk pengelolaan berkas dan pemeriksaan kesehatan aplikasi (health check).
