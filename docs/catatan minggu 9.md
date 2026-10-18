# Catatan Minggu 9 — Strategi Penyimpanan Berkas LMS

## Konteks

LMS Laravel 12 memiliki tiga jenis berkas yang perlu disimpan:

1. **Foto profil pengguna**
2. **Materi kuliah** yang diunggah dosen (PDF/PPTX)
3. **Pengumpulan tugas** mahasiswa (PDF/DOCX)

Masing-masing memiliki kebutuhan keamanan dan pola akses yang berbeda, sehingga tidak boleh diperlakukan sama.

---

## Tabel Strategi Penyimpanan

| Aspek | Foto Profil Pengguna | Materi Kuliah Dosen (PDF/PPTX) | Pengumpulan Tugas Mahasiswa (PDF/DOCX) |
| :--- | :--- | :--- | :--- |
| **Disk** | `public` | `local` (private) | `local` (private) |
| **Alasan** | Bukan data rahasia. Ditampilkan di banyak tempat (navbar, forum, daftar kelas) dengan frekuensi tinggi. Perlu di-serve langsung oleh web server tanpa overhead PHP. | Materi bersifat eksklusif untuk kelas tertentu. Mahasiswa luar kelas, pihak eksternal, dan mesin pencari tidak boleh mengakses. Ada aspek hak cipta. | Sangat sensitif. Berisi karya individu/kelompok. Kebocoran memicu plagiarisme massal dan melanggar privasi penilaian. |
| **Struktur Folder** | `storage/app/public/avatars/{user_id}_{hash}.jpg` | `storage/app/private/courses/{course_id}/materials/{material_id}_{hash}.pdf` | `storage/app/private/assignments/{assignment_id}/submissions/{student_id}_{hash}.pdf` |
| **Cara Akses** | **URL statis langsung** — di-serve oleh web server via symlink `public/storage/avatars/...`. Tidak melewati PHP. | **Melalui Controller** — request melewati middleware `auth`, lalu Controller menjalankan Policy untuk memverifikasi keanggotaan kelas. Berkas di-stream via `Storage::response()` atau `Storage::download()`. | **Melalui Controller** — request melewati middleware `auth`, lalu Controller menjalankan Policy kepemilikan tugas. Berkas di-stream via `Storage::response()` atau `Storage::download()`. |
| **Yang Berhak Mengakses** | Semua pengguna (publik) | • Dosen pengampu kelas • Mahasiswa yang terdaftar (enrolled) di kelas tersebut • Admin LMS | • Mahasiswa pemilik berkas • Dosen penilai/pengampu tugas • Admin LMS — **Mahasiswa lain dilarang keras** |

---

## Mengapa `storage/app/public` Membocorkan Data Meskipun Ada Login & Policy?

Ini adalah pertanyaan kritis yang sering disalahpahami. Berikut penjelasannya:

### 1. Mekanisme `storage/app/public` dan Symlink

Ketika menjalankan `php artisan storage:link`, Laravel membuat symlink:

```
public/storage/  →  storage/app/public/
```

Web server (Nginx/Apache) dikonfigurasi untuk **melayani berkas statis terlebih dahulu** sebelum meneruskan request ke `index.php` (Laravel).

### 2. Web Server Mem-bypass Laravel Sepenuhnya

Ketika seseorang mengakses URL seperti:

```
https://lms.test/storage/assignments/12/submissions/tugas_budi.pdf
```

Yang terjadi:

1. Web server menerima request.
2. Web server memeriksa: *"Apakah ada berkas fisik di path ini?"*
3. Berkas **ditemukan** (karena symlink mengarah ke `storage/app/public/`).
4. Web server **langsung mengirim berkas ke browser**.
5. **Request tidak pernah sampai ke PHP/Laravel.**

### 3. Seluruh Lapisan Keamanan Tidak Dieksekusi

Karena request tidak pernah menyentuh Laravel, maka:

- ❌ Middleware `auth` → **tidak jalan**
- ❌ Pengecekan session/token → **tidak jalan**
- ❌ `CoursePolicy` / `AssignmentPolicy` → **tidak jalan**
- ❌ Logic otorisasi apapun → **tidak jalan**

**Kesimpulan:** Login, middleware, dan Policy yang sudah ditulis dengan benar pun **sama sekali tidak relevan** karena request-nya tidak pernah melewati kode PHP.

### 4. Risiko Konkret

| Risiko | Penjelasan |
| :--- | :--- |
| **Akses tanpa autentikasi** | Siapapun yang mengetahui/menebak URL dapat mengunduh berkas tanpa login. |
| **Enumerasi URL** | Pola URL yang dapat ditebak (misal ID berurutan: `submissions/1.pdf`, `submissions/2.pdf`, ...) memungkinkan *brute-force* untuk mengunduh semua berkas. |
| **Kebocoran via referer/cache** | URL berkas bisa bocor melalui HTTP Referer header, cache proxy kampus, atau riwayat browser bersama. |
| **Indexing mesin pencari** | Jika tautan sempat terekspos, web crawler (Google, Bing) dapat mengindeks dan menampilkan dokumen sensitif di hasil pencarian. |

### 5. Solusi: Simpan di `storage/app/private/`

