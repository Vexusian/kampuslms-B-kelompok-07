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

| # | Yang dirusak | Prediksi | Yang Anda pelajari |
|---|--------------|--------------------|--------------------|
| 1 | Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah | Akan muncul error karena _method_ yang tidak tepat | Method HTTP tidak cocok → 405
| 2 | Ubah nama view di `return view(...)` menjadi yang tidak ada | Akan muncul eror not found | Exception view not found |
| 3 | Hapus `->name('courses.show')`, lalu muat halaman yang memakai `route('courses.show')` | Laman dengan URL `/courses` tidak akna muncul karena di dalamnya terdapat penggunaan nama route `courses.show`. Sehingga jika namanya diganti, Laravel tidak lagi mengenali nama route tersebut. | Kenapa nama route wajib |
| 4 | Pindahkan `/courses/{course}` ke ATAS `/courses/create`, lalu buka `/courses/create` | `create` akan dianggap sebagai parameter _input_ `{course}` pada `/courses/{course}` sehingga hal ini akan menimbulkan eror `404 not found` | Urutan route menentukan |
| 5 | Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, isi `$nama` dengan `<script>alert('XSS')</script>` | `{{ $nama }}` akan menampilkan script sebagai teks dan tidak menjalankannya. Sedangkan `{!! $nama !!}` akan menjalankan script sehingga muncul alert XSS. | **XSS nyata di layar Anda sendiri** |
| 6 | Hapus `@vite(...)` dari layout | Karena berhubungan dengan CSS dan JS, maka tampilan UI akan mengalami perubahan | Aset tidak termuat |
| 7 | Hentikan `npm run dev` lalu muat ulang halaman | Halaman yang melibatkan `courses` akan menampilkan eror not found | Beda dev server vs build |
| 8 | Panggil `route('courses.show')` tanpa mengirim parameter | Karena tidak ada parameter _input_ Laravel akan menganggapnya sebagai `/course/` dan menampilkan seluruh daftar mata kuliah sama seperti `/course` | Missing required parameter |

# Checkpoint Minggu 2

## 1. Kenapa menghapus data lewat `GET` berbahaya? Beri satu skenario konkret.

Menghapus data lewat `GET` berbahaya karena method `GET` seharusnya digunakan untuk mengambil atau membaca data, bukan untuk melakukan perubahan atau penghapusan data. Request `GET` dapat dipanggil secara tidak sengaja, misalnya melalui crawler, bookmark, atau ketika URL dibuka oleh pengguna.

Contoh konkretnya, jika terdapat route:

```php
Route::get('/courses/5/delete', function () {
    // Menghapus mata kuliah dengan ID 5
});

```
## 2. Apa yang terjadi kalau /courses/{course} ditulis sebelum /courses/create? Kenapa?
Jika /courses/{course} ditulis sebelum /courses/create, ketika URL /courses/create diakses, Laravel akan mencocokkannya terlebih dahulu dengan route /courses/{course}.

Hal ini terjadi karena Laravel mencocokkan route berdasarkan urutan dari atas ke bawah dan menggunakan route pertama yang cocok.

## 3. Tunjukkan di kode Anda satu tempat yang memakai route(). Apa untungnya dibanding URL hardcode?
Salah satu penggunaan route() terdapat pada file `resources/views/courses/index.blade.php`:

``` html
<a href="{{ route('courses.show', ['course' => $course['id']]) }}">
    Detail
</a>
```
Route tersebut menggunakan nama:

``` html
Route::get('/courses/{course}', [CourseController::class, 'show'])
    ->name('courses.show');
```

Keuntungannya dibandingkan URL hardcode adalah kode tidak bergantung langsung pada struktur URL. Jika URI:

``` php
/courses/{course}
```

diubah menjadi misalnya:
``` php
/mata-kuliah/{course}
```

saya cukup mengubah URI pada definisi route, sedangkan pemanggilan:

```php
route('courses.show', ['course' => $course['id']])
```

tetap dapat digunakan selama nama route courses.show tidak berubah.

Dengan demikian, penggunaan `route()` membuat kode lebih mudah dikelola ketika struktur URL berubah.

## 4. Apa beda {{ }} dan {!! !!}? Peragakan XSS yang Anda buat di bagian BREAK.

{{ }} digunakan untuk menampilkan data dengan HTML _escaping_ otomatis oleh Blade. Hal ini membuat karakter HTML dari input pengguna tidak langsung dianggap sebagai kode HTML atau JavaScript oleh _browser_.

## 5. Apa fungsi @vite? Apa beda npm run dev dan npm run build?
@vite adalah directive Blade yang digunakan untuk menghubungkan view Laravel dengan asset frontend yang dikelola oleh Vite, seperti CSS dan JavaScript.

`npm run dev` digunakan ketika melakukan pengembangan aplikasi. Perintah ini menjalankan Vite dalam mode development sehingga _asset frontend_ dapat diproses oleh development server. Sedangkan `npm run build` digunakan untuk membuat hasil build asset yang siap digunakan untuk _deployment_.

## 6. Jelaskan mengapa data dari `Request` tidak boleh dipercaya.

Data dari Request tidak boleh langsung dipercaya karena request berasal dari client atau pengguna, sedangkan client berada di luar kendali server.

Pengguna dapat memodifikasi request, menambahkan parameter, atau mengirim request secara manual tanpa menggunakan form yang disediakan aplikasi.