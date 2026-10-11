## Catatan Minggu 7

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### 1. Berkas mana yang menangani POST login? Method apa?  
Berkas: `app/Http/Controllers/AuthController.php`   
Method: `login(Request $request)` 

### 2. Di baris mana `Auth::attempt()` atau setaranya dipanggil?  
Dipanggil pada: Berkas `app/Http/Controllers/AuthController.php` di dalam method `login()` pada baris `Auth::attempt($credentials)`.   

### 3. Temukan `session()->regenerate()`. Kenapa ia ada di situ?   
Lokasi: Dipanggil di `AuthController.php` tepat setelah pemeriksaan autentikasi `Auth::attempt()` berhasil.  
Alasan: Untuk mencegah serangan **Session Fixation**. Dengan meregenerasi ID sesi saat pengguna berhasil masuk, ID sesi yang lama (sebelum login) dibatalkan sehingga peretas tidak bisa memanfaatkan sesi yang telah diinjeksi sebelumnya.   

### 4. Di mana kata sandi di-hash? Cari cast `hashed` di model `User`.   
Proses Hashing: Terjadi secara otomatis di Model `app/Models/User.php`.   
Cast Hashed: Terdefinisi pada method `casts()` di model User:   
```php
'password' => 'hashed',
```

### 5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session.  
Sebelum Login: Cookie sesi (seperti `laravel_session`) berisi ID string terenkripsi yang merepresentasikan sesi tamu (guest session).   
Sesudah Login: Nilai string pada cookie `laravel_session` berubah total. Perubahan nilai token/cookie ini terjadi akibat pemanggilan `session()->regenerate()` setelah autentikasi berhasil.   

### 6. Logout, lalu tekan tombol *back*. Apa yang terjadi? Kenapa?  
Apa yang terjadi: Halaman dashboard/internal aplikasi yang dibuka sebelum logout akan muncul kembali di layar (atau menampilkan versi ter-cache). Namun, jika pengguna mencoba melakukan aksi (klik link/kirim form/refresh), pengguna akan langsung dilempar (redirect) kembali ke halaman login.   
Kenapa:
- Sisi Browser (Cache): Tombol Back membaca versi halaman dari cache lokal browser (BFCache / Back-Forward Cache) tanpa melakukan HTTP Request baru ke server.   
- Sisi Server (Session Destroyed): Begitu aksi baru dilakukan atau halaman di-refresh, server mendeteksi sesi autentikasi pengguna sudah dihapus/dihancurkan melalui method `logout()`, sehingga middleware `auth` memblokir akses dan mengarahkan kembali pengguna ke halaman login.   

### BREAK

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Hapus `session()->regenerate()` dari login | Session fixation terbuka |
| 2 | Hapus `Gate::authorize()` dari `update`, tapi biarkan `@can` di Blade | **Tombol hilang, URL tetap jalan** |
| 3 | Sebagai dosen A, kirim PUT ke mata kuliah dosen B lewat `curl` | Uji Policy Anda sungguhan |
| 4 | Sebagai mahasiswa, buka submission mahasiswa lain (IDOR minggu 5) | Sekarang harus 403 |
| 5 | Ubah `index` menjadi `Course::paginate()` polos, login sebagai mahasiswa | Kebocoran tingkat daftar |
| 6 | Kirim `role=admin` pada form edit profil | Mass assignment kembali diuji |
| 7 | Ganti cast `hashed` menjadi tidak ada, buat user baru, lihat kolom password di database | **Kata sandi polos di database** |
| 8 | Login, salin cookie session, tempel di browser lain | Kenapa cookie harus HttpOnly & Secure |

### FIX

Branch `w07` pada repo `kampuslms-broken` berisi **9 masalah**: `@can` tanpa `Gate::authorize()` di dua controller, Policy yang selalu `return true`, kebocoran daftar untuk mahasiswa, `role` bisa diubah lewat update profil, `session()->regenerate()` hilang, pesan login membocorkan keberadaan email, satu route dosen tanpa middleware, dan Policy yang menyebabkan N+1.  
1. `@can` Tanpa `Gate::authorize()` di Controller  
    Lokasi Berkas: `app/Http/Controllers/CourseController.php`   `app/Http/Controllers/MaterialController.php`  
    Perbaikan: Tambahkan `Gate::authorize('update', $course)` atau `$this->authorize()` di dalam method controller sebelum memproses data.   

