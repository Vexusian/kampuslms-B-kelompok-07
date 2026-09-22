# Week 4 – Ringkasan Validasi & PRG

**1. Method yang menerima request & controller**
- `store` dan `update` pada **`App\Http\Controllers\CourseController`** menerima request HTTP (POST/PUT).

**2. Titik terjadinya validasi**
- Validasi terjadi **sebelum** baris pertama logika controller, yaitu di dalam **Form Request** (`StoreCourseRequest` / `UpdateCourseRequest`). Laravel mem‑call `->validate()` otomatis ketika Form Request di‑inject.

**3. Redirect setelah gagal**
- Jika validasi gagal, Laravel **meng‑redirect kembali ke URL form sebelumnya** (biasanya `create` atau `edit`). Tujuannya ditentukan oleh **`Redirector::back()`** yang mengambil referer dari request.

**4. Asal `@error('sks')`**
- Blade directive `@error('sks')` mengambil **pesan error** dari **`$errors`** bag‑session yang otomatis di‑flash oleh validator.

**5. Asal `old('sks')`**
- `old('sks')` membaca **value lama** yang disimpan di **flash session** (`_old_input`). Nilainya bertahan **satu request cycle** (hanya sampai redirect berikutnya).

**6. Cookie session Laravel**
- Buka DevTools → Application → Cookies → `localhost` (atau domain app). Nama cookie session standar: **`laravel_session`**.

---

### Keamanan & Praktik

**7. Kenapa validasi JavaScript tidak dianggap keamanan?**
- JS dapat dimodifikasi di sisi klien. Penyerang dapat meng‑bypass dengan men‑disable script atau meng‑ubah request lewat tools (cURL, Postman). *Contoh bypass:*
```bash
curl -X POST http://localhost/courses \
  -d "code=TEST123&name=Malicious&description=&sks=10&status=active&lecturer_id=1"
```
  (nilai `sks=10` melewati batas 1‑6 jika hanya client‑side yang dicek).

**8. Apa yang dikembalikan `$request->validated()`?**
- Mengembalikan **array hanya berisi field yang lolos aturan**. Lebih aman daripada `$request->all()` yang menyertakan semua input termasuk `_token`, `_method`, atau field yang tidak di‑rule.

**9. Pola PRG (Post‑Redirect‑Get)**
- Setelah **store** atau **update** berhasil, controller **redirect** ke halaman lain (biasanya index atau show). Jika `store` mengembalikan view langsung, maka **refresh** halaman akan mengirim ulang POST → duplikasi data.

**10. Filter di query string vs. session**
Query string digunakan untuk memungkinkan pengguna dapat membagikan url dengan menyimpan filter pencariannya pada url tersebut. url yang dibagikan akan memuat filter pencarian, sehingga ketika dibuka di tab lainnya filter pencarian masih berlaku namun ketika tab baru dibuka tanpa url tampilannya tidak akan menerapkan filter pencarian pada tab sebelumnya. 

- kalau session dipakai di pencarian, filter pencarian tidak disimpan di url, 
namun jika kita gunakan session untuk filter pencarian, url tidak menyimpan filter pencarian namun filter disimpan di session sehingga ketika session masih aktif dan user membuka tab filter yang lama akan mempengaruhi tampilan pada tab baru. 


**11. Fungsi `@csrf`**
- csrf disini berfungsi sebagai pencegah penyerang yang memanfaatkan pengguna yang sedang login, penyerang ini memanfaatkan session untuk menyisipkan form berbahaya untuk user mengirimkan request berbahaya kedalam aplikasi. jika kita tambahkan csrf maka request akan diubah menjadi token terlebih dahulu dan di validasi melalui 

**12. Kenapa `unique` pada update perlu `ignore()`?**
Unique update perlu ditambahkan karena ketika pengguna memasukan data update maka tanpa ignore data id yang harus unique akan dianggap duplikat sehingga update dicegah karna id nya dianggap duplikat. sedangkan jika dengan ignore() maka form akan memastikan nilai tetap unik tapi akan mengabaikan pada baris yang sedang diperbarui. 
