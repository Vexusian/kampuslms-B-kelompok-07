# Catatan Minggu 10 — Realtime, Event, Queue, dan Notifikasi

## Nama: Tresia Uyang
## NIM: 10241072

### READ — Ikuti satu job

1. Matikan worker. Buat satu tugas baru. Buka tabel `jobs` — apa isinya?
> Saat worker dimatikan lalu tugas baru dibuat, job untuk mengirim notifikasi tetap tersimpan di tabel `jobs`. Job tersebut menunggu sampai worker dijalankan.

2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?
> Kolom `payload` menunjukkan Listener `SendAssignmentPublishedNotification` dan Event `AssignmentPublished`. Job yang diperiksa berkaitan dengan tugas ID 23.

3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.
> Setelah `php artisan queue:work` dijalankan, worker memproses job pengiriman notifikasi. Job yang berhasil diproses kemudian tidak lagi muncul di tabel `jobs`.

4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?
> Hasil pemeriksaan menunjukkan ada 15 baris notifikasi dengan ID penerima yang berbeda. Jumlah ini sesuai dengan jumlah mahasiswa yang terdaftar pada mata kuliah tersebut.

5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?
> Payload notifikasi tugas memuat `type`, `assignment_id`, `assignment_title`, `course_id`, `course_name`, `due_at`, dan `message`. Nilai dan feedback mahasiswa tidak disertakan. Metadata mata kuliah dan tenggat dipakai untuk menampilkan konteks tugas.

### BREAK — Delapan kerusakan (50 menit)

| # | Yang dicoba | Hasil yang diharapkan | Hasil pengamatan / status uji |
|---|-------------|----------------------|-----------------------------|
| 1 | Set `QUEUE_CONNECTION=database`, jangan jalankan worker, lalu buat tugas baru | Job menunggu di antrean dan notifikasi belum diterima sampai worker aktif; request tidak menampilkan error. | Job masuk ke tabel `jobs`. Setelah worker dijalankan, job diproses dan dihapus; 15 notifikasi untuk tugas ID 23 muncul di tabel `notifications`. |
| 2 | Hapus `implements ShouldQueue` dari Listener, lalu buat tugas untuk 40 mahasiswa | Listener berjalan sinkron; waktu respons perlu dibandingkan dengan Listener yang memakai antrean. | `event:list` tidak lagi menampilkan label `ShouldQueue`. Label kembali setelah kode dipulihkan. Waktu respons untuk 40 mahasiswa tidak tercatat, jadi dampak waktunya belum terukur. |
| 3 | Ganti `QUEUE_CONNECTION=sync`, ulangi nomor 2 | Notifikasi diproses langsung tanpa menunggu worker dan tanpa job tertunda di tabel `jobs`. | Notifikasi `uji break sync` langsung masuk ke tabel `notifications` tanpa membuat job di tabel `jobs`. Setelah uji, koneksi dikembalikan ke `database`. |
| 4 | Buat Notification melempar exception, jalankan worker | Percobaan ulang mengikuti nilai `tries`; job yang gagal seluruhnya tercatat di `failed_jobs`. | Dua job notifikasi gagal saat error dipasang. Setelah penyebabnya diperbaiki dan job dicoba ulang, worker menampilkan `DONE`; `queue:failed` tidak lagi menemukan job gagal. |
| 5 | Hentikan worker di tengah pemrosesan job | Job yang sedang diambil worker tercatat sebagai reserved dan bisa diproses lagi setelah masa reservasi berakhir. | Worker menampilkan `RUNNING`, lalu dihentikan dengan Ctrl+C. Tabel `jobs` menunjukkan `attempts` bernilai 1 dan `reserved_at` terisi. Setelah menunggu, worker dijalankan lagi untuk melanjutkan pemrosesan. |
| 6 | Masukkan nilai mahasiswa ke payload notifikasi, lalu buka tabel `notifications` sebagai admin | Payload tidak menyimpan nilai atau feedback yang bersifat sensitif. | Pemeriksaan source dan payload menunjukkan bahwa `score` dan `feedback` tidak disertakan. Uji dengan menambahkan nilai secara langsung ke payload tidak tercatat. |
| 7 | Panggil endpoint "tandai dibaca" dengan ID notifikasi orang lain | Pengguna tidak dapat menandai atau membuka notifikasi milik pengguna lain. | Pemeriksaan source menunjukkan controller mencari notifikasi melalui relasi pengguna yang sedang login. Uji request langsung dengan ID milik orang lain tidak tercatat. |
| 8 | Jika memakai Reverb, ubah `PrivateChannel` menjadi `Channel` lalu coba berlangganan ke channel pengguna lain | Channel publik dapat membuka siaran kepada pengguna yang tidak berhak. | **Tidak berlaku pada repo ini:** pencarian source tidak menemukan Reverb atau channel broadcasting, sehingga tidak ada channel yang dapat diubah atau diuji. |

