# Catatan Minggu 6 

## Nama : Tresia Uyang 
## NIM : 10241072

### READ 

### 1. Perubahan `bootstrap/app.php` setelah `php artisan install:api`
>Setelah menjalankan perintah `php artisan install:api`, terdapat perubahan pada konfigurasi `bootstrap/app.php`. Laravel menambahkan pendaftaran fil 
```php
withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
)
```
> Setelah menjalankan `php artisan install:api`, pada `bootstrap/app.php` terdapat konfigurasi `api` yang mengarah ke `routes/api.php`. Ini menunjukkan bahwa laravel sekarang sudah mendaftrakan jalur khusus utuk routing API. Sebelumnya aplikasi hanya menggunakan routing web sedangkan setelah konfigurasi ini terdapat jalur API yang dapat digunakan untuk membuat endpoint seperti `/api/v1/courses`.

#### 2. Perbandingan `CourseController` Versi Web vs API

| Aspek | Jalur Web | Jalur API |
|-------|-----------|-----------|
| Output | Menghasilkan halaman atau tampilan menggunakan blade | Menghasilkan data dalam bentuk JSON |
|Autentikasi | Menggunakan mekanisme autentikasi dan sessio pada aplikasi web | Menggunakan token melalui laravel sanctum |
| CSRF | request web menggunakan perlindungan CSRF | Request ApI tidak menggunakan CSRF seperti pada form web |
| Penanganan Error | Error dapat diarahkan kembali ke halaman sebelumnya atau ditampilkan melalui halaman web | Error diberikan melalui response JSON dengan status HTTP |
| Penyajian data | Error dapat diarahkan kembali ke halaman sebelumnya atau ditampilkan melalui halaman web | Data disiapkan sebagai response yang dapat digunakan oleh client API | 

#### 3. Perbedaan Request tanpa dan dengan header `Accept: application/json`
> ketika request dikirim tanpa header `accept: application/json, laravel dapat memperlakukannya seperti request dari aplikasi web. Jika terjadi error, response yang diberikan dapat berupa halaman HTML atau redirect.

>Sedangkan ketika request menggunakan:
``` 
Accept: application/json
```
> Laravel menegtahui bahwa client mengharapkan respons dalam bentuk JSON. Oleh karena itu, ketika terjadi error, response diberikan dalam format JSON beserta status HTTP yang sesuai, misalnya `401` untuk masalah autentikasi atau `422` untuk kesalahan validasi. Jadi, perbedaan utamanya terdapat pada bentuk response yang diterima oleh client.

### 4. Hasil Verifikasi Route API 
> Untuk melihat route yang digunakan oleh API, perintah yang dijalankan adalah:

```bash
php artisan route:list --path=api
```
>Hasilnya menunjukkan bahwa endpoint API pada project sudah terdaftar, di antaranya:

```
POST       api/v1/assignments
PUT|PATCH  api/v1/assignments/{assignment}
DELETE     api/v1/assignments/{assignment}
GET|HEAD   api/v1/assignments/{assignment}/submissions
POST       api/v1/assignments/{assignment}/submissions
POST       api/v1/auth/login
POST       api/v1/auth/logout
GET|HEAD   api/v1/courses
GET|HEAD   api/v1/courses/{course}
GET|HEAD   api/v1/courses/{course}/assignments
GET|HEAD   api/v1/courses/{course}/materials
GET|HEAD   api/v1/me
GET|HEAD   api/v1/notifications
POST       api/v1/notifications/{id}/read
PUT        api/v1/submissions/{submission}/grade
```
> Salah satu endpoint yang terdapat pada daftar tersebut adalah `GET|HEAD   api/v1/courses` yang diarahkan ke `Api\CourseController@index` Dari hasil tersebut dapat dilihat bahwa route API untuk kebutuhan project sudah tersedia dan menggunakn struktur `/api/v1/...`.


### BREAK 

| # | Percobaan | Hasil Pengamatan Rill | Analisi Keamanan |
|---|-------------|------------------------| --------------|
| 1 | Mengembalikan `response()->json(User::all())` | Data pengguna dapat tampil secara langsung, temasuk data seperto `password`,`remember_token`, dan `email_verified_at`. | Kecocoran data. Data sensitif tidak seharusnya dikirim langsung melalui API karena dapat dimanfaatkan oleh pihak yang tidak berwenang. |
| 2 | Menghapus middleware `auth:sanctum` dari route group | Endpoint seperti `/api/v1/courses` dan /api/v1/me` dapat diakses tanpa melakukan login atau mengirim token | Broken Authentication. Endpoint yang seharusnya dilindungi menjadi dapat diakses oleh siapa saja |
| 3 | Menggunakan token yang sudahh tidak valid atau sudah dihapus | server memberikan response HHTP 401 Unauthorized dengan pesan `unauthenticated` | Token yang sudah tidak valid tidak dapat digunakan untuk mengakses endpoint yang dilindungi. Sanctum melakukan pegecekan autentikasi sebelum request diproses.|
| 4 | Login sebagai mahasiswa lalu mengirim `POST/api/v1/assignments` | server memberikan response HTTP 403 Forbidden dengan pesan tidak memiliki akses. | 403 berbeda dengan 401. user sudah berhasil login, tetapi tidak memiliki hak untuk melakukan aksi tersebut |
| 5 | Menghapus eager loading `with(`lecturer`)` pada endpoint courses | Data lecturer harus diambil kembali untuk setiap courses sehingga dapat menghasilkan banyak query database | N+1 Query Problem. Kondisi ini membuat penggunaan database menjadi lebih boros dan dapat menurunkan performa ketika jumlah data semakin banyak. |
| 6 | Menghapus `throttle:5,1` pada endpoint login dan melakukan banya percobaan login | Request login dapat dikirim berulang kali tanpa pembatasan dari rate limit tersebut. | Brute-force vulnerability. Penyerang dapat mencoba banyak kombinasi email dan password secara terus menerus|
| 7 | Membuat pesan login berbeda antara email tidak terdaftar dan password salah | Dari pesan yang berbeda, seseorang dapat mengetahui apakah sebauh email terdaftar di sistem. | User Enumeration. Informasi mengenai akun yang terdaftar dapat dimanfaatkan sebagai langkah awal untuk serangan berikutnya |

