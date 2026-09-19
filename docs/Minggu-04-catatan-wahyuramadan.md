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
- Menyimpan filter di **query string** membuat URL dapat dibagikan, bookmark, dan mudah dipertahankan saat berpindah halaman.
- Jika dipindah ke **session**, filter tidak terlihat di URL; membuka tab baru atau men‑share link akan **kehilangan filter**. *Skenario rusak:*
  - User A mengatur filter `status=active` di session.
  - User B membuka tab baru (session sama) → akan otomatis melihat data **aktif** meski tidak mengatur filter, mengacaukan UI.

**11. Fungsi `@csrf`**
- Menyisipkan **token CSRF** ke dalam form. Laravel memverifikasi token pada setiap request non‑GET. Mencegah **Cross‑Site Request Forgery**, di mana penyerang memaksa user yang sudah login mengirim request berbahaya ke aplikasi.

**12. Kenapa `unique` pada update perlu `ignore()`?**
- Pada update, nilai kolom yang **sudah ada** (mis. kode mata kuliah) akan dianggap duplikat oleh aturan `unique`. `Rule::unique('courses','code')->ignore($course->id)` mengecualikan record yang sedang di‑update sehingga validasi tidak gagal bila kode tidak berubah.
