# Catatan Minggu 4 

## Nama : Tresia Uyang
## NIM : 10241072


### READ 

Tanpa AI. Buat form tambah mata kuliah, isi dengan data yang pasti tidak valid (SKS = 99), kirim, lalu jawab:

1. Method apa yang menerima request? Di controller mana?
> Request `POST /courses` ditangani oleh method `store(StoreCourseRequest $request)` di dalam `CourseController.php`.

2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
> Validasi terjadi sebelum baris pertama method controller dijalankan. Ini karena `store()` men-type-hint `StoreCourseRequest`, jadi Laravel harus membentuk objek itu dulu lewat Service Container sebelum controllernya bisa jalan dan di proses pembentukan itulah validasi otomatis dicek. Kalau ada yang gagal, `ValidationException` langsung dilempar, dan kode di dalam `store()` belum sempat dieksekusi sama sekali.

3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
> Laravel me-redirect kembali ke URL form sebelumnya (`/courses/create`), lewat `redirect()->back()`. Yang menentukan tujuannya adalah `Illuminate\Routing\UrlGenerator`, dan sumber utamanya diambil dari `session _previous.url` header `Referer` cuma dipakai sebagai cadangan kalau diminta secara eksplisit, bukan sumber utama.

4. Dari mana `@error('sks')` mengambil pesannya?
> Dari `$errors` (`ViewErrorBag`), yang otomatis dibagikan ke semua view Blade lewat middleware `Illuminate\View\Middleware\ShareErrorsFromSession`. Directive `@error('sks')` mengecek apakah ada error untuk field sks, lalu menampilkan pesan pertamanya lewat `$message`.

5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
> Dari flash session key `_old_input`, yang otomatis kesimpan saat Laravel redirect balik karena validasi gagal (lewat `withInput()`). Nilainya cuma bertahan untuk satu request berikutnya, lalu otomatis hilang.

6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
> Pada DevTools → Application → Cookies, cookie session Laravel biasanya tampil dengan nama `laravel_session`



