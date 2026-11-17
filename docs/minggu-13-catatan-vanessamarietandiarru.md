# Catatan Praktikum Modul Minggu 13 — Kolaborasi, Version Control, dan Peer Review

- **Nama:** Vanessa Marie Tandiarru
- **NIM:** 10251118
- **Kelas / Kelompok:** Pemrograman Web (Kelas B - Kelompok 07)
- **Repository Target Review:** [https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git](https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git)

---

## 1. Pemilihan Pull Request (PR)

- **PR Terpilih:** [PR #79 — feat(submission): mahasiswa mengumpulkan tugas dan unduh berkas lewat policy](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/79)
- **Penulis PR:** `andikaputra9`
- **Berkas yang Diubah:**
  - `app/Http/Controllers/SubmissionController.php` (+67 baris)
  - `routes/web.php` (+7 baris, -1 baris)

### Konteks Fitur & Alasan Pemilihan:
1. **Konteks Fitur:** PR ini menambahkan alur utama agar mahasiswa bisa mengunggah berkas tugas (`store`) dan dosen/mahasiswa bisa mengunduh berkas tersebut (`download`).
2. **Ukuran Terukur:** Perubahan kodenya ringkas (hanya 74 baris pada 2 berkas), sehingga seluruh alurnya mudah dipahami dan ditelaah tanpa tercampur kode tampilan atau konfigurasi lain.
3. **Memenuhi 3 Kategori Wajib Modul 13:** Memiliki celah nyata pada aspek keamanan (pembatasan jenis file), ketahanan sistem (pengecekan file saat download & risiko klik ganda), serta kerapian struktur kode (pemisahan validasi ke Form Request).

---

## 2. Rincian 3 Temuan Review

---

### Temuan 1: Kategori Keamanan (Security)

- **Penjelasan Masalah:**
  Pada fungsi pengumpulan tugas, sistem baru mengecek batas ukuran file (maksimal 10 MB), tetapi **belum membatasi jenis/ekstensi filenya**. Akibatnya, mahasiswa bisa saja mengunggah file apa saja ke dalam sistem.
- **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php:22-26`
- **Kode Saat Ini:**
  ```php
  $request->validate([
      'file' => ['required', 'file', 'max:10240'],
      'note' => ['nullable', 'string', 'max:1000'],
  ]);
  ```
- **Risiko Nyata:**
  Pengguna jahil dapat mengunggah file berbahaya selain dokumen tugas, misalnya script `.php`, berkas program `.exe`, atau file `.html`/`.svg` yang disusupi script berbahaya. Jika berkas tersebut tersimpan di server, keamanan aplikasi bisa terancam.
- **Saran Perbaikan:**
  Tambahkan aturan `mimes` secara eksplisit pada validasi agar hanya menerima file dokumen tugas yang sah (PDF, DOC, DOCX):
  ```php
  $request->validate([
      'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
      'note' => ['nullable', 'string', 'max:1000'],
  ]);
  ```

---

### Temuan 2: Kategori Performa & Ketahanan Sistem (Performance & Reliability)

- **Penjelasan Masalah:**
  1. Pada fungsi `download()`, sistem langsung menyuruh server mengunduh file tanpa memastikan apakah filenya masih ada di folder penyimpanan server.
  2. Pada fungsi `store()`, pengecekan *"apakah mahasiswa sudah mengumpulkan tugas"* dilakukan lewat query `exists()` terpisah sebelum `create()`.
- **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php:33-41` dan `app/Http/Controllers/SubmissionController.php:57-64`
- **Kode Saat Ini:**
  ```php
  // Pada store():
  if ($assignment->submissions()->where('user_id', $user->id)->exists()) {
      return back()->withErrors(['file' => 'Anda sudah mengumpulkan tugas ini.']);
  }
  ...
  $assignment->submissions()->create([...]);

  // Pada download():
  return Storage::disk('local')->download(
      $submission->file_path,
      $submission->original_name
  );
  ```
- **Risiko Nyata:**
  1. Jika ada berkas tugas yang fisik filenya terhapus atau jalurnya rusak, fungsi download akan langsung menampilkan layar *error 500* (layar putih/crash).
  2. Jika mahasiswa mengalami internet lambat dan menekan tombol kumpul tugas dua kali dengan cepat (*double click*), kedua request bisa lolos bersamaan sebelum data tersimpan, sehingga memicu error duplikasi database.
- **Saran Perbaikan:**
  1. Tambahkan pengecekan keberadaan fisik berkas sebelum mengunduh. Jika tidak ada, kembalikan pesan 404 yang ramah pengguna:
     ```php
     if (!$submission->file_path || !Storage::disk('local')->exists($submission->file_path)) {
         abort(404, 'Berkas tugas tidak ditemukan di penyimpanan server.');
     }
     ```
  2. Gunakan `updateOrCreate` atau jalankan proses simpan di dalam `DB::transaction()` agar aman dari pengiriman ganda.

---

### Temuan 3: Kategori Struktur & Keterbacaan (Clean Code & Architecture)

- **Penjelasan Masalah:**
  Semua aturan dan pesan validasi form ditulis langsung (*inline*) di dalam fungsi Controller. Selain itu, alamat rute URL pengumpulan tugas belum mengikuti format standar RESTful Laravel.
- **Lokasi Kode:** `app/Http/Controllers/SubmissionController.php:21-27` dan `routes/web.php:167`
- **Kode Saat Ini:**
  ```php
  // Di controller:
  $request->validate([
      'file' => ['required', 'file', 'max:10240'],
      'note' => ['nullable', 'string', 'max:1000'],
  ], [
      'file.required' => 'Berkas tugas wajib diunggah.',
      ...
  ]);

  // Di routes/web.php:
  Route::post('/tugas/{assignment}/kumpul', [SubmissionController::class, 'store']);
  ```
- **Risiko Nyata:**
  Fungsi Controller menjadi panjang dan bercampur aduk antara logika alur aplikasi dengan aturan validasi input. Hal ini membuat kode lebih sulit dibaca dan dipelihara (*fat controller*).
- **Saran Perbaikan:**
  1. Pindahkan aturan validasi ke berkas terpisah (*Form Request Class*), misalnya `StoreSubmissionRequest`, sehingga Controller tetap ringkas (*skinny controller*).
  2. Gunakan pola penamaan rute bersarang (*nested resource*) yang seragam dengan rute tugas lainnya, misalnya `POST /assignments/{assignment}/submissions`.

---

## 3. Teks Siap Salin untuk Review di GitHub

Berikut kalimat yang bisa langsung kamu salin saat memberi komentar di GitHub:

### Komentar 1 (Beri pada baris 22 di `SubmissionController.php`):
> **[Temuan Keamanan] Batasan jenis berkas belum ada**
> - **Risiko:** Validasi saat ini hanya memeriksa ukuran maksimal 10 MB, tetapi belum membatasi ekstensi file (`mimes`). Pengguna berpotensi mengunggah berkas yang berbahaya (seperti script `.php`, `.sh`, atau `.exe`) ke server.
> - **Saran:** Tambahkan batasan ekstensi dokumen tugas secara eksplisit, misalnya `'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240']`.

### Komentar 2 (Beri pada baris 57 di `SubmissionController.php`):
> **[Temuan Ketahanan & Performa] Belum ada verifikasi keberadaan berkas fisik saat download**
> - **Risiko:** Method `download()` langsung memanggil `Storage::download()` tanpa memeriksa apakah file fisiknya benar-benar ada di storage. Jika file tidak sengaja terhapus, aplikasi akan melempar unhandled error 500. Selain itu, pengecekan `exists()` pada proses submit rawan race condition saat terjadi klik ganda.
> - **Saran:** Tambahkan pengecekan `if (!Storage::disk('local')->exists($submission->file_path)) { abort(404, 'Berkas tidak ditemukan.'); }` sebelum file diunduh.

### Komentar 3 (Beri pada baris 21 di `SubmissionController.php`):
> **[Temuan Struktur Kode] Validasi form masih ditulis langsung di dalam Controller**
> - **Risiko:** Menumpuk aturan validasi dan pesan kustom di controller membuat controller menjadi panjang (*fat controller*) dan kurang rapi menurut prinsip pemisahan tanggung jawab (*separation of concerns*).
> - **Saran:** Pindahkan logika validasi ini ke Form Request tersendiri (misalnya `StoreSubmissionRequest`) agar fungsi `store` di controller tetap bersih dan ringkas.

---

## 4. Langkah Menaruh Review di Halaman GitHub

1. Buka [PR #79 Kelompok 01](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/79).
2. Klik tab **Files changed**.
3. Di berkas `SubmissionController.php`:
   - Cari baris **22**, klik tombol biru **`+`**, tempel teks **Komentar 1**, lalu klik **Start a review**.
   - Cari baris **57**, klik tombol biru **`+`**, tempel teks **Komentar 2**, lalu klik **Add review comment**.
   - Cari baris **21**, klik tombol biru **`+`**, tempel teks **Komentar 3**, lalu klik **Add review comment**.
4. Klik tombol hijau **Review changes** / **Submit review** di bagian kanan atas layar.
5. Pilih **Comment** atau **Request changes**, lalu klik **Submit review**.

---

## 5. Jawaban Checkpoint Minggu 13

### 1. Berapa commit atas nama Anda? Tunjukkan git shortlog -sn.
- **Jumlah commit atas nama saya:** 146 commit (dari total 265 commit repositori, atau sekitar 55%).
  - 103 commit atas nama `Vexusian <vanessapakan@gmail.com>`
  - 43 commit atas nama `Vanessa Marie Tandiarru <vanessapakan@gmail.com>`
- **Data kontribusi tim (`git shortlog -sn`):**
  - Vanessa (`Vexusian` / `Vanessa Marie Tandiarru`): 146 commit
  - Wahyu (`forbidenperson` / `wahyyrmdann`): 58 commit
  - Zalfa (`ZalfaPutriSopyandi` / `Zalfa Putri`): 38 commit
  - Tresia (`10241072-jpg` / `Tresia`): 22 commit

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?
Perintah `git rm --cached .env` hanya menghapus berkas dari commit terbaru (staging area / HEAD), tetapi berkas `.env` beserta isinya masih tersimpan permanen di riwayat commit sebelumnya (*git history*). Siapa pun yang meng-clone repositori atau melihat log commit lama tetap bisa membaca rahasia tersebut. Jika berkas rahasia sudah ter-push ke remote publik, kredensial di dalamnya (`APP_KEY`, password database, API key) harus dianggap bocor dan wajib segera di-rotate (diganti).

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan.
Temuan keamanan pada berkas `app/Http/Controllers/SubmissionController.php:22-26` di PR #79 Kelompok 01.
- **Risiko:** Fungsi kumpul tugas hanya membatasi ukuran berkas (maksimal 10 MB), tetapi tidak membatasi ekstensi berkas (`mimes`). Pengguna berpotensi mengunggah berkas berbahaya (seperti skrip `.php`, `.sh`, berkas executable `.exe`, atau file `.html` berisi XSS) langsung ke penyimpanan server.
- **Saran:** Tambahkan batasan tipe file yang diizinkan secara eksplisit pada validasi, misalnya `mimes:pdf,doc,docx`.

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?
- **Temuan yang masuk:** Saran perbaikan untuk menyembunyikan panel akun demo pada halaman login saat berjalan di lingkungan produksi agar kredensial pengujian tidak tampil bebas untuk umum.
- **Keputusan:** Diterima.
- **Alasan:** Masukan tersebut valid untuk standar keamanan produksi. Kelompok kami menindaklanjutinya dengan membatasi tampilan akun demo hanya pada environment lokal (`app()->environment('local')`) melalui commit perbaikan.

### 5. Kenapa PR sebaiknya kecil dan satu fitur?
1. **Mudah ditelaah:** Reviewer dapat memahami perubahan kode secara menyeluruh tanpa kelelahan membaca ratusan baris sekaligus.
2. **Minim risiko konflik:** PR kecil lebih cepat diuji dan digabungkan (*merge*), sehingga branch tidak tertinggal jauh dari branch utama.
3. **Mudah dilacak dan dibatalkan:** Jika muncul bug baru setelah digabungkan, sumber masalah cepat ditemukan dan dapat dibatalkan (*revert*) tanpa merusak fitur lain.

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
1. **Kepenulisan (*author*) Git bisa dipalsukan:** Informasi nama dan email pembuat commit dapat dimanipulasi menggunakan flag `git commit --author`.
2. **Bobot pekerjaan berbeda-beda:** Satu commit yang menyelesaikan logika algoritma kompleks bernilai lebih besar dibanding puluhan commit kecil yang hanya memperbaiki spasi, typo, atau dokumentasi.
3. **Kontribusi non-kode tidak tercatat:** Kontribusi penting seperti diskusi arsitektur, pemecahan bug bersama (*pair programming*), pengujian manual, dan review PR rekan tim tidak selalu menghasilkan commit.
