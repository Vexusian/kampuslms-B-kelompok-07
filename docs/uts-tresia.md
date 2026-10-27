## B.individu

#### 1. Tunjukkan migrasi yang Anda tulis. Jelaskan setiap constraint di dalamnya.

> - buka folder database/migrations
>- buka file database\migrations\2026_09_12_000003_create_course_user_table.php
> - jelaskan constrain-nya:
>- foreignId : constrain untuk menetapkan bahwa kolom tersebut merupakan FK
>- constrained : constrain untuk merujuk ke tabel induk
>- cascadeOnDelete: constrain untuk menentukan perilaku OnDelete-nya
>- unique(['course_id', 'user_id']) constrain untuk mengatur agar pasangan course_id dan user_id tidak ada yang duplikat (jelaskan contohnya)

#### 2. Kenapa `course_user` punya unique composite? Peragakan tanpa itu.
>- unique(['course_id', 'user_id']) constrain untuk mengatur agar pasangan course_id dan user_id tidak ada yang duplikat (jelaskan contohnya)
>- buka file migrations course_user
>- jadikan komen bagian $table->unique(['course_id', 'user_id']); lalu save 
>- jalankan di terminal: php artisan migrate:fresh
>- jalankan di terminal: php artisan db:seed
>- buka di chrome: localhost/phpmyadmin
>- masukkan username: root, password kosong
>- cari dan buka database kampuslms
>- klik tabel course_user
>- di bagian atas: cari dan tekan tombol insert
>- masukkan data duplikat

#### 3. Apa itu mass assignment? Tunjukkan apa yang mencegahnya di kode Anda.

 **Definisi:** Kerentanan di mana pengguna menyisipkan parameter berbahaya pada HTTP request (contoh: `role=admin`) dan sistem langsung memasukkannya ke database lewat `Model::create($request->all())`.
