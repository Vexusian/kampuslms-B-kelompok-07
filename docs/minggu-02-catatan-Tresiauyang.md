# Catatan Minggu 2

Nama : Tresia Uyang 

NIM : 10241072

## READ — Telusuri satu request penuh (30 menit)

1. Baris mana di `routes/web.php` yang menangkapnya?

Di `routes/web.php` yang menangkapnya adalah :

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

2. Kalau ditangani controller, berkas dan method mana?

Saat ini route `/tentang` belum menggunakan Controller, tetapi masih ditangani langsung di `routes/web.php`

3. View mana yang dikembalikan? Di path apa persisnya?

View yang dikembalikan adalah `/tetang`

 `resources\views\tentang.blade.php`

4. Layout apa yang membungkusnya?

Layout `tentang` atau `resources\views\tentang.blade.php`.

5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?

iya, sesuai analisis saya 

## BREAK — Delapan kerusakan (40 menit)


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

Nomor 5 wajib benar-benar dicoba, bukan dibayangkan. Melihat `alert` muncul dari data yang Anda "masukkan sebagai pengguna" mengubah cara Anda memandang setiap keluaran di layar selamanya.

## FIX — Repo cacat (30 menit)

Branch `w02` pada repo `kampuslms-broken` berisi modul mata kuliah dengan **6 masalah**: satu route saling menutupi, satu method HTTP salah, dua URL hardcode, satu XSS, dan satu logika query yang seharusnya tidak berada di view.

Temukan semuanya, perbaiki, kirim PR. **Deskripsi PR wajib menjelaskan risiko dari tiap masalah**, bukan sekadar "sudah diperbaiki".

## BUILD — Kerangka KampusLMS (sisa waktu)

Masih memakai data statis (array di controller) — database baru masuk minggu depan.

1. Layout `x-layout` dengan navbar berisi: Dashboard, Mata Kuliah, Tentang.
2. `CourseController` dengan `index` dan `show`.
3. `courses/index.blade.php` — tabel daftar mata kuliah (kode, nama, SKS, dosen).
4. `courses/show.blade.php` — detail satu mata kuliah.
5. Semua tautan memakai `route()`.
6. Halaman 404 kustom (`resources/views/errors/404.blade.php`).
7. Minimal 2 anggota berbeda membuat commit; PR di-review anggota lain sebelum merge.