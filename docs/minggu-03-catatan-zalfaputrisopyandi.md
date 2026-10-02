## Catatan Minggu 3

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### 1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
**Jawab:**  
<img src = "images/image_3-ERD.jpeg">

### 2. Untuk setiap foreign key, tentukan perilaku onDelete-nya dan tuliskan alasannya.
**Jawab:**  
| No | Entitas (Induk) | Entitas (Anak) | Perilaku OnDelete | Alasan |
|----|-----------------|----------------|------------------|--------|
| 1 | User  | Notifications | cascade On Delete | Jika user dihapus maka user tidak perlu lagi mendapatkan notifikasi |
| 2 | User  | Courses  | restrict On Delete | Selama dosen memiliki mata kuliah diampu maka dosen tidak boleh dihapus |
| 3 | User  | Grades | restrict On Delete | Akun user (dosen) harus tetap ada agar siapa yang menilai bisa tetap terlihat, selagi atribut graded by masih ada karena atribut tersebut mereferensi ke user |
| 4 | Users  | Course User | cascade On Delete| Jika user mahasiswa dihapus maka, Course User tidak masalah terhapus |
| 5 | Users  | Material | cascade On Delete | Jika user dosen dihapus maka materi ikut terhapus |
| 6 | Users  | Submissions | cascade On Delete | Jika user mahasiswa dihapus maka, submissions ikut terhapus |
| 7 | Users  | Assignment | cascade On Delete | Jika user mahasiswa dihapus maka, assignment ikut terhapus |
| 8 | Courses | Assignment | cascade On Delete | Jika mata kuliah dihapus maka Tugas yang diberikan juga ikut terhapus |
| 9 | Courses  | Corse_user | cascade On Delete | Jika mata kuliah dihapus maka course_user akan ikut terhapus, karena mata kuliah itu tidak bisa lagi diambil user |
| 10 | Courses | Material | cascade On Delete | Jika mata kuliah dihapus maka material juga ikut dihapus karena sudah tidak dipakai |
| 11 | Assignment | Submission | cascade On Delete | Jika assignment dihapus maka submission akan ikut dihapus, karena dari pengumpulan tugas akan tidak berguna jika penugasannya tidak ada |
| 12 | Submission | Grades | cascade On Delete | Jika submission dihapus maka grades sudah tidak perlu lagi dan perlu dihapus|

### 3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
**Jawab:**  
Jika mengikuti rancangan yang telah dibuat, maka penghapusan entitas Dosen tidak diizinkan karena perilaku OnDelete-nya adalah Restrict On Delete. Namun jika dengan terpaksa dilakukan force delete, maka Mata Kuliah sebagai entitas anak akan menjadi data yang ‘yatim piatu’ (Orphaned Data) karena tidak memiliki induk.

### 4. Jawab: kenapa grades.submission_id bersifat unique, bukan sekadar index biasa?
**Jawab:**  
grades.submission_id harus dibuat unik karena penilaian untuk satu submission hanya boleh memiliki 1 nilai. jika tidak dibuat unik, maka memungkinkan untuk satu submission mendapatkan banyak nilai. 

## BREAK  

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Hapus `unique(['course_id','user_id'])` dari `course_user`, lalu daftarkan mahasiswa yang sama dua kali | Data ganda lolos tanpa keluhan |
| 2 | Tambahkan `role` ke `$fillable` model `User`, lalu kirim request pembuatan user dengan `role=admin` lewat form yang **tidak punya field role** | **Mass assignment nyata** — Anda baru saja jadi admin |
| 3 | Ganti seluruh `$fillable` dengan `protected $guarded = [];` lalu ulangi nomor 2 | Kenapa `$guarded` kosong dilarang |
| 4 | Kosongkan isi `down()` di satu migrasi, lalu jalankan `php artisan migrate:refresh` | Migrasi tidak reversible = CI merah |
| 5 | Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus satu dosen | Kehilangan data berantai |

## FIX  
Branch `w03` pada repo `kampuslms-broken` berisi migrasi dan model dengan 7 masalah: urutan migrasi salah, dua unique composite hilang, satu `onDelete` keliru, satu `$guarded = []`, satu `down()` kosong, dan satu controller yang memakai `$request->all()`.  
**Jawab:**  
**Masalah 1: Urutan migrasi salah**  
- 2026_01_01_000001_create_courses_table.php (salah)  
- 2026_01_01_000002_create_users_table.php (salah)  

File `create_courses_table` memiliki timestamp `000001` yang seharusnya dijalankan pertama, padahal di dalamnya ada: `$table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();`  
Ini mereferensikan tabel `users`. Jika courses dibuat duluan, database akan error: `table "users" does not exist`.  

