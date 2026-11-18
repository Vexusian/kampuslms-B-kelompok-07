# Catatan Minggu 9

## Nama : Tresia Uyang 
## NIM : 10241072

### READ — Telusuri satu berkas 

#### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?
Berkas submission disimpan pada disk `local` yang dikonfigurasi dalam file `config/filesystems.php`. Disk `local` menggunakan direktori `stroage/app/private` sebagai lokasi penyimpanan utama. Oleh karena itu, setiap berkas submissiom akan disimpan di dalam folder `submission`, kemudian dikelompokkan berdasarkan ID assignment.

Pola penyimpanan berkas submission adalah `stroage/app/private/submission/{assignment_id}/{nama_acak}`.`{ekstensi}`

#### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.

 Pada database, informasi lokasi berkas disimpan dalam kolom `file_path`. Path yang tersimpan merupakan path relatif terhadap root disk `local`, bukan lokasi lengkap berkas di dalam komputer.

 Contoh:
 nilai `file_path` pada database adalah `submissios/7/nama-acak.pdf`

 sementara itu,lokasi fisik berkas pada sistem penyimpanan adalah `storage/app/private/submissions/7/nama-acak.pdf`

Perbedaannya hanya terletak pada penulisan path. Database menyimpan lokasi relatif, sedangkan lokasi fisiknya menunjukkan direktori lengkap tempat berkas tersebut disimpan.

#### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?

Tidak berhasil, karena berkas submission disimpan di dalam folder privat (`storage/app/private`), bukan di folder publik (`public/storage`). Jadi, berkas tidak bisa dibuka langsung melalui browser menggunakan URL biasa.

Untuk mengakses atau mengunduhnya, pengguna harus melalui route controller yang akan memeriksa autentikasi dan Policy terlebih dahulu agar hanya pengguna yang memiliki izin yang bisa mengakses berkas tersebut.

#### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?

Disk `local` menggunakan direktori `stroage/app/private` untuk menyimpan berkas yang bersifat privat dan membutuhkan pemeriksaan izin sebelum diakses sedangkkan disk `public` menggunakan direktori `storage/app/public` untuk menyimpan berkas yang boleh diakses secara umum melalui URL `/stroage`.

Jadi, disk `local` lebih cocok untuk berkas yang membutuhkan keamanan, sedangkan disk `public` digunakan untuk berkas yang memang boleh diliat oleh siapa saja.

#### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda **tidak boleh** ada di sana?

Perintah `php artisan storage:link` digunakan untuk membuat symbolic link atau penghubung antara folder `public/storage` dengan `storage/app/public`. Dengan adanya penghubung tersebut, berkas yang disimpan di disk `public` bisa diakses langsung melalui browser.

Materi dan submission tidak boleh disimpan di sana karena berkas tersebut bersifat privat dan hanya boleh diakses oleh pengguna yang memiliki izin. Jika disimpan di folder publik, orang lain berpotensi mengaksesnya secara langsung tanpa melalui pemeriksaan autentikasi dan Policy. Oleh karena itu, berkas tetap disimpan di disk `local` agar keamanannya lebih terjaga.

### BREAK — Delapan kerusakan 

| # | Percobaan | Hasil pengamatan |
|---|---|---|
| 1 | Pindahkan submission ke `storage/app/public`, lalu buka URL berkas tanpa login. | Berkas dapat diakses langsung. Akses melewati autentikasi dan Policy Laravel karena web server menyajikannya sebagai berkas publik. |
| 2 | Hapus `Gate::authorize()` dari endpoint unduh, lalu coba unduh submission mahasiswa lain dengan mengubah ID. | Endpoint tidak lagi memeriksa hak akses berdasarkan pemilik. Pengguna yang sudah login berpotensi mengunduh berkas orang lain; ini merupakan IDOR. |
| 3 | Simpan berkas dengan `storeAs()` memakai nama asli, lalu unggah dua berkas bernama `tugas.pdf` ke folder yang sama. | Berkas kedua dapat menimpa berkas pertama. Penggunaan nama acak mencegah benturan nama seperti ini. |
| 4 | Ubah nama berkas PHP menjadi `.pdf`, lalu bandingkan validasi `extensions` dengan `mimes`. | Validasi yang hanya memeriksa ekstensi dapat menerima nama `.pdf` palsu. Validasi `mimes` memeriksa tipe berdasarkan isi berkas dan menolak berkas yang bukan PDF. |
| 5 | Ubah `max:5120` menjadi `max:5`, lalu unggah berkas 1 MB. | Berkas ditolak karena `max:5` berarti maksimal 5 KB, bukan 5 MB. |
| 6 | Atur `upload_max_filesize=1M`, lalu unggah berkas 3 MB dengan aturan Laravel `max:5120`. | Unggahan ditolak oleh batas PHP sebelum validasi Laravel dapat menerima berkas tersebut. Batas server harus diperiksa bersama batas aplikasi. |
| 7 | Ganti `updateOrCreate()` dengan `create()`, lalu coba membuat submission kedua untuk mahasiswa dan tugas yang sama. | Jika request sampai ke database, unique constraint pada pasangan `(assignment_id, user_id)` menolak baris duplikat. **Catatan:** pada kode saat ini, `SubmissionPolicy::create()` menolak pengumpulan kedua lebih awal, sehingga constraint database tidak tercapai kecuali pemeriksaan policy dilewati saat percobaan. |
| 8 | Hapus baris submission langsung melalui SQL, lalu periksa folder storage. | Baris database hilang, tetapi berkas fisiknya tertinggal karena penghapusan melalui SQL tidak menjalankan event `deleting` pada model Eloquent. Berkas itu menjadi berkas yatim. |


