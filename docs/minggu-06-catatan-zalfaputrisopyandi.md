## Catatan Minggu 6

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### 1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.  
Menjalankan perintah `php artisan install:api` di Laravel 12 akan secara otomatis:
- Membuat file rute API baru di `routes/api.php`.
- Mendaftarkan file rute `api.php` dan middleware API ke dalam konfigurasi aplikasi di `bootstrap/app.php`.  

Perubahan pada `bootstrap/app.php`:
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <-- Baru ditambahkan untuk mendaftarkan rute API
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

### 2. Buat satu endpoint `GET /api/v1/courses` sederhana.  
- Rute di `routes/api.php`:
    ```php
    use App\Http\Controllers\Api\v1\CourseController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('v1')->group(function () {
        Route::get('/courses', [CourseController::class, 'index']);
    });
    ```

- Controller di `app/Http/Controllers/Api/v1/CourseController.php`:
    ```php
    namespace App\Http\Controllers\Api\v1;

    use App\Http\Controllers\Controller;
    use App\Models\Course;
    use Illuminate\Http\JsonResponse;

    class CourseController extends Controller
    {
        public function index(): JsonResponse
        {
            $courses = Course::all();

            return response()->json([
                'success' => true,
                'data' => $courses
            ], 200);
        }
    }
    ```

### 3. Bandingkan dengan `CourseController` versi web yang sudah ada. Tulis di catatan: apa yang sama dan apa yang berbeda di antara keduanya?  
| Aspek | Web Controller (`app/Http/Controllers/CourseController.php`) | API Controller (`app/Http/Controllers/Api/v1/CourseController.php`) |
| :--- | :--- | :--- |
| **Persamaan** | Sama-sama mengambil data dari Model (misal: `Course::all()`) dan memproses logika bisnis course. | Sama-sama mengambil data dari Model (misal: `Course::all()`) dan memproses logika bisnis course. |
| **Format Output** | Mengembalikan tampilan Blade HTML (`return view('courses.index', compact('courses'));`). | Mengembalikan data terstruktur JSON (`return response()->json(...);`). |
| **Response Error** | Mengembalikan halaman error HTML atau *redirect* dengan *session flash message*. | Mengembalikan JSON *error response* beserta *HTTP Status Code* (misal: `404`, `422`, `401`). |
| **State/Session** | Menggunakan *Session* & *CSRF Token* (Stateful). | *Stateless* (umumnya menggunakan Token/Sanctum untuk autentikasi). |

### 4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.  
- **Tanpa Header (`Accept: text/html` atau kosong)**:
    - **Respon Sukses:** Tetap mengembalikan respon JSON jika controller langsung memanggil `response()->json()`.
    - **Respon Error/Validasi/Unauthenticated:** Laravel akan mencoba menganggap permintaan sebagai request browser biasa, sehingga jika terjadi error/unauthenticated (misal 401), Laravel akan mengembalikan respon berupa Redirect ke halaman login/HTML error page, bukan JSON.

- **Dengan Header `Accept: application/json`:**
    - **Respon Sukses & Error:** Laravel dipaksa untuk selalu mengembalikan respon berformat JSON dalam semua kondisi (termasuk saat terjadi validasi gagal 422, unauthenticated 401, atau server error 500).

### 5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.  
Hasil eksekusi perintah `php artisan route:list --path=api`:

```
  POST            api/v1/assignments ................................... Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} ..................... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} .................... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions .... Api\AssignmentController@submissions
  POST            api/v1/assignments/{assignment}/submissions Api\AssignmentController@storeSubmission
  POST            api/v1/auth/login .......................................... Api\AuthController@login
  POST            api/v1/auth/logout ........................................ Api\AuthController@logout
  GET|HEAD        api/v1/courses ........................................... Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} ................................... Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments ................ Api\CourseController@assignments
  GET|HEAD        api/v1/courses/{course}/materials .................... Api\CourseController@materials
  GET|HEAD        api/v1/me ..................................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications ............................... Api\NotificationController@index
  POST            api/v1/notifications/{id}/read ................ Api\NotificationController@markAsRead
  PUT             api/v1/submissions/{submission}/grade ................ Api\SubmissionController@grade
  GET|HEAD        api/v1/test-user-raw .............................................. routes/api.php:16
  GET|HEAD        api/v1/test-user-resource ......................................... routes/api.php:20

                                                                                    Showing [17] routes
```

