## Catatan Minggu 4

Nama: Zalfa Putri Sopyandi  
NIM: 10241076

## READ 

### 1. Method apa yang menerima request? Di controller mana?
**Jawab:**  
Method `store()`, yang berada di dalam `CourseController` (`app/Http/Controllers/CourseController.php`).

### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
**Jawab:**  
Sebelum baris pertama method controller berjalan. Karena kita menggunakan Form Request (`StoreCourseRequest`), Laravel otomatis menjalankan validasi terlebih dahulu sebelum mengeksekusi kode di dalam method `store()`.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
**Jawab:**  
Laravel me-redirect kembali ke halaman form sebelumnya (halaman create). Tujuannya ditentukan secara otomatis oleh Laravel berdasarkan header `Referer` (halaman asal user menekan tombol submit).

### 4. Dari mana `@error('sks')` mengambil pesannya?
**Jawab:**  
Dari method `messages()` di dalam file Form Request (`app/Http/Requests/StoreCourseRequest.php`) yang sudah kita buat. Laravel menyimpan pesan ini di flash `session`.

### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
**Jawab:**  
Dari flash session (data session sementara). Nilainya hanya bertahan untuk satu request berikutnya (satu kali redirect), lalu otomatis dihapus oleh Laravel.

### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
**Jawab:**  
Nama default cookie session Laravel adalah `laravel_session`.

## BREAK  

| # | Yang dirusak | Yang harus Anda amati | Hasil pengamatan | 
|---|--------------|------------------------|-----|
| 1 | Hapus `@csrf` dari form, lalu kirim | Error 419 — dan renungkan apa yang dicegahnya | Saat form disubmit, muncul Error 419 (Page Expired). Ini membuktikan `@csrf` wajib ada untuk menolak request dari situs luar (CSRF).
| 2 | Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl` | Mass assignment kembali terbuka | Data liar tersebut berhasil masuk ke database. Ini membuktikan bahaya Mass Assignment. 
| 3 | Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999` | Data yatim masuk database | Mata kuliah berhasil disimpan padahal dosennya tidak ada di database. Ini disebut Data Yatim (Orphan Data).
| 4 | Hapus validasi `in:...` pada `status`, kirim `status=superadmin` | Enum jebol | Data berhasil disimpan dengan status yang tidak valid. Aturan enum di database/jalur aplikasi jebol.
| 5 | Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2 | Filter hilang — bug klasik | Filter pencarian hilang dan halaman 2 menampilkan semua data. Filter tidak bertahan di URL.
| 6 | Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan | Data ganda; ini alasan PRG ada | Browser memunculkan peringatan. Jika diklik Yes, data akan tersimpan 2 kali (duplikat). Ini membuktikan pentingnya pola PRG.
| 7 | Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan | Rasakan sendiri sebagai pengguna | Saat kembali ke form karena error, seluruh isian yang sudah diketik hilang. Pengguna dipaksa mengetik ulang dari awal (pengalaman pengguna yang buruk).

## FIX  
Branch `w04` pada repo `kampuslms-broken` berisi modul mata kuliah dengan 6 masalah: validasi hanya di frontend, `unique` pada update yang menolak dirinya sendiri, filter disimpan di session, pagination kehilangan query string, `store` tanpa redirect, dan satu form tanpa `@csrf`.

## BUILD  
1. `StoreCourseRequest` dan `UpdateCourseRequest` lengkap dengan pesan bahasa Indonesia.  
2. Form tambah/edit mata kuliah dengan `old()`, `@error`, dan `@csrf`.  
3. Flash message sukses/gagal di layout, sehingga berlaku untuk seluruh halaman.  
4. Daftar mata kuliah dengan pencarian (kode/nama), filter status, dan pagination 15 per halaman — filter bertahan saat berpindah halaman.  
5. Hal yang sama diterapkan pada modul Pengguna.  
6. Konfirmasi sebelum menghapus (modal atau halaman konfirmasi), dan penghapusan memakai method `DELETE`.

**Dari BUILD ini, kami sudah mengerjakan dan sudah bisa menghasilkan daftar daftar matakuliah dengan pencarian dan juga filter status sesuai ketentuannya.**