Berkas yang disimpan di `storage/app/private/` (disk `local`):

- **Tidak** memiliki symlink ke folder `public/`.
- **Tidak bisa** diakses langsung via URL.
- **Hanya** bisa diakses melalui Controller yang menjalankan seluruh siklus Laravel (middleware → auth → policy → stream file).

```
storage/
├── app/
│   ├── public/          ← Symlink ke public/storage/ (JANGAN taruh data sensitif di sini)
│   │   └── avatars/     ← Foto profil (aman, memang publik)
│   │
│   └── private/         ← TIDAK ada symlink, tidak bisa diakses langsung
│       ├── courses/     ← Materi kuliah (dilindungi Controller + Policy)
│       └── assignments/ ← Tugas mahasiswa (dilindungi Controller + Policy)
```

---

## Ringkasan Prinsip

> **Aturan sederhana:** Jika berkas memerlukan pengecekan "siapa yang boleh mengakses", maka berkas tersebut **tidak boleh** disimpan di `storage/app/public`. Taruh di `storage/app/private` dan sajikan melalui Controller.

---

## Implementasi Pengumpulan Tugas (Laravel 12)

### 1. Arsitektur Penyimpanan & Keamanan
- **Disk:** `local` (`storage/app/private/submissions/{assignment_id}/{random_hash}.{ext}`).
- **Pemisahan Nama Berkas:**
  - `file_path`: Path berkas fisik acak yang di-generate otomatis oleh `store()`. Mencegah enumerasi dan tabrakan nama file.
  - `original_filename`: Nama asli berkas (misal `Laporan_Akhir_Budi.pdf`) untuk kenyamanan saat mahasiswa/dosen mengunduh.
- **Validasi Ketat:**
  `'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120']` (maks. 5 MB).
- **Perhitungan Keterlambatan (`is_late`):**
  Dihitung langsung di sisi server:
  `$isLate = $assignment->due_at ? now()->greaterThan($assignment->due_at) : false;`
- **Idempoten & Pengumpulan Ulang (`updateOrCreate`):**
  Menggunakan composite key `['assignment_id' => $assignment->id, 'user_id' => $user->id]`.
  Jika mahasiswa mengumpulkan ulang, berkas fisik lama dihapus dari disk privat sebelum berkas baru disimpan.

### 2. Endpoint Download & Otorisasi
- Laravel 12 tidak menyediakan helper `$this->authorize()` pada base controller. Otorisasi berkas dilakukan menggunakan facade `Gate::authorize()`:
  ```php
  Gate::authorize('download', $submission);

  if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
      abort(404, 'Berkas submission tidak ditemukan.');
  }

  return Storage::disk('local')->download(
      $submission->file_path,
      $submission->original_filename ?? basename($submission->file_path)
  );
  ```
- Kebijakan `SubmissionPolicy::download` hanya mengizinkan:
  1. Mahasiswa pemilik tugas (`$submission->user_id === $user->id`)
  2. Dosen pengampu mata kuliah terkait
  3. Administrator LMS

---

## Analisis: Kasus di Mana Event Model Eloquent TIDAK Terpicu

Penghapusan berkas fisik diatur menggunakan model hook `deleting` pada `Submission::booted()`:
```php
protected static function booted(): void
{
    static::deleting(function (Submission $submission) {
        if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
            Storage::disk('local')->delete($submission->file_path);
        }
    });
}
```

Namun, **event model Eloquent ini TIDAK AKAN terpicu** pada kondisi-kondisi berikut:

1. **Mass Delete / Query Builder Delete:**
   ```php
   // Event deleting/deleted TIDAK terpanggil!
   Submission::where('assignment_id', $assignmentId)->delete();
   ```
   *Penyebab:* Laravel mengeksekusi satu query SQL `DELETE FROM submissions WHERE ...` langsung ke database tanpa menghidupkan (*instantiate*) instance model Eloquent.
   *Solusi:* Gunakan `$assignment->submissions->each->delete()` atau manual chunked deletion.

2. **Foreign Key `cascadeOnDelete()` di Level Database:**
   ```php
   $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
   ```
   *Penyebab:* Jika parent record (misalnya `Assignment` atau `Course`) dihapus, engine database (MySQL / InnoDB / PostgreSQL) secara otomatis menghapus baris di tabel `submissions`. Engine database sama sekali tidak tahu-menahu tentang PHP atau lifecycle Eloquent, sehingga event `deleting` di PHP tidak pernah dieksekusi. Berkas fisik akan tertinggal menjadi **file yatim (*orphan file*)** di disk.
   *Solusi:*
   - Hindari `cascadeOnDelete()` di DB jika model memiliki berkas fisik terkait, atau
   - Pasang event deleting pada parent model (`Assignment::deleting`) untuk menghapus submission lewat Eloquent (`$assignment->submissions->each->delete()`), atau
   - Buat artisan command pembersih berkas orphan secara terjadwal (`cron`).

3. **Query Mentah (*Raw SQL / DB Facade*):**
   ```php
   DB::table('submissions')->where('id', $id)->delete();
   DB::statement('TRUNCATE TABLE submissions');
   ```
   *Penyebab:* Melewati layer Eloquent secara total.
