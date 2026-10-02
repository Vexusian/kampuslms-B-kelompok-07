# Catatan Praktikum Minggu 4

Nama: Vanessa Marie Tandiarru  
NIM: 10251118  

---

## READ — Telusuri Satu Siklus Form Gagal

Ketika sebuah form tambah mata kuliah dikirim dengan data tidak valid (misalnya SKS = `99`):

1. **Method apa yang menerima request? Di controller mana?**
   > Request HTTP `POST /courses` ditujukan ke method `store(StoreCourseRequest $request)` di dalam [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php).

2. **Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?**
   > Validasi terjadi **sebelum baris pertama method controller dieksekusi**. Laravel menggunakan pipeline middleware dan resolusi dependensi Service Container: saat type-hint `StoreCourseRequest` diinjeksi ke method `store()`, method `validateResolved()` pada form request otomatis terpanggil sebelum controller menerima kontrol eksekusi. Jika validasi gagal, request langsung dihentikan dan dilempar melalui `ValidationException`.

3. **Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**
   > Laravel me-redirect kembali ke URL sebelumnya (*previous URL*, yaitu halaman form `/courses/create`). Penentunya adalah `Illuminate\Routing\Redirector` bawaan Laravel melalui method `redirect()->back()`, yang membaca header HTTP `Referer` atau session `_previous.url`.

4. **Dari mana `@error('sks')` mengambil pesannya?**
   > Mengambil dari session flash `$errors` (`ViewErrorBag`) yang dibagikan otomatis (*shared*) oleh middleware `Illuminate\View\Middleware\ShareErrorsFromSession` ke seluruh view Blade. Directive `@error('sks')` mengecek apakah ada error bag untuk key `'sks'` dan mengekstrak `$message` pertamanya.

5. **Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?**
   > Mengambil nilainya dari flash session key `_old_input` yang disimpan otomatis saat terjadi redirect akibat validasi gagal (`withInput()`). Nilai ini hanya bertahan selama **satu siklus request berikutnya** (*flash lifespan*), dan akan terhapus otomatis setelah request tersebut selesai diproses.

6. **Cookie session Laravel:**
   > Pada DevTools → Application → Cookies, nama cookie session bawaan Laravel adalah **`laravel_session`** (atau `<app_name>_session`). Cookie ini berisi ID sesi terenkripsi yang dicocokkan ke session driver di server.

---

## BREAK — Tujuh Kerusakan

| # | Yang Dicoba | Prediksi | Hasil Pengamatan Riil |
|---|-------------|----------|------------------------|
| **1** | Hapus `@csrf` dari form, lalu kirim | Request ditolak dengan kode status HTTP 419 | **Terbukti**: Muncul error `419 | Page Expired`. Middleware `VerifyCsrfToken` / `ValidateCsrfToken` mendeteksi token CSRF hilang atau tidak valid, sehingga request langsung dihentikan sebelum mencapai controller. |
| **2** | Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl` | Field liar yang lolos fillable akan masuk ke database (*mass assignment vulnerability*) | **Terbukti**: Jika kolom baru ada di database dan masuk `$fillable`, input liar yang tidak tervalidasi ikut tersimpan. Menggunakan `$request->validated()` menjamin hanya field yang didefinisikan dalam `rules()` yang boleh diambil. |
| **3** | Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999` | Gagal di level database (*foreign key constraint failure*) dan menghasilkan data yatim/error 500 jika tanpa FK | **Terbukti**: Muncul error `QueryException: Integrity constraint violation (foreign key constraint fails)`. Tanpa validasi `exists`, database harus menangani error fatal ini alih-alih memberikan pesan ramah kepada user. |
| **4** | Hapus validasi `in:...` pada `status`, kirim `status=superadmin` | Nilai di luar opsi enum berhasil tersimpan ke database | **Terbukti**: Nilai `'superadmin'` tersimpan atau memicu error database jika tipe kolom adalah enum SQL murni. Validasi `in:draft,active,archived` krusial menjaga integritas status aplikasi. |
| **5** | Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2 | Parameter pencarian dan filter hilang di URL halaman 2 | **Terbukti**: URL berubah menjadi `/courses?page=2` tanpa menyertakan `?q=...&status=...`. Hasil tabel menampilkan seluruh data halaman 2 tanpa filter pencarian sebelumnya. |
| **6** | Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan | Browser menampilkan konfirmasi pengiriman ulang form (*Confirm Form Resubmission*) dan data tersimpan dobel | **Terbukti**: Tekan F5 memicu browser mengulangi request POST terakhir. Record baru tersimpan dua kali di database karena melanggar pola PRG (*Post/Redirect/Get*). |
| **7** | Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan | Seluruh inputan pengguna terhapus dan form menjadi kosong kembali | **Terbukti**: Pengguna dipaksa mengisi ulang semua kolom dari awal padahal hanya satu kolom yang salah. Pengalaman pengguna (*UX*) menjadi sangat buruk. |

