# Catatan Praktikum 

Nama: Vanessa Marie Tandiarru  
NIM: 10251118

## READ — Menelusuri Satu Request Penuh
1. Baris mana di routes/web.php yang menangkapnya? 
> `Route::get('/tentang', function () {
    return view('tentang');
});`

2. Kalau ditangani controller, berkas dan method mana?
> Saat ini route `/tentang` belum menggunakan Controller, tetapi masih ditangani langsung menggunakan Closure pada `routes/web.php`

3. View mana yang dikembalikan? Di path apa persisnya?
> View `resources\views\tentang.blade.php`

4. Layout apa yang membungkusnya?
> Layout `tentang` atau `resources\views\tentang.blade.php`.

5. Jalankan php artisan route:list --path=tentang. Cocok dengan analisis Anda?
> Ya

## BREAK — Delapan Kerusakan

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

