# 📚 Catatan Bedah Struktur Laravel (`kampuslms`)

Dokumen ini berisi penjelasan struktur folder dan bedah kode dasar Laravel dengan **bahasa yang sederhana, santai, dan mudah dipahami**.

---

## 🏬 Analisis Struktur Folder (Analogi Restoran)

Bayangkan project Laravel ini seperti sebuah **Restoran Modern**:

| Nama Folder / File | Analogi & Penjelasan Sederhana |
| :--- | :--- |
| **`public/`** | **Etalase Depan & Pintu Masuk.** Satu-satunya folder yang bisa diakses langsung oleh pengunjung internet. Tempat menyimpan file `index.php` (pintu utama), gambar, dan CSS/JS hasil jadi. |
| **`app/`** | **Dapur Utama & Otak Aplikasi.** Tempat koki (kodingan kita) bekerja memasak logika web, mengolah data (Models), dan mengatur alur permintaan (Controllers). |
| **`routes/`** | **Buku Menu & Papan Petunjuk Jalan.** Berisi daftar alamat URL. Kalau pengunjung buka alamat X, rute ini yang menentukan koki harus masak apa. |
| **`resources/`** | **Dapur Desain (Tampilan).** Tempat menyimpan halaman web (*Blade views*) dan bahan mentah CSS/JS sebelum dirapiin. |
| **`bootstrap/`** | **Tombol Starter & Saklar Listrik.** Berkas yang menyalakan mesin Laravel saat pertama kali diakses, mengatur rute, satpam (middleware), dan penanganan error. |
| **`config/`** | **Buku Pengaturan.** Berisi setelan nama aplikasi, koneksi database, email, dan konfigurasi lainnya. |
| **`database/`** | **Cetak Biru & Bahan Rumah Data.** Tempat membuat struktur tabel (*migrations*) dan data awal (*seeders*). |
| **`storage/`** | **Gudang Penyimpanan.** Tempat menyimpan foto yang diupload user, berkas sementara (cache), dan buku catatan error (*logs/laravel.log*). |
| **`tests/`** | **Lapangan Uji Coba.** Tempat menaruh baris kode untuk mengetes apakah aplikasi kita bebas dari bug secara otomatis. |
| **`vendor/`** | **Kotak Peralatan Tambahan.** Berisi alat-alat atau pustaka (*library*) buatan orang lain yang kita pakai di Laravel via Composer. |
| **`.env`** | **Buku Catatan Kunci Rahasia.** Tempat menyimpan password database, kunci aplikasi, dan setelan sensitif lainnya. |

---

## 📖 Tugas READ — Bedah Instalasi (Penjelasan Mudah)

### 1. File `public/index.php` (Pintu Utama)

File: [`public/index.php`](file:///d:/laragon/www/kampuslms/kampuslms/public/index.php)

**3 Kalimat Penjelasan Sederhana:**
1. Berkas ini adalah **pintu masuk pertama** yang dilewati setiap kali ada pengunjung membuka website kita di browser.
2. Di sini, Laravel mengecek apakah web sedang perbaikan (*maintenance*), lalu mengaktifkan semua modul pendukung agar siap digunakan.
3. Setelah semuanya siap, berkas ini menerima permintaan pengunjung, memprosesnya ke sistem, dan menampilkan hasilnya di layar pengunjung.

---

### 2. File `bootstrap/app.php` (Manajer Pengatur Sistem)

File: [`bootstrap/app.php`](file:///d:/laragon/www/kampuslms/kampuslms/bootstrap/app.php)

Di Laravel 11/12, file ini berfungsi sebagai pusat pengatur 3 hal penting:

1. **Bagian Rute / Jalan (`withRouting`)**:
   ```php
   ->withRouting(
       web: __DIR__.'/../routes/web.php',
       commands: __DIR__.'/../routes/console.php',
       health: '/up',
   )
   ```
   👉 *Fungsinya untuk memberi tahu Laravel file penunjuk jalan mana saja yang dipakai (seperti `routes/web.php`).*

2. **Bagian Satpam / Filter (`withMiddleware`)**:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       //
   })
   ```
   👉 *Fungsinya seperti tempat menyiagakan satpam. Di sini kita bisa mengatur siapa saja yang boleh masuk atau aturan apa yang harus dilewati sebelum masuk ke halaman tertentu.*

3. **Bagian Penanganan Masalah / P3K (`withExceptions`)**:
   ```php
   ->withExceptions(function (Exceptions $exceptions) {
       //
   })
   ```
   👉 *Fungsinya seperti tim medis. Jika ada error atau aplikasi 'jatuh/rusak', di sinilah kita atur bagaimana pesan error akan ditampilkan ke pengunjung.*

---

### 3. File `routes/web.php` (Papan Penunjuk URL)

File: [`routes/web.php`](file:///d:/laragon/www/kampuslms/kampuslms/routes/web.php)

Isi kode bawaannya:
```php
Route::get('/', function () {
    return view('welcome');
});
```

* **Maksud Kode Ini:** 
  Kalau ada pengunjung mengetik URL utama/domain kita (`/`), tolong ambilkan dan tampilkan halaman desain bernama **`welcome`** (yang ada di folder `resources/views/welcome.blade.php`).

* **Cara Mengubah Teksnya (Bisa dicoba):**
  Ubah kode di atas menjadi seperti ini:
  ```php
  Route::get('/', function () {
      return 'Halo, Selamat Datang di Kampus LMS!';
  });
  ```
  *Simpan file, lalu buka browser dan muat ulang (refresh). Tulisannya akan langsung berubah menjadi pesan di atas!*

---

### 4. Perintah `php artisan route:list` (Daftar Menu Aktif)

Buka terminal di folder project, lalu ketik:
```bash
php artisan route:list
```

Hasil yang muncul di layar:
```text
  GET|HEAD  / ....................................................................................... routes/web.php:5
  GET|HEAD  storage/{path} storage.local › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider...
  PUT       storage/{path} storage.local.upload › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvider...
  GET|HEAD  up ........... vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:219
```

**Penjelasan Hasilnya:**
1. **`GET|HEAD /`** $\rightarrow$ Ini rute halaman utama yang kita atur di file [`routes/web.php`](file:///d:/laragon/www/kampuslms/kampuslms/routes/web.php).
2. **`GET|HEAD up`** $\rightarrow$ Ini rute cek kesehatan otomatis yang dibuat oleh Laravel via [`bootstrap/app.php`](file:///d:/laragon/www/kampuslms/kampuslms/bootstrap/app.php).
3. **`storage/{path}`** $\rightarrow$ Ini rute otomatis Laravel agar file/foto di folder storage bisa diakses dan ditampilkan di web.