### BUILD — Alur berkas KampusLMS

#### 1. **Unggah materi** oleh dosen: PDF/PPTX/DOCX, maksimal 20 MB, disk privat, atau alternatif berupa tautan eksternal.
- Lokasi kode: `app/Http/Controllers/MaterialController.php, method store`.
- Berkas materi disimpan di disk privat `local` pada folder materials/{course_id}. Format yang diperbolehkan adalah PDF, PPTX, dan DOCX dengan ukuran maksimal 20 MB (`max:20480`).
- Nama berkas dibuat secara acak menggunakan `$file->store()`, sedangkan nama asli, ukuran, dan tipe berkas disimpan secara terpisah di database.
- Selain mengunggah berkas, dosen juga bisa menambahkan materi berupa tautan eksternal dengan validasi URL.

#### 2. **Unduh materi** oleh mahasiswa yang terdaftar di mata kuliah tersebut — lewat controller + Policy.
- Lokasi kode: `MaterialController.php`, method download, dan `app/Policies/MaterialPolicy.php`.
- Sebelum berkas diunduh, sistem menjalankan `Gate::authorize('download', $material)` untuk memeriksa izin pengguna.
- Hak akses diberikan kepada admin, dosen pengampu, dan mahasiswa yang terdaftar pada mata kuliah. Setelah izin sesuai, berkas dikirim melalui `Storage::disk('local')->download()`.

#### 3. **Pengumpulan tugas** oleh mahasiswa: `updateOrCreate`, `is_late` dihitung server, tolak jika `allow_late` bernilai false dan sudah lewat deadline.
- Lokasi kode: `app/Http/Controllers/SubmissionController.php`, method `store`.
- Berkas tugas disimpan pada disk privat dengan folder `submissions/{assignment_id}`. Nama fisik berkas dibuat secara acak menggunakan `$file->store()`, sedangkan nama aslinya disimpan pada kolom `original_filename`.
- Format berkas yang diterima adalah PDF, DOC, dan DOCX dengan ukuran maksimal 5 MB (`max:5120`).
- Waktu pengumpulan dan status keterlambatan (`is_late`) ditentukan oleh server berdasarkan tenggat tugas (`due_at`). Jika keterlambatan tidak diperbolehkan dan tenggat sudah lewat, sistem akan menolak pengumpulan.
- Controller menggunakan `Submission::updateOrCreate()` untuk menyimpan atau memperbarui submission berdasarkan `assignment_id` dan `user_id`. Berkas lama juga dihapus sebelum diganti dengan berkas baru.

> Catatan pencocokan kode: Halaman tugas menampilkan tombol “Kirim Ulang Tugas”, dan controller memakai `updateOrCreate()`. Namun, `SubmissionPolicy::create()` menolak pengumpulan jika mahasiswa sudah memiliki submission. Karena `StoreSubmissionRequest` memeriksa izin melalui policy tersebut, pengumpulan ulang saat ini tertahan oleh pemeriksaan otorisasi. Selain itu, aturan validasi server menetapkan `file` sebagai `nullable`, walaupun input berkas di halaman diberi atribut `required`.

#### 4. **Unduh submission**: hanya pemiliknya, dosen pengampu, dan admin.
- Lokasi kode: `app/Http/Controllers/SubmissionController.php`, method `download`, dan `app/Policies/SubmissionPolicy.php`.
- Sebelum mengirimkan berkas, controller melakukan pemeriksaan menggunakan `Gate::authorize('download', $submission)`.
- Pengunduhan hanya diperbolehkan bagi pemilik submission, dosen pengampu, dan admin. Mahasiswa lain tidak memiliki izin untuk mengakses berkas tersebut.

#### 5. **Daftar pengumpulan** untuk dosen: siapa sudah/belum mengumpulkan, ditandai terlambat.
- Lokasi kode: `app/Http/Controllers/SubmissionController.php`, method `index`, dan `resources/views/submissions/index.blade.php`.
- Halaman dosen menampilkan jumlah mahasiswa yang terdaftar, jumlah mahasiswa yang sudah mengumpulkan, serta jumlah mahasiswa yang belum mengumpulkan tugas.
- Informasi submission juga ditampilkan secara terpisah, meliputi nama mahasiswa, nama dan ukuran berkas, waktu pengumpulan, status keterlambatan, serta nilai.

#### 6. **Penghapusan berkas fisik** saat record dihapus, termasuk penanganan kasus cascade.
- Lokasi kode: `app/Models/Submission.php`, `app/Models/Material.php`, `app/Models/Assignment.php`, dan `app/Models/Course.php`.
- Event `deleting` pada model `Submission` dan `Material` menghapus berkas dari disk `local`.
- Saat assignment dihapus, model `Assignment` menghapus submission melalui Eloquent dan membersihkan folder `submissions/{assignment_id}`.
- Saat course dihapus, model `Course` menghapus materi dan assignment melalui Eloquent, sehingga proses penghapusan berkas ikut dijalankan.

 >Penghapusan massal melalui query builder, SQL langsung, atau `cascadeOnDelete` di database tidak menjalankan event Eloquent. Karena itu, penghapusan terkait berkas dilakukan secara eksplisit melalui model.

#### 7. Tampilkan `original_name` dan ukuran berkas dalam format yang terbaca manusia.
- Lokasi kode: accessor di `app/Models/Material.php` dan `app/Models/Submission.php`.
- Tampilan materi dan submission menggunakan nama asli berkas dan format ukuran yang mudah dibaca, seperti B, KB, MB, atau GB.

---