Hasil Evaluasi dan Pencocokan Spesifikasi:
- URI Prefix: Rute terdaftar pada path `api/v1/courses`, sudah sesuai dengan standar versi API (`v1`).
- HTTP Method: Menggunakan metode `GET` sesuai dengan spesifikasi untuk mengambil daftar data course.
- Middleware: Menggunakan layer middleware `api` bawaan Laravel untuk memproses API request.

## BREAK  
| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Kembalikan `response()->json(User::all())` di satu endpoint uji | **Email dan seluruh kolom pengguna tampil di layar.** Lalu hapus sementara `$hidden` dari model `User` dan ulangi: hash password ikut tampil |
| 2 | Hapus `auth:sanctum` dari grup route, panggil tanpa token | Data terbuka untuk publik |
| 3 | Panggil endpoint terlindungi dengan token yang sudah dihapus | 401 |
| 4 | Login sebagai mahasiswa, panggil `POST /api/v1/assignments` | Harus 403, bukan 401 — periksa punya Anda |
| 5 | Hapus eager loading, panggil daftar mata kuliah, lihat Telescope/Debugbar | N+1 di API |
| 6 | Hapus `throttle` dari login, jalankan 50 percobaan berturut-turut | Brute force tanpa hambatan |
| 7 | Buat pesan login berbeda untuk email salah vs password salah | *User enumeration* — kenapa ini berbahaya |

## FIX  
Branch `w06` pada repo `kampuslms-broken` berisi **8 masalah**: model mentah dikembalikan pada dua endpoint, satu endpoint tanpa `auth:sanctum`, status code salah pada `store` dan `destroy`, 403 dikembalikan sebagai 401, login tanpa throttle, pesan login membocorkan keberadaan email, dan N+1 pada endpoint daftar.  
### Tabel Perbaikan
| No. | Masalah | Lokasi File | Perubahan yang Dilakukan |
|---|---|---|---|
| 1 | Endpoint `/v1/auth/me` mengembalikan model User secara langsung | `app/Http/Controllers/Api/AuthController.php` | Mengubah response agar menggunakan `UserResource`, sehingga data user yang dikembalikan lebih terkontrol. |
| 2 | Endpoint `/v1/users` mengembalikan model User secara langsung | `app/Http/Controllers/Api/UserController.php` | Mengubah response menjadi `UserResource::collection()` agar data user dikembalikan melalui Resource. |
| 3 | Endpoint `/v1/users` tidak menggunakan autentikasi `auth:sanctum` | `routes/api.php` | Menambahkan middleware `auth:sanctum` pada route `/v1/users`. |
| 4 | Endpoint `store` mengembalikan status code `200 OK` | `app/Http/Controllers/Api/CourseController.php` | Mengubah status response menjadi `201 Created` karena berhasil membuat resource baru. |
| 5 | Endpoint `destroy` mengembalikan status code `200 OK` | `app/Http/Controllers/Api/CourseController.php` | Mengubah response menjadi `204 No Content` karena resource berhasil dihapus dan tidak perlu mengembalikan body. |
| 6 | Akses yang tidak memiliki izin mengembalikan `401 Unauthorized` | `app/Http/Controllers/Api/CourseController.php` | Mengubah status code menjadi `403 Forbidden` karena user sudah terautentikasi tetapi tidak memiliki hak akses. |
| 7 | Endpoint login tidak memiliki throttle dan pesan error membocorkan keberadaan email | `app/Http/Controllers/Api/AuthController.php` dan `routes/api.php` | Menambahkan `throttle:5,1` pada login dan menggunakan pesan error yang sama untuk email/password yang salah agar mencegah user enumeration. |
| 8 | Endpoint daftar course mengalami N+1 query | `app/Http/Controllers/Api/CourseController.php` | Menambahkan eager loading dengan `with('lecturer')` sehingga data lecturer diambil lebih efisien dalam query yang lebih sedikit. |

## BUILD  
1. `php artisan install:api`, Sanctum terpasang.
2. Seluruh endpoint di Bagian 5 spesifikasi terimplementasi, **format response persis sesuai kontrak**.
3. API Resource untuk User, Course, Material, Assignment, Submission, Grade.
4. Eager loading di semua endpoint koleksi.
5. Rate limiting: 60/menit umum, 5/menit untuk login.
6. `docs/api.md` berisi dokumentasi lengkap dengan contoh `curl`.
7. `scripts/test-api.sh` berisi skrip pengujian otorisasi dari Prompt C.
8. **Kelompok mendaftarkan jalur frontend** untuk minggu 7–16 kepada dosen.

**Dari BUILD ini, kami sudah mengerjakan dan sudah bisa menghasilkan sesuai ketentuan di BUILD.**