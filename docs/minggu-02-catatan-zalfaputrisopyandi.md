## Catatan Minggu 2

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### Ambil route `/tentang yang Anda buat minggu lalu.

### 1. Baris mana di `routes/web.php` yang menangkapnya? 
**Jawab:**  
Baris yang berisi `Route::get('/tentang', function () {
    return view('tentang');`

### 2. Kalau ditangani controller, berkas dan method mana?
**Jawab:**  
Tidak menggunakan controller. Route ini ditangani langsung oleh Closure (fungsi anonim `function () { ... }`) yang ditulis langsung di dalam file `routes/web.php`.

### 3. View mana yang dikembalikan? Di path apa persisnya?
**Jawab:**  
View yang dikembalikan bernama `tentang` (dari kode `return view('tentang');`).  
Path persisnya di: `resources/views/tentang.blade.php`

### 4. Layout apa yang membungkusnya?
**Jawab:**  
Tidak ada layout yang membungkus. View `tentang.blade.php` yang kita buat berdiri sendiri. File tersebut sudah berisi struktur HTML lengkap (mulai dari `<!DOCTYPE html>` hingga `</html>`), sehingga tidak menggunakan komponen `<x-layout>`.

### 5. Jalankan php artisan `route:list --path=tentang`. Cocok dengan analisis Anda?
**Jawab:**  
Ya cocok dengan analisis saya

## BREAK  
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

