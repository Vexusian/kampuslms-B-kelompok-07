# Catatan Minggu 13

**Nama:** Zalfa Putri Sopyandi  
**NIM:** 10241076  
**Kelompok:** B07  
**Repository Target Review:** [https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git](https://github.com/AdeliaIsraE/kampuslms-kelompok-01.git)

---

## 1. Pemilihan Pull Request (PR)

- **PR Terpilih:** [PR #81 — Menambahkan fitur Admin bisa menambahkan mahasiswa ke matakuliah](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/81)
- **Penulis PR:** `@AdeliaIsraE`
- **Berkas yang Diubah:**
  - `app/Http/Controllers/CourseEnrollmentController.php` (berkas baru, +73 baris)
  - `app/Http/Controllers/Coursecontroller.php` (+26 baris, -3 baris)
  - `resources/css/components/courses.css` (+110 baris)
  - `resources/views/courses/show.blade.php` (+117 baris)
  - `routes/web.php` (+10 baris)

### Konteks Fitur & Alasan Pemilihan:
1. **Fitur Inti yang Kritis:** PR ini menambahkan fungsionalitas manajemen pendaftaran (*enrollment*) mahasiswa ke mata kuliah oleh Admin, termasuk pendaftaran banyak mahasiswa sekaligus (*batch store*) dan pembatalan pendaftaran (*destroy*).
2. **Berbeda dari Anggota Lain:** Anggota tim lain (Vanessa) meninjau PR #79 mengenai pengumpulan tugas oleh mahasiswa (`andikaputra9`), sehingga PR #81 ini unik dan tidak menumpuk pada PR yang sama.
3. **Memenuhi 3 Kategori Wajib Modul 13:** Memiliki temuan nyata pada aspek keamanan (ketiadaan batas maksimal payload array pada pendaftaran), performa (query tanpa paginasi yang memuat seluruh mahasiswa satu kampus ke memori), serta kerapian struktur kode (inline script JavaScript di tengah file template Blade).

---

## 2. Rincian 3 Temuan Review


### Temuan 1: Kategori Keamanan (Security)

- **Penjelasan Masalah:**
  Pada method `store()`, validasi input array mahasiswa (`student_ids`) hanya mensyaratkan `min:1`, namun **tidak memiliki batasan jumlah maksimal (`max`)**. Selain itu, pada method `destroy()`, penghapusan mahasiswa menggunakan parameter integer mentah `int $student` tanpa memverifikasi apakah ID tersebut valid sebagai mahasiswa yang terdaftar di mata kuliah terkait.
- **Lokasi Kode:** `app/Http/Controllers/CourseEnrollmentController.php:26-40` dan `app/Http/Controllers/CourseEnrollmentController.php:63-68`
- **Kode Saat Ini:**
  ```php
  // Pada store():
  $data = $request->validate([
      'student_ids'   => ['required', 'array', 'min:1'],
      'student_ids.*' => [
          'integer',
          'distinct',
          Rule::exists('users', 'id')->where('role', 'mahasiswa')->whereNull('deleted_at'),
      ],
  ]);
  
  // Pada destroy():
  public function destroy(Course $mata_kuliah, int $student)
  {
      Gate::authorize('manageEnrollment', $mata_kuliah);
      $removed = $mata_kuliah->students()->detach($student);
      ...
  }
  ```
- **Risiko Nyata:**
  1. Pengguna dapat mengirim ribuan ID mahasiswa sekaligus dalam satu request POST. Hal ini memaksa server mengeksekusi operasi `syncWithoutDetaching` yang sangat berat, berpotensi menyebabkan kehabisan memori server (*Memory Exhaustion*) atau serangan *Denial of Service* (DoS).
  2. Pada `destroy()`, pemanggilan `detach($student)` tanpa Route Model Binding atau validasi relasi berisiko memicu operasi database yang tidak terduga jika ID yang dikirimkan acak atau bukan mahasiswa.
- **Saran Perbaikan:**
  1. Tambahkan batasan maksimal jumlah ID yang boleh dikirimkan per request:
     ```php
     'student_ids' => ['required', 'array', 'min:1', 'max:50'],
     ```
  2. Gunakan Route Model Binding eksplisit `User $student` pada method `destroy()` agar Laravel otomatis memastikan entitas user valid sebelum relasi dilepas.

---

### Temuan 2: Kategori Performa (Performance)

- **Penjelasan Masalah:**
  Pada `Coursecontroller@show`, data mahasiswa yang belum terdaftar diambil menggunakan query `get()` tanpa batasan (`limit`) atau paginasi. Seluruh data tersebut kemudian dioper ke view dan di-render satu per satu sebagai elemen checkbox HTML.
- **Lokasi Kode:** `app/Http/Controllers/Coursecontroller.php:118-123` dan `resources/views/courses/show.blade.php:354-367`
- **Kode Saat Ini:**
  ```php
  // Di Controller:
  if (Route::has($routePrefix . '.mahasiswa.store')) {
      $data['available'] = User::query()
          ->where('role', 'mahasiswa')
          ->whereNotIn('id', $enrolled->modelKeys())
          ->orderBy('name')
          ->get(['id', 'name', 'nim_nip']);
  }

  // Di View Blade:
  @foreach ($available as $s)
      <label class="mk-student-option" ...>
          <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" ...>
          <span>{{ $s->name }}</span>
          <small>{{ $s->nim_nip }}</small>
      </label>
  @endforeach
  ```
- **Risiko Nyata:**
  Pada sistem kampus nyata dengan ribuan mahasiswa aktif, query `whereNotIn` dengan daftar ID yang panjang akan sangat lambat dieksekusi oleh basis data. Memuat ribuan objek Eloquent sekaligus ke memori PHP akan memicu *memory spike*. Selain itu, me-render ribuan node DOM checkbox di browser klien akan membuat halaman web menjadi berat dan macet (*browser lag / freezing*).
- **Saran Perbaikan:**
  Hindari memuat seluruh mahasiswa satu kampus ke dalam satu halaman. Terapkan paginasi, batasi dengan `limit(20)`, atau gunakan pencarian dinamis berbasis AJAX / API auto-complete sehingga data mahasiswa hanya dimuat saat dicari oleh admin.

---

### Temuan 3: Kategori Struktur & Keterbacaan (Structure & Clean Code)

- **Penjelasan Masalah:**
  1. Terdapat blok skrip JavaScript mentah (`<script>`) yang disisipkan langsung di tengah-tengah file template Blade untuk menangani filter checkbox mahasiswa.
  2. Logika otorisasi pendaftaran `$canEnroll` dihitung di dalam View Blade menggunakan pengecekan keberadaan rute `Route::has()` dan variabel `isset($available)`, bukan dikontrol dari Controller atau Policy.
  3. Terdapat inkonsistensi penamaan berkas controller pada branch ini: `Coursecontroller.php` (menggunakan huruf kecil 'c' pada controller) sementara controller lainnya menggunakan format standar PascalCase `CourseEnrollmentController.php`.
- **Lokasi Kode:** `resources/views/courses/show.blade.php:197-209` dan `resources/views/courses/show.blade.php:22-24`
- **Kode Saat Ini:**
  ```blade
  {{-- Logika di View: --}}
  @php
      $canEnroll = isset($available) && Route::has($routePrefix . '.mahasiswa.store');
  @endphp

  {{-- Script inline di tengah template: --}}
  <script>
      (function () {
          const input  = document.getElementById('mk-student-filter');
          const picker = document.getElementById('mk-student-picker');
          if (!input || !picker) return;

          input.addEventListener('input', function () {
              const q = input.value.trim().toLowerCase();
              picker.querySelectorAll('.mk-student-option').forEach(function (el) {
                  el.hidden = q !== '' && !el.dataset.search.includes(q);
              });
          });
      })();
  </script>
  ```
- **Risiko Nyata:**
  Menyisipkan skrip langsung di file Blade melanggar prinsip *separation of concerns* (pemisahan tampilan dan logika perilaku), tidak dapat di-minify atau di-bundle oleh Vite, dan rawan terblokir oleh aturan *Content Security Policy* (CSP) di server produksi. Inkonsistensi nama berkas juga dapat memicu *class not found* pada sistem operasi Linux yang bersifat *case-sensitive*.
- **Saran Perbaikan:**
  1. Pindahkan kode JavaScript ke berkas terpisah (misalnya `resources/js/student-picker.js`) atau letakkan di dalam stack `@push('scripts')`.
  2. Gunakan pengecekan otorisasi standar Laravel `@can('manageEnrollment', $mataKuliah)` pada View agar logika hak akses terpusat di Policy.
  3. Pastikan penamaan berkas controller konsisten menggunakan format PascalCase (`CourseController.php`).

---

## 3. Teks Siap Salin untuk Review di GitHub

Berikut adalah teks komentar yang sudah siap kamu salin dan tempelkan langsung pada baris kode yang sesuai di tab **Files changed** pada PR #81:

### Komentar 1 (Kategori Keamanan)
* **Berkas:** `app/Http/Controllers/CourseEnrollmentController.php`
* **Baris:** Klik tombol biru `+` pada baris **27**
* **Teks Komentar:**
```markdown
Temuan Keamanan: Belum ada batas maksimal jumlah input pada array `student_ids`

- **Risiko:** Aturan validasi saat ini hanya memeriksa `min:1`, tetapi belum membatasi jumlah maksimal item yang dapat dikirimkan (`max`). Jika pengguna mengirimkan ribuan ID mahasiswa sekaligus dalam satu request POST, server dan basis data akan terbebani oleh operasi `syncWithoutDetaching` yang berat (potensi *Memory Exhaustion* / DoS). Selain itu, pada method `destroy`, parameter `$student` hanya berupa integer tanpa validasi apakah user tersebut benar-benar bertipe mahasiswa.
- **Saran:** Tambahkan batasan kuota maksimal pada validasi array, misalnya `'student_ids' => ['required', 'array', 'min:1', 'max:50']`, dan pertimbangkan menggunakan Route Model Binding `User $student` pada method `destroy()`.
```

---

### Komentar 2 (Kategori Performa)
* **Berkas:** `app/Http/Controllers/Coursecontroller.php`
* **Baris:** Klik tombol biru `+` pada baris **118** (atau baris 119)
* **Teks Komentar:**
```markdown
**Temuan Performa: Memuat seluruh data mahasiswa tanpa paginasi (*unpaginated query*) ke memori**
- **Risiko:** Query `User::query()->where('role', 'mahasiswa')->whereNotIn(...)->get(...)` mengambil seluruh mahasiswa satu kampus yang belum terdaftar sekaligus ke dalam koleksi Eloquent. Pada basis data riil dengan ribuan mahasiswa, query `NOT IN` akan sangat lambat dan memakan banyak RAM server. Di samping itu, me-render ribuan node checkbox di browser klien akan membuat peramban mengalami lag (*freezing*).
- **Saran:** Hindari memuat seluruh data ke dalam satu halaman HTML. Gunakan paginasi, batasi dengan `limit(20)`, atau terapkan pencarian dinamis berbasis AJAX / server-side auto-complete agar data hanya diambil saat dibutuhkan.
```

---

### Komentar 3 (Kategori Struktur & Keterbacaan)
* **Berkas:** `resources/views/courses/show.blade.php`
* **Baris:** Klik tombol biru `+` pada baris **197** (pada tag `<script>`)
* **Teks Komentar:**
```markdown
**Temuan Struktur & Keterbacaan: Penulisan skrip JavaScript inline di dalam template Blade**
- **Risiko:** Menyisipkan tag `<script>` mentah di tengah file template Blade melanggar prinsip pemisahan tanggung jawab (*separation of concerns*). Skrip ini tidak ikut terkompilasi atau ter-minify oleh bundler Vite, lebih sulit dipelihara, dan dapat menimbulkan masalah jika server produksi menerapkan *Content Security Policy* (CSP). Selain itu, logika `$canEnroll` sebaiknya menggunakan pengecekan Policy `@can` daripada menghitung rute di dalam View.
- **Saran:** Pindahkan fungsi pencarian ini ke berkas JavaScript tersendiri di `resources/js/` atau tampung menggunakan `@push('scripts')`. Gunakan juga `@can('manageEnrollment', $mataKuliah)` untuk merapikan pengecekan otorisasi di tampilan.
```

---

## 4. Langkah Menaruh Review di Halaman GitHub

1. Buka tautan: [https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/81](https://github.com/AdeliaIsraE/kampuslms-kelompok-01/pull/81).
2. Masuk ke tab **Files changed** (di bagian atas tampilan PR).
3. Cari berkas `app/Http/Controllers/CourseEnrollmentController.php`:
   - Arahkan kursor ke baris **27**, klik ikon tanda tambah biru (**`+`**).
   - Tempelkan teks **Komentar 1**.
   - Klik tombol **Start a review** (tombol hijau).
4. Cari berkas `app/Http/Controllers/Coursecontroller.php`:
   - Arahkan kursor ke baris **118**, klik ikon tanda tambah biru (**`+`**).
   - Tempelkan teks **Komentar 2**.
   - Klik tombol **Add review comment**.
5. Cari berkas `resources/views/courses/show.blade.php`:
   - Arahkan kursor ke baris **197** (pada tag `<script>`), klik ikon tanda tambah biru (**`+`**).
   - Tempelkan teks **Komentar 3**.
   - Klik tombol **Add review comment**.
6. Di bagian kanan atas (di atas daftar berkas), klik tombol hijau bertuliskan **Submit review**.
7. Pilih opsi **Comment**, lalu klik **Submit review**.

---

## 5. Tindak Lanjut & Tanggapan Review yang Masuk (PR #31)

Sesuai ketentuan Modul 13 bagian BUILD, kelompok yang direview wajib menanggapi seluruh temuan (diterima dan diperbaiki, atau ditolak dengan alasan yang sah), serta minimal satu temuan benar-benar diperbaiki lewat commit baru.

- **PR yang Direview:** [PR #31 — feat:update fitur upload materials](https://github.com/Vexusian/kampuslms-B-kelompok-07/pull/31)
- **Reviewer:** `@06Victoire` (Kelompok 06)

### Rekapitulasi Tanggapan & Perbaikan Kode:

| No | Kategori | Ringkasan Temuan Reviewer | Keputusan | Tindak Lanjut / Alasan Teknis |
|:---:|---|---|:---:|---|
| **1** | **Keamanan** | Aturan validasi `file` dan `external_url` tidak kondisional (`nullable`). Jika user memilih tipe berkas tanpa mengunggah file, record tersimpan dengan `file_path = null` (data korup / 404 saat unduh). | **Diterima & Diperbaiki** | Menambahkan aturan kondisional `required_if:type,file` untuk berkas dan `required_if:type,link` untuk URL eksternal pada method `store()` dan `update()` di `app/Http/Controllers/MaterialController.php`. |
| **2** | **Performa** | Kueri materi pada `MaterialController@index` tidak memanggil eager loading `course`, memicu N+1 query saat pemeriksaan otorisasi `@can('update', $material)` di view. | **Diterima & Diperbaiki** | Memastikan kueri pagination materi memuat relasi `course` menggunakan `with('course')` pada `MaterialController.php:23`. |
| **3** | **Struktur & Keterbacaan** | 1. Duplikasi logika pemotongan ekstensi berkas `strtoupper(pathinfo(...))` berulang di beberapa file Blade view.<br>2. Usulan memindahkan seluruh validasi ke Form Request. | **Diterima Sebagian & Ditolak Sebagian** | **Diterima:** Dibuatkan Accessor `getExtensionAttribute()` pada Model `Material` dan seluruh pemanggilan di Blade disederhanakan menjadi `$material->extension`.<br>**Ditolak:** Tetap mempertahankan validasi di controller karena alur unggah berkas fisik privat membutuhkan penanganan respons error kondisional yang terikat erat dengan siklus controller, sementara Form Request difungsikan untuk otorisasi `authorize()`. |

---

## 6. Jawaban Checkpoint Minggu 13

### 1. Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.
- **Jumlah commit atas nama saya:** **44 commit** pada repositori proyek KampusLMS Kelompok 07:
  - 36 commit tercatat dengan identitas `ZalfaPutriSopyandi`
  - 8 commit tercatat dengan identitas `Zalfa Putri`
- **Data kontribusi tim (`git shortlog -sn --all`):**
  ```text
  104  Vexusian
    49  Vanessa Marie Tandiarru
    42  forbidenperson
    36  ZalfaPutriSopyandi
    17  10241072-jpg
    16  wahyyrmdann
     8  Zalfa Putri
     5  Tresia
  ```

---

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?
Perintah `git rm --cached .env` hanya menghapus berkas `.env` dari commit saat ini (*staging area/HEAD*), namun berkas tersebut beserta isinya **masih tersimpan di riwayat commit sebelumnya** (*git history*). Siapa pun yang meng-clone repositori atau melihat log lama tetap bisa membaca rahasia tersebut. Jika `.env` sudah sempat ter-push ke GitHub publik, seluruh kredensial di dalamnya (`APP_KEY`, password database, API key) harus dianggap telah bocor dan wajib segera diganti (*rotated*).

---

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan.

**Pilihan A — Temuan Keamanan (Berkas `app/Http/Controllers/CourseEnrollmentController.php:27` pada PR #81):**
* **Temuan:** Validasi input pendaftaran mahasiswa (`student_ids`) hanya mensyaratkan `min:1`, namun **belum memiliki batas maksimal (`max`)**.
* **Penjelasan Risiko (Gaya Wawancara Tanpa Membaca Catatan):**  
  *"Pada fungsi pendaftaran mahasiswa ke kelas, sistem hanya memeriksa bahwa minimal harus ada 1 mahasiswa yang dipilih, tapi tidak membatasi berapa jumlah maksimalnya. Risikonya, penyerang atau pengguna bisa mengirimkan ribuan ID mahasiswa sekaligus dalam satu kali request POST. Hal ini akan memaksa database mengeksekusi proses `syncWithoutDetaching` yang sangat berat secara bersamaan, sehingga server bisa kehabisan memori (Memory Exhaustion) atau mengalami DoS (Denial of Service) yang menyebabkan web macet dan tidak bisa diakses pengguna lain."*
* **Saran Perbaikan:** Tambahkan batasan kuota maksimal pada validasi array, misalnya `'student_ids' => ['required', 'array', 'min:1', 'max:50']`.

---

**Pilihan B — Temuan Performa (Berkas `app/Http/Controllers/Coursecontroller.php:118-123` pada PR #81):**
* **Temuan:** Pengambilan data mahasiswa yang belum terdaftar menggunakan query `whereNotIn` tanpa limit atau paginasi (*unpaginated query*).
* **Penjelasan Risiko (Gaya Wawancara Tanpa Membaca Catatan):**  
  *"Sistem mengambil seluruh data mahasiswa satu kampus yang belum terdaftar sekaligus ke dalam memori server tanpa paginasi. Kalau kampusnya punya ribuan mahasiswa, query database akan sangat lambat, server kehabisan RAM, dan browser klien akan lag parah karena harus merender ribuan elemen checkbox sekaligus di halaman web."*
* **Saran Perbaikan:** Terapkan paginasi atau gunakan pencarian dinamis berbasis AJAX agar data mahasiswa dimuat secara bertahap saat dicari saja.

---

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?

Dari *code review* oleh `@06Victoire` (Kelompok 06) pada [PR #31 (`feat:update fitur upload materials`)](https://github.com/Vexusian/kampuslms-B-kelompok-07/pull/31):

1. **Temuan 1 — Keamanan: Validasi input berkas tidak kondisional (DITERIMA & DIPERBAIKI):**
   * **Masalah:** Input `file` disetel `nullable` tanpa aturan kondisional berbasis tipe (`type`). Saat user memilih `type = file` namun tidak memilih berkas apa pun, validasi tetap lolos dan record tersimpan dengan `file_path = null` (data korup / error 404 saat unduh).
   * **Keputusan:** **Diterima.**
   * **Alasan & Perbaikan:** Temuan ini penting untuk menjaga integritas data. Diperbaiki pada `MaterialController.php` dengan menambahkan aturan kondisional `required_if:type,file` untuk input `file` dan `required_if:type,link` untuk `external_url`.

2. **Temuan 2 — Performa: N+1 Query pada pemeriksaan otorisasi daftar materi (DITERIMA & DIPERBAIKI):**
   * **Masalah:** Pada `MaterialController::index()`, kueri materi tidak melakukan eager loading relasi `course`, sehingga evaluasi `@can('update', $material)` dan `@can('delete', $material)` di view memicu query relasi `courses` berulang per baris materi.
   * **Keputusan:** **Diterima.**
   * **Alasan & Perbaikan:** Temuan ini sangat valid untuk efisiensi kueri. Diperbaiki dengan memastikan kueri memanggil eager loading `with('course')` pada `$course->materials()->with('course')->latest()->paginate(15)`.

3. **Temuan 3 — Struktur Kode: Duplikasi logika manipulasi ekstensi berkas di View Blade (DITERIMA SEBAGIAN & DITOLAK SEBAGIAN):**
   * **Bagian yang Diterima (Logika Ekstensi di View):** Pengecekan `strtoupper(pathinfo(...))` berulang di beberapa berkas view diterima dan diperbaiki dengan memindahkannya ke Accessor `getExtensionAttribute()` pada Model `Material`, sehingga di view cukup memanggil `$material->extension`.
   * **Bagian yang Ditolak (Memindahkan Seluruh Validasi ke Form Request):** Ditolak dengan alasan teknis, karena saat ini alur validasi di controller memiliki dependensi kondisional dan *custom error feedback* yang terikat erat dengan siklus penyimpanan fisik berkas lokal di controller. Form Request tetap difungsikan sebagai gerbang otorisasi utama berbasis Policy (`authorize()`). Menolak masukan dengan argumen teknis yang tepat adalah tanggapan yang sah dan bernilai penuh sesuai panduan Modul 13.

---

### 5. Kenapa PR sebaiknya kecil dan satu fitur?
1. **Mudah ditelaah (*reviewable*):** Peninjau kode dapat memahami alur logika secara mendalam tanpa kelelahan membaca ratusan baris kode sekaligus.
2. **Minim konflik (*merge conflict*):** PR kecil lebih cepat digabungkan sehingga branch tidak tertinggal jauh dari branch utama.
3. **Mudah dilacak dan dibatalkan (*revert*):** Jika muncul bug setelah digabungkan, sumber masalah sangat mudah diidentifikasi dan dapat dibatalkan tanpa mengganggu fitur lainnya.

---

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
1. **Identitas commit bisa dipalsukan:** Pengarang commit (*author name & email*) dapat diubah secara bebas menggunakan perintah `git commit --author`.
2. **Bobot pekerjaan berbeda-beda:** Satu commit yang merancang arsitektur atau menyelesaikan algoritma rumit jauh lebih bernilai dibanding puluhan commit kecil yang hanya memperbaiki spasi, typo, atau dokumentasi.
3. **Kontribusi non-kode tidak terlihat:** Kontribusi penting seperti review kode rekan tim, diskusi desain sistem, pemecahan bug bersama, dan pengujian aplikasi tidak selalu menghasilkan commit git.