- **Pencegahan di Kode:**
  1. **Whitelist Model (`$fillable`):** Pada [app/Models/User.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Models/User.php#L24-L29), kolom `role` **tidak dicantumkan**.
  2. **Form Request (`$request->validated()`):** Pada [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L94), pemanggilan `Course::create($request->validated())` hanya mengambil field yang lolos validasi `rules()`.

---

#### 4. Kenapa `role` tidak ada di `$fillable`? Di mana ia diisi?
- $fillable untuk menentukan field atau atribut mana saja yang boleh diisi oleh user
- role tidak boleh pakai $fillable karena user akan bisa pilih sendiri role-nya, misal mahasiswa memilih role admin sehingga bisa meng-utak-atik sistem
- sebagai gantinya, dimana dia diisi? buka file UserController.php 
- cari baris 39: $user->role = 'mahasiswa';
- di sini user yang baru mendaftar akan otomatis dapat role sebagai mahasiswa
- selain itu, kami juga ada memasukkan data menggunakan seeder (buka file database/seeders/DatabaseSeeder.php baris 24)
- di sini kami yang mengatur langsung apa role-nya

#### 5. Tunjukkan satu Policy yang Anda tulis. Jelaskan tiap barisnya.

**Berkas:** [app/Policies/CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L25-L58)

```php
public function view(User $user, Course $course): bool
{
    if ($user->role === 'admin') {
        return true; // 1. Admin berhak melihat seluruh mata kuliah
    }

    if ($user->role === 'dosen') {
        return $course->lecturer_id === $user->id; // 2. Dosen hanya melihat MK miliknya
    }

    if ($user->role === 'mahasiswa') {
        return $course->students()->where('users.id', $user->id)->exists(); // 3. Mahasiswa terdaftar
    }

    return false; // 4. Default deny untuk role lain
}

public function update(User $user, Course $course): bool
{
    return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
}
```

#### 6. Kenapa `@can` di Blade tidak cukup? Peragakan.

- **Alasan:** `@can` hanya mengontrol antarmuka (UI). `@can` hanya menyembunyikan tombol di browser pengguna, tetapi **tidak melindungi endpoint server**.
- **Peragaan Serangan:**
  Dosen B mengirim HTTP PUT langsung via curl/Postman ke mata kuliah Dosen A:
  ```bash
  curl -X POST http://localhost:8000/courses/1 -d "_method=PUT" -d "name=Hack" -b cookies.txt
  ```
- **Pertahanan:** Server memanggil `Gate::authorize('update', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L139), sehingga request langsung ditolak dengan status **HTTP 403 Forbidden**.

#### 7. Apa beda 401 dan 403? Tunjukkan masing-masing satu di API Anda.

- Kode 401 dan 403 sama-sama kode status HTTP
- 401 muncul karena pengguna yang tidak dikenali, belum login sehingga tidak terautentikasi. 
- 403 muncul karena pengguna sudah login dan terautentikasi tapi ternyata tidak punya akses terhadap data tersebut

- Untuk contoh 401, bisa dilihat di routes/api.php baris 28 khususnya bagian 'auth:sanctum' 
- Bagian ini memastikan request yang datang sudah terautentikasi. Jika tidak, request akan ditolak dengan kode eror 401
 
- Untuk Contoh 403, bisa dilihat di Controllers/Api/AssignmentController.php pada baris 42-44
- Di sini user sudah berhasil login , sehingga identitasnya dikenali. Tapi role-nya tidak punya izin untuk membuat assignment baru, sehingga muncul 403 Forbidden.

#### 8. Kenapa `$request->validated()` lebih aman daripada `$request->all()`?

- $request->validated() mengembalikan array berisi input user yang sudah lolos proses validasi sesuai aturan yang didefinisikan dalam rules().
- validated() lebih aman karena data yang diteruskan ke proses berikutnya sudah dibatasi berdasarkan rules yang kita tentukan.
- Sedangkan $request->all() mengambil seluruh input yang dikirim user, termasuk field tambahan yang tidak kita tentukan dalam rules. Kalau field tambahan tersebut ikut diproses, hal itu dapat menimbulkan risiko seperti mass assignment.

Jadi sederhananya:
- validated() = ambil data yang sudah divalidasi dan diizinkan oleh rules.
- all() = ambil semua input dari user.

Catatan penting: validated() sendiri bukan yang melakukan validasi. rules() memberi aturan-aturan validasinya, lalu Laravel lah yang menjalankan proses validasi tersebut. Setelah lolos, validated() mengambil hasilnya.

Tunjukkan letaknya:
* tekan Ctrl + Shift + F
* ketikkan validated( untuk menunjukkan di file mana, request user diatur untuk divalidasi JANGAN PAKAI TUTUP KURUNG
* ketikkan rules( untuk menunjukkan rules atau aturan yang mengatur request user


#### 9. Kenapa filter pencarian ada di query string, bukan session?
- Query string adalah parameter yang disimpan di URL. Jadi ketika kita menggunakan fitur pencarian atau filter, kondisi filter ikut menjadi bagian dari URL.
- Session menyimpan data sementara untuk sesi pengguna di server. Jadi kalau filter disimpan di session, filter tersebut tidak terlihat langsung di URL.
- Query string lebih cocok untuk pencarian, filter, dan pagination karena kondisi halaman menjadi bagian dari URL. Akibatnya, URL tersebut bisa di-refresh atau dibagikan dan kondisi filetrnya tetap ikut terbawa.

> Pagination itu kalau misal ada 100 baris data yang akan ditampilkan di web, namun web membaginya ke berapa halaman. Misal pagination-nya diatur 15, maka 1 halaman itu maksimal menampilkan 15 barus data saja. Sisanya ditarindi halaman berikutnya lagi, jadi kita harus menekan tombol next atau semacamnya.

Skenario kalau menggunakan session:

Misalnya di tab pertama kita membuka web shopping dan menggunakan filter wilayah Kalimantan. Kemudian di tab kedua kita membuka web yang sama dan menggunakan filter wilayah Sulawesi.

Karena kedua tab tersebut menggunakan session pengguna yang sama, ketika tab kedua mengubah filter di session menjadi Sulawesi, nilai filter yang dibaca oleh tab pertama juga dapat berubah menjadi Sulawesi. Jadi state filter antar-tab dapat saling memengaruhi.

Selain itu, karena filter tidak tertulis di URL, maka ketika URL tersebut dibagikan atau dibuka di browser lain, informasi filternya tidak ikut terbawa.

Antisipasi kalau ditanya "kenapa query string bisa mempertahankan filter saat pindah halaman?"
- Karena parameter pencarian/filter berada di URL. Pagination dapat mempertahankan parameter tersebut menggunakan withQueryString()

#### 10. Kenapa `session()->regenerate()` dipanggil setelah login?
**Berkas:** [app/Http/Controllers/AuthController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AuthController.php#L48)

- **Fungsi:** Mencegah serangan **Session Fixation**.
- **Mekanisme:** Penyerang yang menyematkan ID sesi kosong sebelum korban login tidak akan dapat membajak akun korban, karena Laravel menghancurkan ID sesi lama dan menerbitkan ID sesi baru yang acak segera setelah kredensial terverifikasi.

#### 11. Di mana middleware didaftarkan pada Laravel 12? Kenapa beda dari tutorial?
- Pada Laravel 12, middleware didaftarkan di bootstrap/app.php.
- Berbeda dengan banyak tutorial lama yang menggunakan app/Http/Kernel.php.
- Hal ini karena Laravel 11 dan 12 menggunakan struktur aplikasi baru, sehingga konfigurasi middleware dipindahkan ke bootstrap/app.php.

#### 12. Tunjukkan bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
- buka file database\migrations\2026_09_12_000007_create_grades_table.php
- cari baris ke 16 & 17 yang ada onDelete-nya
- jelaskan bahwa ini berasal dari AI
- tapi, AI hanya memberikan sampai $table->foreignId('submission_id') dan $table->foreignId('graded_by')
- maka sesuai kebutuhan, kami menambahkan constrain (ada unique, contrained, dan perilaku onDelete yang sesuai)