Cara perbaikinya: Rename file agar timestamp berurutan dengan benar
- 2026_01_01_000001_create_users_table.php 
- 2026_01_01_000002_create_courses_table.php

Dampak:
Mencegah fatal error saat deployment. Tanpa ini, php artisan migrate akan gagal karena tabel child dibuat sebelum parent-nya ada.

**Masalah 2: Unique composite hilang**  
Pada file `2026_01_01_000003_create_course_user_table.php` belum ada kode uniqe compositenya.

Cara perbaikinya: Tambahkan baris `$table->unique(['course_id', 'user_id']);` setelah `$table->timestamps();` dan sebelum closing `});` dari `Schema::create`.

Dampak: Mencegah mahasiswa terdaftar duplikat di mata kuliah yang sama. Tanpa ini, data enrollment bisa ganda, merusak perhitungan progres dan nilai.

**Masalah 3: Unique composite hilang**  
Pada file `2026_01_01_000004_create_materials_table.php` belum ada kode uniqe compositenya.

Cara perbaikinya: Tambahkan baris `$table->unique(['course_id', 'sequence']);` setelah `$table->timestamps();` dan sebelum closing `});` dari `Schema::create`.

Dampak: Mencegah dua modul memiliki nomor urut yang sama dalam satu mata kuliah. Tanpa ini, alur belajar mahasiswa berantakan dan fitur "materi selanjutnya" error.

**Masalah 4: onDelete keliru**  
Pada file `2026_01_01_000002_create_courses_table.php` rusaknya kode bari `$table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();`. Jika dosen dihapus, semua mata kuliah yang diampunya ikut terhapus permanen beserta data mahasiswa di dalamnya.

Cara perbaikinya: Ubah `cascadeOnDelete()` menjadi `restrictOnDelete()`

Dampak: Mencegah data loss massal. Jika admin tidak sengaja menghapus dosen, sistem akan menolak (karena masih ada mata kuliah aktif), alih-alih menghapus semua course beserta data mahasiswa secara diam-diam.

**Masalah 5: $guarded = []**  
Pada file `app/Models/User.php` menggunakan protected `$guarded = [];` yang mematikan proteksi mass assignment. Attacker bisa inject field berbahaya seperti `status`, `lecturer_id`, dll.

Cara perbaikinya: Ganti dengan `$fillable` eksplisit:
``` php
protected $fillable = [
    'code',
    'name', 
    'description',
    'sks',
    'lecturer_id',
    'status'
];
```

Dampak: Menutup celah Mass Assignment Vulnerability. Mencegah attacker mengubah field sensitif (status, lecturer_id) melalui API request.

**Masalah 6: down() kosong**  
Pada file `2026_01_01_000004_create_materials_table.php` tidak ada di file yang diupload. Method `down()` kosong (`//`), sehingga saat rollback migration, tabel tidak terhapus dan tertinggal di database.

Cara perbaikinya: tambahkan baris kode ini sesuai nama tabel.
```php
public function down(): void
{
    Schema::dropIfExists('assignments');
}
```

Dampak: Memastikan database bersih saat rollback. Mencegah ghost table yang menyebabkan error di masa depan.

**Masalah 7: $request->all()**  
Pada file `app/Http/Controllers/UserController.php`. Rusaknya baris `Course::create($request->all());` yang dapat memproses semua data dari request termasuk field sampah, dan tetap berisiko jika model diubah di masa depan.

Cara perbaikinya: Gunakan FormRequest `Course::create($request->validated());`

Dampak: Defense in depth terhadap mass assignment. Memastikan hanya data tervalidasi yang masuk database, menghemat memori, dan mencegah over-posting attack.

## BUILD 
1. Seluruh migrasi sesuai Bagian 4 spesifikasi, termasuk semua constraint dan index. Reversible.
2. Seluruh model dengan relasi lengkap sesuai Bagian 4.3, `$fillable` yang ketat, `casts()` sebagai method.
3. Factory + seeder yang memenuhi Bagian 4.4, termasuk 3 akun demo.
4. CRUD Mata Kuliah berfungsi penuh (index, create, store, show, edit, update, destroy).
5. CRUD Pengguna berfungsi penuh, dengan `role` tidak di `$fillable` melainkan diisi eksplisit di controller.
6. CI GitHub Actions aktif dan hijau: `migrate:fresh --seed` sukses, `migrate:refresh` sukses, `.env` tidak ter-commit.
7. Setiap anggota punya commit atas namanya sendiri, dan setiap fitur masuk lewat PR yang direview anggota lain.  

**Dari BUILD ini, kami sudah mengerjakan dan sudah bisa menghasilkan CRUD Mata Kuliah dan CRUD Pengguna sesuai ketentuannya.**