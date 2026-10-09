# PANDUAN PENGUJIAN KEAMANAN LANGSUNG (CURL) — ASESMEN UTS (BAGIAN 3)

Dokumen ini merupakan panduan teknis operasional untuk **Bagian 3 — Uji Keamanan Langsung (5 Menit)** pada [04-modul-minggu-08-uts.md](file:///c:/Users/vanes/laragon/www/kampuslms/modules/04-modul-minggu-08-uts.md), yang memvalidasi penutupan titik rawan IDOR (*Insecure Direct Object References*) pada [docs/keamanan.md](file:///c:/Users/vanes/laragon/www/kampuslms/docs/keamanan.md).

---

## 1. PERSIAPAN PENGUJIAN (SETUP COOKIE SESI)

Sebelum menjalankan pengujian dengan `curl`, siapkan file cookie untuk masing-masing peran menggunakan helper simulasi login lokal:

```bash
# 1. Simpan sesi Mahasiswa Demo (User ID: 5)
curl.exe -s -c cookie_mhs.txt -L http://127.0.0.1:8000/dev/login/5 -o NUL

# 2. Simpan sesi Dosen Demo (User ID: 2 - Pengampu Basis Data)
curl.exe -s -c cookie_dosen.txt -L http://127.0.0.1:8000/dev/login/2 -o NUL

# 3. Simpan sesi Dosen Lain (User ID: 3 - Bukan pengampu)
curl.exe -s -c cookie_dosen_lain.txt -L http://127.0.0.1:8000/dev/login/3 -o NUL

# 4. Simpan sesi Admin (User ID: 1)
curl.exe -s -c cookie_admin.txt -L http://127.0.0.1:8000/dev/login/1 -o NUL
```

> **Catatan Windows PowerShell:** Gunakan `curl.exe` agar mengeksekusi binary cURL asli (bukan alias PowerShell `Invoke-WebRequest`).

---

## 2. DAFTAR COMMAND CURL UNTUK SETIAP TITIK RAWAN IDOR

Berikut adalah perintah `curl` untuk menguji setiap baris tabel titik rawan IDOR dari [docs/keamanan.md](file:///c:/Users/vanes/laragon/www/kampuslms/docs/keamanan.md), lengkap dengan penjelasan baris per baris dan letak kode penahannya.

---

### Titik Rawan #10 (Kritis): Mengintip Submission / Jawaban Mahasiswa Lain
**Skenario Serangan:** Mahasiswa A mencoba membaca berkas/jawaban milik Mahasiswa B dengan mengubah parameter ID submission pada URL.

#### Command cURL:
```bash
curl.exe -i -b cookie_mhs.txt http://127.0.0.1:8000/submissions/2
```

#### Penjelasan Baris Command:
- `curl.exe`: Menjalankan tool transfer HTTP cURL.
- `-i`: Menampilkan header respon HTTP (untuk melihat status code `HTTP/1.1 403 Forbidden`).
- `-b cookie_mhs.txt`: Menyertakan cookie sesi terautentikasi milik Mahasiswa Demo.
- `http://127.0.0.1:8000/submissions/2`: URL target menuju submission dengan ID 2 (milik mahasiswa lain).

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('view', $submission)` di [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php#L55).
- **Aturan Otorisasi:** [SubmissionPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/SubmissionPolicy.php#L32-L48) pada method `view()`:
  ```php
  if ($submission->user_id === $user->id) {
      return true; // Hanya pemilik submission yang diizinkan
  }
  // Dosen pengampu MK dan admin juga diizinkan, selain itu DITOLAK (return false)
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`** beserta halaman error 403 kustom.

---

### Titik Rawan #2: Mengakses Kelas yang Tidak Diikuti / Bukan Diampu
**Skenario Serangan:** Mahasiswa membuka detail kelas tertutup yang tidak ia ikuti, atau Dosen A membuka kelas milik Dosen B.

#### Command cURL:
```bash
curl.exe -i -b cookie_mhs.txt http://127.0.0.1:8000/courses/4
```

#### Penjelasan Baris Command:
- `-b cookie_mhs.txt`: Request dikirim atas nama Mahasiswa Demo.
- `/courses/4`: ID mata kuliah yang tidak di-enroll oleh mahasiswa tersebut.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('view', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L106).
- **Aturan Otorisasi:** [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L25-L40):
  ```php
  if ($user->role === 'mahasiswa') {
      return $course->students()->where('users.id', $user->id)->exists();
  }
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #3: Dosen A Mengubah Mata Kuliah Milik Dosen B (Horizontal Escalation)
**Skenario Serangan:** Dosen lain mengirim request HTTP PUT untuk mengedit nama/SKS mata kuliah milik dosen pengampu lain.

#### Command cURL:
```bash
curl.exe -i -X POST -b cookie_dosen_lain.txt \
  -d "_method=PUT" \
  -d "code=IF999" \
  -d "name=Hacked Course" \
  -d "sks=3" \
  -d "status=active" \
  -d "lecturer_id=3" \
  http://127.0.0.1:8000/courses/1
```

#### Penjelasan Baris Command:
- `-X POST -d "_method=PUT"`: Metode HTTP spoofing standar Laravel untuk mengirim request pembaruan data (`PUT`).
- `-b cookie_dosen_lain.txt`: Menggunakan sesi Dosen yang **bukan pengampu** kelas ID 1.
- `-d "..."`: Payload data yang ingin diubah secara ilegal.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('update', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L139) dan `authorize()` di [UpdateCourseRequest.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Requests/UpdateCourseRequest.php#L12).
- **Aturan Otorisasi:** [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L55-L58):
  ```php
  return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #4: Mahasiswa / Dosen Membuat Mata Kuliah Baru (Vertical Escalation)
**Skenario Serangan:** Non-admin memotong jalur UI dan mengirim POST langsung untuk mendaftarkan mata kuliah baru.

#### Command cURL:
```bash
curl.exe -i -X POST -b cookie_mhs.txt \
  -d "code=HACK101" \
  -d "name=Mata Kuliah Ilegal" \
  -d "sks=3" \
  -d "status=active" \
  -d "lecturer_id=2" \
  http://127.0.0.1:8000/courses
```

#### Penjelasan Baris Command:
- `-X POST`: Mengirim request HTTP POST untuk membuat resource baru.
- `-b cookie_mhs.txt`: Dikirim oleh akun ber-role Mahasiswa.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('create', Course::class)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L92).
- **Aturan Otorisasi:** [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L45-L48):
  ```php
  return $user->role === 'admin';
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #5: Dosen / Mahasiswa Menghapus Mata Kuliah
**Skenario Serangan:** Dosen atau mahasiswa mengirim request `DELETE` untuk menghapus mata kuliah dari database.

#### Command cURL:
```bash
curl.exe -i -X POST -b cookie_dosen.txt \
  -d "_method=DELETE" \
  http://127.0.0.1:8000/courses/1
```

#### Penjelasan Baris Command:
- `-d "_method=DELETE"`: Method spoofing DELETE ke endpoint resource course.
- `-b cookie_dosen.txt`: Dilakukan oleh akun Dosen.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('delete', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L155).
- **Aturan Otorisasi:** [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L63-L66):
  ```php
  return $user->role === 'admin'; // Hanya admin yang boleh menghapus MK
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #6: Dosen Lain Mengelola Peserta Kelas (Enrollment Bajakan)
**Skenario Serangan:** Dosen yang bukan pengampu kelas mencoba mendaftarkan mahasiswa ke kelas orang lain.

#### Command cURL:
```bash
curl.exe -i -X POST -b cookie_dosen_lain.txt \
  -d "student_id=5" \
  http://127.0.0.1:8000/courses/1/enroll
```

#### Penjelasan Baris Command:
- `-b cookie_dosen_lain.txt`: Menggunakan akun Dosen yang bukan pemilik kelas ID 1.
- `/courses/1/enroll`: Endpoint enrollment kelas ID 1.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('enroll', $course)` di [CourseController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/CourseController.php#L167).
- **Aturan Otorisasi:** [CoursePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/CoursePolicy.php#L72-L75):
  ```php
  return $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #7: Mengakses Materi Perkuliahan Tanpa Terdaftar
**Skenario Serangan:** Mahasiswa luar kelas mengakses materi privat dari kelas yang tidak diikutinya.

#### Command cURL:
```bash
curl.exe -i -b cookie_mhs.txt http://127.0.0.1:8000/materials/10
```

#### Penjelasan Baris Command:
- `/materials/10`: ID materi milik mata kuliah di mana Mahasiswa Demo tidak terdaftar.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('view', $material)` di [MaterialController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/MaterialController.php#L51).
- **Aturan Otorisasi:** [MaterialPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/MaterialPolicy.php#L25-L42).
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #8: Mahasiswa Mengunggah Materi Kuliah Palsu
**Skenario Serangan:** Mahasiswa mengirim payload untuk membuat materi baru di kelas dosen.

#### Command cURL:
```bash
curl.exe -i -X POST -b cookie_mhs.txt \
  -d "title=Materi Palsu" \
  -d "content=Uraian materi bajakan" \
  http://127.0.0.1:8000/dosen/courses/1/materials
```

#### Penjelasan Baris Command:
- Mengakses rute prefix `/dosen/...` dengan kredensial Mahasiswa.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan Lapis 1 (Middleware):** `Route::middleware('role:admin,dosen')` di [routes/web.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/web.php#L70).
- **Penahan Lapis 2 (Policy):** `Gate::authorize('create', [Material::class, $course])` di [MaterialController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/MaterialController.php#L28).
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #9: Mengakses Soal Tugas Tanpa Terdaftar di Kelas
**Skenario Serangan:** Mahasiswa kelas lain mencoba mencuri soal tugas dari kelas lain.

#### Command cURL:
```bash
curl.exe -i -b cookie_mhs.txt http://127.0.0.1:8000/assignments/10
```

#### Penjelasan Baris Command:
- `/assignments/10`: Tugas dari mata kuliah yang tidak diikuti oleh Mahasiswa Demo.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('view', $assignment)` di [AssignmentController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/AssignmentController.php#L55).
- **Aturan Otorisasi:** [AssignmentPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/AssignmentPolicy.php#L25-L42).
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #11: Mahasiswa Melihat Seluruh Berkas Masuk Satu Angkatan
**Skenario Serangan:** Mahasiswa membuka daftar pengumpulan semua teman sekelasnya.

#### Command cURL:
```bash
curl.exe -i -b cookie_mhs.txt http://127.0.0.1:8000/dosen/assignments/1/submissions
```

#### Penjelasan Baris Command:
- `/dosen/assignments/1/submissions`: Endpoint ringkasan penilaian pengumpulan tugas.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan Lapis 1:** Middleware `role:admin,dosen` di [routes/web.php](file:///c:/Users/vanes/laragon/www/kampuslms/routes/web.php#L70).
- **Penahan Lapis 2:** `Gate::authorize('viewAny', [Submission::class, $assignment])` di [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php#L18).
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

### Titik Rawan #12: Admin atau Mahasiswa Memberi Nilai Tugas (Grading Bypass)
**Skenario Serangan:** Mahasiswa memberi nilai 100 untuk dirinya sendiri, atau Admin mencoba memberi nilai padahal tugas menilai hanya milik Dosen pengampu MK sendiri (Tabel 3 Spesifikasi).

#### Command cURL:
```bash
# 1. Uji oleh Mahasiswa (Mencoba memberi nilai sendiri):
curl.exe -i -X POST -b cookie_mhs.txt \
  -d "_method=PUT" \
  -d "score=100" \
  -d "feedback=Lulus Otomatis" \
  http://127.0.0.1:8000/submissions/1/grade

# 2. Uji oleh Admin (Admin dilarang memberi nilai sesuai Tabel 3):
curl.exe -i -X POST -b cookie_admin.txt \
  -d "_method=PUT" \
  -d "score=100" \
  -d "feedback=Nilai dari Admin" \
  http://127.0.0.1:8000/submissions/1/grade
```

#### Penjelasan Baris Command:
- Mengirim HTTP PUT ke `/submissions/{id}/grade` dengan payload nilai.

#### Apa yang Menahannya & Lokasi Kode:
- **Penahan:** `Gate::authorize('create', [Grade::class, $submission])` di [SubmissionController.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Http/Controllers/SubmissionController.php#L68).
- **Aturan Otorisasi:** [GradePolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/GradePolicy.php#L40-L47):
  ```php
  // Khusus Dosen pengampu mata kuliah terkait:
  if ($user->role === 'dosen') {
      return $course && $course->lecturer_id === $user->id;
  }
  return false; // Admin dan Mahasiswa DITOLAK
  ```
- **Hasil yang Muncul:** Status **`HTTP/1.1 403 Forbidden`**.

---

## 3. CARA MENJAWAB DI DEPAN PENGUJI UTS (5 MENIT)

Saat penguji menunjuk satu baris dan meminta eksekusi langsung:
1. **Jalankan command cURL terkait** di terminal.
2. **Tunjukkan status HTTP:** Tunjukkan baris `HTTP/1.1 403 Forbidden` pada output terminal.
3. **Buka file kodenya:** Buka file controller dan policy yang disebutkan di atas (misal: [SubmissionPolicy.php](file:///c:/Users/vanes/laragon/www/kampuslms/app/Policies/SubmissionPolicy.php)).
4. **Jelaskan logika pertahanannya:**
   > *"Request tersebut tertahan di level server karena di `SubmissionController` kami memanggil `Gate::authorize()`. Policy memeriksa identitas `$user->id` dari session cookies dengan `$submission->user_id` di database. Karena tidak cocok, Laravel langsung membatalkan request dan mengembalikan response 403 Forbidden sebelum data apa pun terbaca."*
