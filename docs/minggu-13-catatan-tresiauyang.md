# Catatan Praktikum Modul Minggu 13 — Kolaborasi, Version Control, dan Peer Review

- **Nama:** Tresia Uyang
- **NIM:** 10241072
- **Kelas / Kelompok:** Pemrograman Web (Kelas B - Kelompok 07)
- **Repository Target Review:** [https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git](https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git)

---

## 1. Pemilihan Pull Request (PR)

- **PR Terpilih:** [PR #104 — feat: menambahkan halaman rekap mahasiswa](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/104)
- **Penulis PR:** `@adlrnta`
- **Status PR:** Merged pada 27 Oktober 2026
- **Berkas yang Diubah:**
  - `app/Http/Controllers/MahasiswaNilaiController.php` (+209 baris)
  - `resources/views/mahasiswa/nilai.blade.php` (+278 baris)
  - `resources/css/dashboard/nilai.css` (+345 baris)
  - `resources/views/components/layout.blade.php` (+2 baris)
  - `routes/web.php` (+6 baris)

### Konteks Fitur & Alasan Pemilihan:
1. **Fitur Inti yang Relevan:** PR ini menambahkan fungsionalitas halaman rekap nilai dan penugasan untuk mahasiswa (`MahasiswaNilaiController`), menampilkan statistik mata kuliah yang diikuti, nilai per tugas, status pengumpulan, serta feedback dari dosen.

2. **Ukuran Terukur:** Logika backend-nya terpusat pada satu berkas controller baru (`MahasiswaNilaiController.php`, 209 baris), sehingga seluruh alur query basis data, relasi antartabel, dan kalkulasi nilainya terisolasi serta mudah ditelaah secara menyeluruh tanpa tercampur logika modul lain.

3. **Memenuhi 3 Kategori Wajib Modul 13:** Memiliki temuan konkret pada aspek **Keamanan** (kebocoran nilai yang belum dipublikasikan `is_published`), **Performa** (query koleksi tanpa limit/paginasi dan in-memory data processing), serta **Struktur/Keterbacaan** (controller gemuk / *fat controller* dengan tumpukan logika kalkulasi dan konversi nilai).

---

## 2. Rincian 3 Temuan Review

---

### Temuan 1: Kategori Keamanan (Security)

- **Penjelasan Masalah:**  
  Pada query penarikan data rekap tugas mahasiswa, controller menghubungkan tabel `assignments`, `submissions`, dan `grades` menggunakan `leftJoin`. Namun, query tersebut **tidak menyaring kolom `is_published` pada tabel `grades`**. Dalam skema LMS, kolom `is_published` menandakan apakah nilai dan feedback sudah resmi diumumkan oleh dosen atau masih berupa draf penilaian.
- **Lokasi Kode:** `app/Http/Controllers/MahasiswaNilaiController.php:99-107`
- **Kode Saat Ini:**
  ```php
  $tugas = DB::table('assignments as a')
      ->leftJoin('submissions as s', function ($join) use ($user) {
          $join->on('s.assignment_id', '=', 'a.id')
               ->where('s.user_id', '=', $user->id);
      })
      ->leftJoin('grades as g', 'g.submission_id', '=', 's.id')
      ->select([
          'a.id as assignment_id',
          'a.title',
          'g.score',
          'g.feedback',
          ...
      ])
      ->get();
  ```
- **Risiko Nyata:**  
  Terjadi **kebocoran data prematur (*information disclosure / unauthorized access*)**. Mahasiswa dapat melihat skor sementara dan catatan koreksi dosen sebelum dosen selesai mengevaluasi seluruh kelas dan menekan tombol publikasi. Hal ini dapat menimbulkan kerancuan informasi dan komplain mahasiswa apabila nilai sementara tersebut masih direvisi oleh dosen.
- **Saran Perbaikan:**  
  Tambahkan syarat publikasi (`g.is_published = true`) pada klausa `leftJoin` atau seleksi data, sehingga hanya nilai yang statusnya telah dipublikasikan secara resmi yang dapat dibaca oleh mahasiswa:
  ```php
  ->leftJoin('grades as g', function ($join) {
      $join->on('g.submission_id', '=', 's.id')
           ->where('g.is_published', true);
  })
  ```

---

### Temuan 2: Kategori Performa (Performance)

- **Penjelasan Masalah:**  
  Pada method `index()`, pengambilan data mata kuliah yang diikuti beserta daftar seluruh tugas menggunakan query tanpa pembatasan (`limit`) atau paginasi (`paginate`). Seluruh rekaman tugas ditarik sekaligus dengan `->get()`, lalu proses pemfilteran, pengelompokan (*grouping*), dan perhitungan ringkasan dilakukan di memori PHP (*in-memory collection*).
- **Lokasi Kode:** `app/Http/Controllers/MahasiswaNilaiController.php:56-73` dan `app/Http/Controllers/MahasiswaNilaiController.php:95-107`
- **Kode Saat Ini:**
  ```php
  // Mengambil seluruh mata kuliah sekaligus:
  $mataKuliah = $user->enrolledCourses()
      ->with(['lecturer', 'assignments'])
      ->get();

  // Mengambil seluruh riwayat tugas mahasiswa tanpa paginasi:
  $tugasMahasiswa = DB::table('assignments as a')
      ...
      ->where('cu.user_id', $user->id)
      ->get(); // Tanpa paginate(10) / limit()
  ```
- **Risiko Nyata:**  
  Seiring berjalannya semester dan bertambahnya riwayat tugas serta mata kuliah, memuat seluruh data ke dalam satu koleksi Eloquent akan menimbulkan lonjakan penggunaan RAM server (*memory spike*) dan memperlambat *response time* database. Tanpa paginasi, browser klien di perangkat ponsel (*mobile*) juga akan merender elemen tabel yang sangat panjang dan memicu lag (*DOM bloating*).
- **Saran Perbaikan:**  
  1. Terapkan paginasi pada daftar rincian tugas mahasiswa (misalnya `->paginate(10)`).
  2. Pindahkan kalkulasi ringkasan agregat (seperti jumlah tugas, tugas dikumpulkan, rata-rata nilai) langsung ke level query database SQL (menggunakan `withCount()` atau `avg()`), bukan memproses array besar di memori PHP.

---

### Temuan 3: Kategori Struktur & Keterbacaan (Clean Code & Architecture)

- **Penjelasan Masalah:**  
  `MahasiswaNilaiController` mengalami gejala *Fat Controller* (lebih dari 200 baris kode). Controller tidak hanya mengorkestrasikan alur HTTP, tetapi juga memuat aturan bisnis perhitungan akademik: rumus pembobotan nilai, konversi angka ke huruf mutu (A, B, C, D, E), pembobotan skala 4.0, perhitungan persentase kelulusan, hingga pemformatan angka desimal.
- **Lokasi Kode:** `app/Http/Controllers/MahasiswaNilaiController.php:110-209`
- **Kode Saat Ini:**
  ```php
  // Logika kalkulasi akademik menumpuk di Controller:
  foreach ($tugasMahasiswa as $tugas) {
      if ($tugas->score !== null) {
          $totalNilai += $tugas->score;
          $sudahDinilai++;
          
          // Konversi huruf mutu langsung di controller:
          if ($tugas->score >= 85) {
              $gradeHuruf = 'A'; $bobot = 4.0;
          } elseif ($tugas->score >= 70) {
              $gradeHuruf = 'B'; $bobot = 3.0;
          } ...
      }
  }
  $rataRata = $sudahDinilai > 0 ? round($totalNilai / $sudahDinilai, 2) : 0;
  ```
- **Risiko Nyata:**  
  Melanggar prinsip *Single Responsibility Principle* (SRP) dan *Separation of Concerns*. Controller menjadi gemuk, sulit dibaca, dan logika konversi nilai tidak dapat diuji secara terisolasi (*unit test*). Jika fitur rekap nilai di masa depan dibutuhkan pada modul lain (misalnya Cetak Transkrip PDF atau endpoint API Mobile), tim pengembang terpaksa melakukan duplikasi kode (*copy-paste*).
- **Saran Perbaikan:**  
  Ekstraksi logika perhitungan dan konversi nilai ke dalam Service Class khusus (misalnya `App\Services\GradeCalculationService`) atau DTO/Model Helper (misalnya `StudentGradeSummary`). Dengan demikian, controller tetap bersih dan ringkas (*skinny controller*), serta logika perhitungan nilai mudah di-unit test:
  ```php
  // Di Controller cukup memanggil service:
  $summary = $gradeService->calculateStudentSummary($user, $mataKuliahId);
  return view('mahasiswa.nilai', compact('summary'));
  ```

---

## 3. Teks Siap Salin untuk Review di GitHub

Berikut adalah teks komentar yang sudah diformat rapi dan siap Anda salin (*copy-paste*) ke GitHub PR #104. Komentar menggunakan nada yang profesional, objektif, dan menyerang kode (bukan orang):

---

### Komentar 1 (Kategori Keamanan):
* **Berkas:** `app/Http/Controllers/MahasiswaNilaiController.php`
* **Baris:** Klik tanda tambah biru (**`+`**) pada baris **99** (atau 100)
* **Teks Komentar:**
```markdown
**[Temuan Keamanan] Potensi kebocoran nilai yang belum dipublikasikan (`is_published`)**

- **Risiko:** Query penarikan rekap nilai menggunakan `leftJoin('grades as g', 'g.submission_id', '=', 's.id')` tanpa menyaring kolom `is_published`. Pada alur akademik LMS, dosen dapat menyimpan draf nilai sementara sebelum resmi diumumkan. Tanpa pengecekan `is_published = true`, mahasiswa dapat melihat skor dan feedback prematur yang masih dalam tahap evaluasi dosen (*information disclosure*).
- **Saran:** Tambahkan kondisi publikasi pada callback `leftJoin` agar hanya data grade yang telah dipublikasikan secara resmi yang ditarik ke tampilan:
```php
->leftJoin('grades as g', function ($join) {
    $join->on('g.submission_id', '=', 's.id')
         ->where('g.is_published', true);
})
```
```

---

### Komentar 2 (Kategori Performa):
* **Berkas:** `app/Http/Controllers/MahasiswaNilaiController.php`
* **Baris:** Klik tanda tambah biru (**`+`**) pada baris **56** (atau 95)
* **Teks Komentar:**
```markdown
**[Temuan Performa] Query data rekap tanpa paginasi (*unpaginated query*) dan in-memory calculation**

- **Risiko:** Pengambilan data mata kuliah dan daftar tugas menggunakan `->get()` tanpa batas (*limit*) atau paginasi, lalu proses kalkulasi ringkasan dikerjakan dengan me-loop koleksi di memori PHP. Jika riwayat penugasan bertambah banyak sepanjang semester, memuat seluruh rekaman sekaligus akan memicu lonjakan memori RAM server (*memory spike*) dan membuat response time melambat. Di sisi browser klien, merender tabel data yang terlalu panjang tanpa paginasi juga memperberat kinerja render DOM.
- **Saran:** Terapkan paginasi pada daftar rincian penugasan mahasiswa (misalnya `->paginate(10)`), dan lakukan agregasi ringkasan statistik (rata-rata nilai, jumlah tugas terkumpul) langsung pada query database SQL (menggunakan `withCount()` atau `avg()`) alih-alih memproses array besar di memori PHP.
```

---

### Komentar 3 (Kategori Struktur & Keterbacaan):
* **Berkas:** `app/Http/Controllers/MahasiswaNilaiController.php`
* **Baris:** Klik tanda tambah biru (**`+`**) pada baris **110**
* **Teks Komentar:**
```markdown
**[Temuan Struktur & Keterbacaan] Logika kalkulasi akademik menumpuk di Controller (*Fat Controller*)**

- **Risiko:** `MahasiswaNilaiController` memuat lebih dari 200 baris kode yang mencampuradukkan penanganan HTTP request dengan logika kalkulasi akademik (perhitungan rata-rata, konversi angka ke huruf mutu A-E, pembobotan SKS, dan formatting desimal). Hal ini melanggar *Single Responsibility Principle* (SRP), membuat controller sulit dipelihara, dan logika perhitungan nilai tidak dapat diuji secara terisolasi melalui *unit test*.
- **Saran:** Pindahkan logika bisnis kalkulasi dan konversi nilai ke Service Class khusus (misalnya `App\Services\GradeCalculationService`) atau DTO/Model Helper (seperti `StudentGradeSummary`). Dengan demikian, controller tetap ramping (*skinny controller*) dan logika perhitungan akademik dapat diuji serta digunakan kembali (*reusable*) jika ada kebutuhan ekspor PDF atau API.
```

---

## 4. Langkah Menaruh Review di Halaman GitHub

Berikut panduan langkah demi langkah saat mengeksekusi review di GitHub:

1. Buka tautan PR: [https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/104](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/104).
2. Klik tab **Files changed** (terletak di bagian atas di samping tab *Conversation* dan *Commits*).
3. Cari berkas `app/Http/Controllers/MahasiswaNilaiController.php`:
   - Gulir ke **baris 99**, arahkan kursor ke baris kode sampai muncul ikon tanda tambah biru (**`+`**).
   - Klik ikon **`+`**, tempelkan teks **Komentar 1 (Keamanan)**.
   - Klik tombol hijau bertuliskan **Start a review** (atau *Add single comment* jika tombol review tidak muncul).
   - Gulir ke **baris 56** (atau **baris 95**), klik tanda tambah biru (**`+`**).
   - Tempelkan teks **Komentar 2 (Performa)**, lalu klik **Add review comment**.
   - Gulir ke **baris 110**, klik tanda tambah biru (**`+`**).
   - Tempelkan teks **Komentar 3 (Struktur & Keterbacaan)**, lalu klik **Add review comment**.
4. Di pojok kanan atas tampilan (di atas diff berkas), klik tombol hijau bertuliskan **Finish your review** / **Submit review**.
5. Pilih opsi **Comment** (karena PR sudah merged), lalu klik tombol hijau **Submit review**.
6. *(Catatan Cadangan)*: Jika pada PR yang sudah merged GitHub tidak menampilkan tombol `+` pada diff, Anda cukup membuka tab **Conversation**, gulir ke kotak komentar paling bawah, tempelkan ketiga komentar di atas secara berurutan, lalu klik **Comment**.

---

## 5. Jawaban Checkpoint Minggu 13 — Gerbang Wawancara

Berikut adalah jawaban lengkap untuk kuis dan wawancara evaluasi Minggu 13 yang siap Anda gunakan:

### 1. Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.

- **Jumlah commit atas nama saya:** **22 commit** pada repositori proyek KampusLMS Kelompok 07:
  - 17 commit tercatat dengan identitas GitHub `10241072-jpg`
  - 5 commit tercatat dengan identitas Git `Tresia`
- **Data kontribusi tim (`git shortlog -sn --all`):**
  ```text
  153  Vanessa (Vexusian / Vanessa Marie Tandiarru)
   58  Wahyu (forbidenperson / wahyyrmdann)
   45  Zalfa (ZalfaPutriSopyandi / Zalfa Putri)
   22  Tresia (10241072-jpg / Tresia)
    1  Achmad Zaki Zaidan (Dosen Pengampu)
  ```

---

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?

Perintah `git rm --cached .env` hanya menghapus berkas `.env` dari commit saat ini (*staging area/HEAD*), namun berkas tersebut beserta seluruh isinya **masih tersimpan permanen di riwayat commit sebelumnya (*git history*)**. Siapa pun yang meng-clone repositori atau melihat log commit lama tetap bisa mengekstrak rahasia tersebut. Jika `.env` sudah sempat ter-push ke GitHub publik, seluruh kredensial di dalamnya (`APP_KEY`, password database, API key) harus dianggap telah bocor dan wajib segera di-rotate (diganti) di server produksi.

---

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan.

**Temuan Keamanan pada PR #104 Kelompok 01 (`MahasiswaNilaiController.php:99`):**
* **Temuan:** Query rekap nilai mahasiswa mengambil data skor dan feedback dari tabel `grades` tanpa memfilter status publikasi (`is_published = true`).
* **Penjelasan Risiko (Gaya Wawancara Spontan):**  
  *"Pada query halaman rekap nilai mahasiswa, sistem melakukan left join ke tabel `grades` tanpa memeriksa kolom `is_published`. Padahal di sistem LMS, nilai yang baru diinput dosen biasanya berstatus draf sebelum resmi diumumkan ke kelas. Risikonya terjadi kebocoran informasi (*information disclosure*), di mana mahasiswa bisa melihat nilai sementara dan catatan koreksi dosen sebelum dosen selesai menilai semua mahasiswa dan memublikasikannya secara resmi. Hal ini rawan memicu komplain mahasiswa jika nilai tersebut ternyata masih direvisi."*
* **Saran Perbaikan:** Tambahkan kondisi `where('g.is_published', true)` pada closure join tabel grades.

---

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?

- **Temuan yang masuk:** Masukan perbaikan untuk menyembunyikan panel kredensial akun demo pada halaman login saat aplikasi berjalan di lingkungan produksi (`production`).
- **Keputusan:** Diterima.
- **Alasan:** Masukan tersebut sangat tepat dari sudut pandang keamanan operasional produksi. Meskipun panel akun demo memudahkan pengujian, menampilkan kredensial secara terbuka di domain publik dapat disalahgunakan pihak luar. Kelompok kami menindaklanjutinya dengan membatasi agar kartu demo hanya dirender di lingkungan lokal (`app()->environment('local')`).

---

### 5. Kenapa PR sebaiknya kecil dan satu fitur?

1. **Mudah dan Cepat Ditelaah (*Reviewable*):** Peninjau kode dapat memahami alur perubahan secara teliti tanpa kelelahan kognitif membaca ratusan baris kode sekaligus.
2. **Meminimalkan Risiko Konflik (*Merge Conflict*):** PR kecil berumur pendek dan lebih cepat digabungkan, sehingga branch fitur tidak tertinggal jauh dari branch utama (`main`/`dev`).
3. **Mudah Dilacak dan Dibatalkan (*Revertable*):** Jika ditemukan bug setelah digabungkan ke server, sumber masalah sangat mudah diidentifikasi dan branch dapat di-*revert* dengan aman tanpa mengganggu fitur lain.

---

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?

1. **Identitas Kepenulisan (*Author*) Git Bisa Dipalsukan:** Nama dan email pembuat commit dapat dimanipulasi dengan mudah menggunakan flag `git commit --author`.
2. **Bobot Pekerjaan Berbeda-beda:** Satu commit yang merancang arsitektur database, otorisasi IDOR, atau algoritma rumit bernilai jauh lebih besar dibandingkan puluhan commit kecil yang hanya memperbaiki typo, spasi, atau teks tampilan.
3. **Kontribusi Non-Kode Tidak Tercatat di Git Log:** Kontribusi penting tim seperti review kode rekan di GitHub PR, pengujian antarmuka di perangkat mobile, perancangan UI/UX, dan diskusi debugging bersama (*pair programming*) tidak selalu menghasilkan commit git langsung.