### FIX — Repo cacat 

Berdasarkan masalah yang ditemukan pada branch `w06`, dilakukan beberapa perbaikan pada API sebagai berikut:

>1. Model mentah: Penggunaan model secara langsung pada endpoint API diperbaiki dengan menggunakan API Resource seperti `UserResource`, `CourseResource`, `MaterialResource`, `AssignmentResource`, `SubmissionResource`, dan `GradeResource`. Dengan cara ini, data yang dikirim dapat dikontrol dan informasi sensitif tidak ikut ditampilkan.

>2. Endpoint tanpa autentikasi : Endpoint yang seharusnya bersifat privat ditempatkan dalam middleware `auth:sanctum`. Pengguna harus memiliki token yang valid sebelum dapat mengakses endpoint tersebut.

>3. Status code store: Method store diperbaiki agar ketika data berhasil dibuat, API mengembalikan status 201 Created menggunakan `setStatusCode(201)`.

>4. Status Code Destroy: Method destroy diperbaiki agar proses penghapusan data menghasilkan status 204 No Content dengan menggunakan `response()->noContent()`.

>5. Kesalahan 403 dan 401: Pengecekan autentikasi dipisahkan dari pengecekan hak akses. Pengguna yang belum terautentikasi mendapatkan 401, sedangkan pengguna yang sudah login tetapi tidak mempunyai izin mendapatkan 403.

>6. Rate Limiting Login: Endpoint `POST /api/v1/auth/`login diberikan middleware throttle:5,1 untuk membatasi percobaan login dan mengurangi risiko brute-force.

>7. Pesan Login: Pesan kegagalan login dibuat seragam menjadi `"Email atau kata sandi salah."` agar sistem tidak memberikan informasi mengenai apakah email tertentu terdaftar.

>8. N+1 Query: Pengambilan relasi diperbaiki dengan menggunakan eager loading `with()` pada query daftar data dan `whenLoaded()` pada API Resource. Hal ini dilakukan agar query database yang berulang dapat dikurangi.