---

## BUILD — Form dan Daftar yang Layak Pakai

Status kesesuaian implementasi repositori:

1. **`StoreCourseRequest` dan `UpdateCourseRequest` dengan Pesan Bahasa Indonesia:**
   - [StoreCourseRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/StoreCourseRequest.php): Berisi aturan `code` (unique), `name`, `description`, `sks` (between 1-6), `lecturer_id` (exists:users,id), dan `status` (in:draft,active,archived) beserta method `messages()` dalam Bahasa Indonesia yang informatif.
   - [UpdateCourseRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/UpdateCourseRequest.php): Menggunakan `Rule::unique('courses', 'code')->ignore($courseId)` agar saat memperbarui data tidak memicu validasi duplikat terhadap dirinya sendiri.

2. **Form Tambah & Edit Mata Kuliah:**
   - [courses/create.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/courses/create.blade.php): Menggunakan `@csrf`, `value="{{ old('field') }}"`, dan `@error('field')` untuk penanganan pesan kesalahan per kolom.
   - [courses/edit.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/courses/edit.blade.php): Menggunakan `@csrf`, `@method('PUT')`, `value="{{ old('field', $course->field) }}"`, dan `@error('field')`.

3. **Flash Message Sukses/Gagal di Layout Utama:**
   - [layout.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/components/layout.blade.php): Diletakkan di dalam tag `<main>` sehingga mencakup seluruh halaman secara terpusat untuk `session('success')` dan `session('error')`.

4. **Daftar Mata Kuliah (Search, Filter, Pagination):**
   - [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php): Method `index()` mendukung pencarian kode/nama via `q`, filter `status`, dan pagination 15 item dengan `->withQueryString()`.
   - [courses/index.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/courses/index.blade.php): Memiliki form pencarian `q`, filter status dropdown, tombol Reset, dan tautan pagination `{{ $courses->links() }}`.

