# Nama : Wahyu Ramadan
# NIM : 10241074

### READ — Telusuri satu request penuh (30 menit)

Ambil route `/tentang` yang Anda buat minggu lalu. Tanpa AI, tulis di catatan Anda:

1. Baris mana di `routes/web.php` yang menangkapnya?
2. Kalau ditangani controller, berkas dan method mana?
3. View mana yang dikembalikan? Di path apa persisnya?
4. Layout apa yang membungkusnya?
5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?

### Jawaban READ
1. untuk `/tentang` pada baris `routes/web.php` ditangkap melalui baris 

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang'); 
```
2. `/tentang` tidak ditangani controller melainkan `/routes/web.php`
3. Untuk `/tentang` dikembalikan pada path `resources\views\tentang.blade.php`
4. Layout yang membungkusnya pada bagian `resources\views\tentang.blade.php`
5. sudah sesuai 


### BREAK — Delapan kerusakan (40 menit)

Lakukan berurutan. Untuk setiap nomor, **tulis prediksi Anda dulu** sebelum menjalankan.

| # | Yang dirusak | Yang Anda pelajari |
|---|--------------|--------------------|
| 1 | Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah | Method HTTP tidak cocok → 405 |
| 2 | Ubah nama view di `return view(...)` menjadi yang tidak ada | Exception view not found |
| 3 | Hapus `->name('courses.show')`, lalu muat halaman yang memakai `route('courses.show')` | Kenapa nama route wajib |
| 4 | Pindahkan `/courses/{course}` ke ATAS `/courses/create`, lalu buka `/courses/create` | Urutan route menentukan |
| 5 | Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, isi `$nama` dengan `<script>alert('XSS')</script>` | **XSS nyata di layar Anda sendiri** |
| 6 | Hapus `@vite(...)` dari layout | Aset tidak termuat |
| 7 | Hentikan `npm run dev` lalu muat ulang halaman | Beda dev server vs build |
| 8 | Panggil `route('courses.show')` tanpa mengirim parameter | Missing required parameter |