2. Policy yang Selalu `return true`  
    Lokasi Berkas: `app/Policies/CoursePolicy.php`   `app/Policies/MaterialPolicy.php`  
    Perbaikan: Hapus `return true`; dan ganti dengan logika pengecekan peran (`role === 'dosen'`) atau kepemilikan (`$user->id === $course->user_id`).   

3. Kebocoran Daftar untuk Mahasiswa  
    Lokasi Berkas: `app/Http/Controllers/CourseController.php` (method `index`)    
    Perbaikan: Filter kueri berdasarkan peran pengguna (misal: jika mahasiswa, filter kursus yang diikuti via relasi `$user->courses`).

4. `role` Bisa Diubah Lewat Update Profil  
    Lokasi Berkas: `app/Models/User.php` (properti `$fillable`)   `app/Http/Controllers/ProfileController.php`  
    Perbaikan: Hapus `'role'` dari properti `$fillable` pada `User.php` dan batasi input dari request update profil.

5. `session()->regenerate()` Hilang  
    Lokasi Berkas: `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (atau `app/Http/Controllers/Api/AuthController.php`)   
    Perbaikan: Tambahkan `$request->session()->regenerate()`; tepat setelah proses autentikasi berhasil.

6. Pesan Login Membocorkan Keberadaan Email  
    Lokasi Berkas: `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (atau `app/Http/Controllers/Api/AuthController.php`)   
    Perbaikan: Disamakan pesan respon gagalnya menggunakan `'email' => __('auth.failed')` tanpa membedakan pesan email salah atau kata sandi salah.   

7. Route Dosen Tanpa Middleware  
    Lokasi Berkas: `routes/web.php` (atau `routes/api.php`)   
    Perbaikan: Bungkus route khusus dosen ke dalam grup middleware `['auth', 'role:dosen']` atau tambahkan middleware langsung pada definisinya.   

8. Policy Menyebabkan N+1 Query  
    Lokasi Berkas: `app/Policies/CoursePolicy.php`   `app/Policies/SubmissionPolicy.php`   
    Perbaikan: Hindari pemanggilan relasi yang memicu kueri ulang di dalam method Policy; gunakan eager loading atau pemeriksaan langsung pada kunci asing (`user_id`).   

9. Otorisasi / Pendaftaran Policy Kebocoran Tambahan  
    Lokasi Berkas: `app/Providers/AppServiceProvider.php`   
    Perbaikan: Pastikan pemetaan policy atau aturan `Gate::before()` dikonfigurasi dengan tepat untuk menghindari jalan pintas (bypass) otorisasi secara tidak terencana.

### BUILD

Deliverable yang dinilai sebagai **Tugas 2**:

1. **Autentikasi lengkap**: login, logout, register (khusus admin yang mendaftarkan), reset kata sandi.
2. **Tiga peran berfungsi** dengan dashboard berbeda per peran.
3. **Policy untuk seluruh model** (Course, Material, Assignment, Submission, Grade), dipanggil di controller **dan** dipakai di Blade.
4. **Seluruh index disaring di level query** sesuai peran.
5. **Tabel Titik Rawan IDOR terisi penuh** di `docs/keamanan.md`: tiap baris menyebut Policy atau query yang menutupnya.
6. **CRUD Materi dan Tugas** berfungsi dengan otorisasi yang benar (unggah berkas baru minggu 9 — cukup metadata dulu).
7. **`scripts/test-authz.sh`** — skrip pengujian otorisasi yang seluruhnya lolos.
8. CI hijau, setiap anggota punya commit, tiap fitur lewat PR yang direview.  

**Dari BUILD ini, kami sudah mengerjakan dan sudah bisa menghasilkan sesuai ketentuan di BUILD.**