### FIX — Repo cacat (30 menit)

Branch `w10` pada repo `kampuslms-broken` berisi **7 masalah**: Listener tanpa `ShouldQueue`, notifikasi dikirim dalam loop satu per satu (N+1), payload memuat nilai mahasiswa, endpoint tandai-dibaca tanpa pemeriksaan kepemilikan, tidak ada `failed_jobs`, job tanpa `tries`, dan satu channel broadcast yang publik.

Pada repo KampusLMS yang sedang dibuka, penanganan untuk masalah serupa terlihat pada bagian berikut:

1. **Listener berjalan di antrean:** `SendAssignmentPublishedNotification` dan `SendGradeSubmittedNotification` mengimplementasikan `ShouldQueue`.
2. **Pengiriman massal:** `SendAssignmentPublishedNotification` memuat relasi `course.students` dan mengirim notifikasi dengan `Notification::send()`.
3. **Payload tanpa nilai:** `GradeSubmittedNotification` tidak menambahkan `score` atau `feedback` ke payload.
4. **Pemeriksaan kepemilikan:** `NotificationController` mencari notifikasi melalui relasi milik pengguna yang sedang login.
5. **Tabel job gagal:** migrasi `create_failed_jobs_table` tersedia.
6. **Retry dan pencatatan kegagalan:** listener dan notification mengatur `tries`, `backoff`, serta method `failed()` yang menulis log.
7. **Channel broadcast:** repo ini tidak memiliki konfigurasi Reverb atau channel broadcasting yang digunakan.

**Status repo latihan:** Folder `kampuslms-broken` dan branch `w10` tidak ditemukan di workspace ini. Karena itu, perubahan pada repo latihan dan PR belum dapat diverifikasi. Waktu respons sebelum dan sesudah perbaikan juga belum dicatat; angka benchmark tidak dicantumkan agar tidak mengarang hasil.

### BUILD — Milestone M3

Deliverable yang dinilai sebagai **Tugas 3**:

1. **Event, Listener, dan Notification untuk tugas baru dan nilai:**
   - **Implementasi:** Event `AssignmentPublished` dijalankan saat tugas dipublikasikan, sedangkan `GradeSubmitted` dijalankan saat submission diberi nilai. Setiap Listener menggunakan `ShouldQueue` untuk mengirim Notification melalui channel database.

2. **Queue database dan worker:**
   - **Implementasi:** Proyek menggunakan `QUEUE_CONNECTION=database`. Migrasi untuk tabel `jobs`, `failed_jobs`, dan `job_batches` tersedia. README menjelaskan cara menjalankan worker dengan perintah `php artisan queue:work`.

3. **Penanganan job gagal dan retry:**
   - **Implementasi:** Listener dan Notification memiliki `tries = 3`, `backoff = [10, 30, 60]`, serta method `failed()` untuk mencatat error ke log. Pengujian error dan retry telah dilakukan. Setelah penyebab error dihapus, job yang sebelumnya gagal berhasil diproses.

4. **Lonceng notifikasi di navbar:**
   - **Implementasi:** Navbar menampilkan jumlah notifikasi yang belum dibaca saat halaman dimuat. Pada kode saat ini belum ditemukan polling atau Reverb, sehingga jumlahnya belum diperbarui otomatis setiap 15 detik. Bagian ini masih belum memenuhi deliverable realtime/polling.

5. **Halaman daftar dan status notifikasi:**
   - **Implementasi:** Halaman notifikasi menampilkan daftar pemberitahuan dan menyediakan aksi untuk menandai satu atau semua notifikasi sebagai sudah dibaca. Controller membatasi pencarian pada notifikasi milik pengguna yang sedang login. Template saat ini menampilkan satu ringkasan pesan untuk setiap notifikasi.

6. **Alur penilaian submission:**
   - **Implementasi:** Dosen dapat memberi nilai dan feedback pada submission. Setelah nilai disimpan, Event memicu pengiriman notifikasi kepada mahasiswa pemilik submission. Mahasiswa dapat membuka detail submission untuk melihat nilai dan feedback.

7. **Rekap nilai per mata kuliah dan per mahasiswa:**
   - **Implementasi:** Dosen dapat membuka rekap submission dan nilai melalui halaman nilai mata kuliah. Mahasiswa dapat melihat nilai dan feedback pada detail submission miliknya sendiri.

8. **Payload notifikasi tanpa nilai atau feedback:**
   - **Implementasi:** `GradeSubmittedNotification` tidak memasukkan `score` atau `feedback` ke payload. Payload tetap memuat metadata lain, seperti ID submission dan tugas, judul tugas, nama mata kuliah, waktu penilaian, dan pesan. Jadi, data nilai terlindungi, tetapi payload belum dibatasi hanya pada ID dan judul.