5. **Penerapan pada Modul Pengguna (User):**
   - [StoreUserRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/StoreUserRequest.php) & [UpdateUserRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/UpdateUserRequest.php): Validasi user dipisahkan ke Form Request dengan pesan Bahasa Indonesia dan `Rule::unique()->ignore()`.
   - [UserController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/UserController.php): Method `index()` memfasilitasi pencarian `q` (nama, email, NIM/NIP), filter `role`, dan pagination `->withQueryString()`.
   - [users/index.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/users/index.blade.php), [users/create.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/users/create.blade.php), dan [users/edit.blade.php](file:///c:/Users/vanes/laragon/www/kampuslms/resources/views/users/edit.blade.php): Sudah dilengkapi form pencarian/filter, `@csrf`, `old()`, dan `@error`.

6. **Konfirmasi Penghapusan & Method `DELETE`:**
   - Penghapusan mata kuliah dan user menggunakan form ber-method `POST` dengan directive `@method('DELETE')`, `@csrf`, serta konfirmasi JavaScript `onsubmit="return confirm(...) "`.

---

## Checkpoint

### 1. Kenapa validasi di JavaScript tidak dianggap keamanan? Peragakan cara melewatinya.
> **Alasan:**  
> JavaScript berjalan di sisi klien (*client-side* / browser), yang berada sepenuhnya di bawah kendali pengguna. Siapa pun dapat mematikan JavaScript di browser, memanipulasi skrip via Console DevTools, atau melewati browser sama sekali dengan mengirimkan HTTP Request langsung ke server menggunakan peralatan seperti `curl`, Postman, atau script Python. Validasi JavaScript murni berfungsi sebagai **kenyamanan pengalaman pengguna** (*User Experience*) untuk memberi respon instan tanpa perlu reload halaman, bukan sebagai mekanisme proteksi/keamanan.
>
> **Peragaan Cara Melewatinya:**  
> Meskipun form HTML memiliki atribut `required`, `max="6"`, atau validasi JavaScript untuk mencegah nilai SKS = 99, penyerang dapat mengeksekusi perintah `curl` langsung ke endpoint server:
> ```bash
> curl -X POST http://kampuslms.test/courses \
>   -H "X-CSRF-TOKEN: <token_csrf>" \
>   -b cookies.txt \
>   -d "code=IF999" \
>   -d "name=Mata Kuliah Ilegal" \
>   -d "sks=99" \
>   -d "lecturer_id=1" \
>   -d "status=active"
> ```
> Validasi JavaScript tidak akan pernah berjalan pada request ini. Karena itu, server wajib melakukan validasi ulang secara independen menggunakan **Form Request Laravel**.

---

### 2. Apa yang dikembalikan `$request->validated()` dan kenapa lebih aman daripada `$request->all()`?
> **Jawaban:**  
> - `$request->validated()` mengembalikan sebuah array asosiatif yang **hanya berisi pasangan key dan value yang didefinisikan secara eksplisit di dalam method `rules()`** pada Form Request dan telah lolos validasi.
> - `$request->all()` mengembalikan seluruh payload yang dikirimkan oleh klien di dalam request HTTP, termasuk token CSRF (`_token`), method spoofing (`_method`), serta field-field liar tambahan yang sengaja diselundupkan penyerang.
>
> **Kenapa lebih aman:**  
> `$request->validated()` menjadi perisai utama terhadap kerentanan **Mass Assignment**. Apabila developer secara tidak sengaja mendaftarkan field sensitif (misalnya `is_admin`, `role`, atau `verified`) ke dalam array `$fillable` model, pemanggilan `Model::create($request->all())` akan langsung memasukkan nilai selundupan tersebut ke database. Sebaliknya, dengan `Model::create($request->validated())`, data liar di luar aturan validasi otomatis dibuang dan diabaikan.

---

### 3. Jelaskan pola PRG. Apa yang terjadi kalau `store` mengembalikan view?
> **Penjelasan Pola PRG (Post → Redirect → Get):**  
> PRG adalah pola arsitektur web di mana setiap request yang mengubah state server (seperti `POST`, `PUT`, atau `DELETE`) diakhiri dengan respon HTTP Redirect (`302 Found` / `303 See Other`) ke endpoint `GET`, bukan merender view HTML secara langsung.
>
> **Siklus Alur:**  
> 1. Klien mengirim `POST /courses` berisi data form baru.
> 2. Server memproses dan menyimpan data ke database.
> 3. Server mengembalikan respon `redirect()->route('courses.index')->with('success', '...')`.
> 4. Browser menerima redirect dan otomatis mengirimkan request `GET /courses`.
> 5. Server merender tampilan daftar mata kuliah via `GET`.
>
> **Apa yang terjadi kalau `store` langsung mengembalikan view (`return view(...)`):**  
> Riwayat request terakhir di browser tetap berstatus `POST`. Jika pengguna menekan tombol **F5 / Refresh**, browser akan menampilkan peringatan dialog *"Confirm Form Resubmission"*. Jika pengguna menekan tombol *"Continue / Resubmit"*, browser akan mengirim ulang request `POST` beserta datanya, menyebabkan **data tersimpan ganda (duplikat)** di database.

---

### 4. Kenapa filter pencarian sebaiknya di query string, bukan session? Beri satu skenario yang rusak kalau dipindah ke session.
> **Jawaban:**  
> Query string (`/courses?q=basis&status=active`) bersifat **idempoten, representasional, dan melekat pada URL**. Session bersifat **stateful dan melekat pada browser/kuki pengguna di sisi server**.
>
> **Alasan memilih query string:**  
> 1. **Dapat Dibagikan (*Shareable*):** URL dengan query string dapat di-copy dan dikirimkan ke pengguna lain, dan penerima akan melihat hasil pencarian yang sama persis.
> 2. **Dapat Di-bookmark:** Pengguna dapat mem-bookmark tautan hasil pencarian spesifik.
> 3. **Mendukung Navigasi Browser (*Back & Forward*):** Riwayat penjelajahan browser bekerja sesuai ekspektasi.
>
> **Skenario Nyata yang Rusak Jika Disimpan di Session:**  
> **Skenario Multi-Tab:** Seorang dosen membuka LMS dalam dua tab browser secara bersamaan:
> - Di **Tab A**, dosen memfilter daftar mata kuliah: `status=draft`. Filter `status=draft` tertulis ke session.
> - Di **Tab B**, dosen membuka tab baru untuk mencari mata kuliah `status=active`. Session tertimpa menjadi `status=active`.
> - Dosen kembali ke **Tab A** dan mengklik Halaman 2. Karena filter dibaca dari session, Tab A tiba-tiba menampilkan mata kuliah dengan status `active`, bukan `draft`. Keadaan ini menyebabkan inkonsistensi data dan membingungkan pengguna (*state pollution antar-tab*).

---

### 5. Apa fungsi `@csrf`? Serangan apa yang dicegahnya, dan bagaimana serangan itu bekerja?
> **Fungsi `@csrf`:**  
> Menghasilkan input hidden `<input type="hidden" name="_token" value="...">` berisi token kriptografi acak yang unik per sesi pengguna. Token ini divalidasi oleh middleware Laravel pada setiap request non-GET (`POST`, `PUT`, `DELETE`).
>
> **Serangan yang Dicegah:**  
> **Cross-Site Request Forgery (CSRF)**.
>
> **Cara Kerja Serangan CSRF:**  
> 1. Seorang Admin login ke `kampuslms.test`. Browser menyimpan cookie sesi otentikasi admin.
> 2. Tanpa logout, Admin membuka tautan berbahaya di tab lain yang mengarah ke situs penyerang (`evil-site.com`).
> 3. Situs penyerang memuat skrip tersembunyi berupa formulir otomatis:
>    ```html
>    <form id="csrfForm" action="http://kampuslms.test/courses/15" method="POST">
>        <input type="hidden" name="_method" value="DELETE">
>    </form>
>    <script>document.getElementById('csrfForm').submit();</script>
>    ```
> 4. Browser Admin secara otomatis menyertakan cookie sesi `kampuslms.test` saat mengirim request tersebut.
> 5. **Tanpa proteksi CSRF:** Server mengira request tersebut sah datang dari Admin dan menghapus mata kuliah ID 15.
> 6. **Dengan proteksi `@csrf`:** Server menolak request dengan kode status `419 Page Expired` karena situs penyerang tidak dapat membaca token rahasia CSRF yang ada di sesi pengguna (*Same-Origin Policy*).

---

### 6. Kenapa aturan `unique` pada update perlu `ignore()`?
> **Jawaban:**  
> Pada operasi **Update**, data yang sedang diedit sudah tersimpan di database. Jika aturan validasi kolom unik ditulis secara polos:
> ```php
> 'code' => ['required', 'unique:courses,code']
> ```
> Saat pengguna hanya ingin mengedit kolom lain (misalnya mengganti nama mata kuliah atau SKS) tanpa mengganti kode mata kuliah, query validasi Laravel akan mengecek: *"Apakah kode ini sudah ada di tabel courses?"*.
>
> Jawabannya adalah **ADA** — yaitu baris milik data itu sendiri. Akibatnya, validasi akan gagal dengan pesan error palsu: *"Kode mata kuliah ini sudah dipakai"*.
>
> Dengan menambahkan method `Rule::unique('courses', 'code')->ignore($courseId)`:
> ```php
> Rule::unique('courses', 'code')->ignore($this->route('course'))
> ```
> Laravel mengecualikan baris dengan ID tersebut (`WHERE id != :courseId`) saat melakukan pengecekan keunikan di database, sehingga pengguna dapat menyimpan perubahan tanpa ditolak oleh datanya sendiri.
