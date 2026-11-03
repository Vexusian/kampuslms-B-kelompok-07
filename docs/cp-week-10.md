Viewed SendGradeSubmittedNotification.php:1-73
Viewed NotificationController.php:1-60

Berikut adalah jawaban lengkap, sistematis, dan mudah dipahami untuk ketujuh pertanyaan tersebut, disesuaikan langsung dengan kode dan arsitektur proyek **Kampus LMS**:

---

### 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim? Peragakan.

#### **Apa yang terjadi:**
* **Bagi Pengguna (Dosen):** Aplikasi web tetap berjalan lancar tanpa *loading* lama dan tidak mengalami *crash*. Pesan *"Tugas berhasil dibuat"* tetap muncul di layar.
* **Bagi Sistem Antrean:** Event berhasil ditembakkan (*dispatched*) dan dicatat ke dalam antrean (tabel `jobs` di database) dengan status tertunda (*pending*, `attempts = 0`).
* **Bagi Penerima (Mahasiswa):** Pengiriman notifikasi **tertunda**. Notifikasi belum masuk ke tabel `notifications`, sehingga mahasiswa belum melihat notifikasi lonceng sampai worker dihidupkan kembali.

#### **Alur Peragaan (Demonstrasi):**
1. **Langkah 1 (Matikan Worker):**
   Pastikan background worker mati (tutup jendela terminal worker atau tekan `Ctrl + C` pada terminal `php artisan queue:work`).
2. **Langkah 2 (Dosen Membuat Tugas):**
   Buka browser, login sebagai Dosen, masuk ke salah satu mata kuliah, lalu buat tugas baru dan klik **Simpan**.
3. **Langkah 3 (Amati Kondisi Tertunda):**
   * Halaman dosen sukses tersimpan (*redirect* cepat).
   * Buka database (misal via phpMyAdmin/HeidiSQL), periksa tabel `jobs`: **Muncul 1 baris baru** (berisi job `SendAssignmentPublishedNotification`).
   * Periksa tabel `notifications`: **Masih kosong / belum ada baris baru bertambah**.
   * Buka akun Mahasiswa di browser: Ikon lonceng belum ada notifikasi tugas tersebut.
4. **Langkah 4 (Nyalakan Worker):**
   Buka terminal di folder proyek dan jalankan:
   ```bash
   php artisan queue:work
   ```
5. **Langkah 5 (Hasil Akhir):**
   * Terminal menampilkan log `Processing: App\Listeners\SendAssignmentPublishedNotification` lalu `Processed`.
   * Tabel `jobs` otomatis **kembali kosong (0 baris)**.
   * Tabel `notifications` kini **terisi baris notifikasi untuk seluruh mahasiswa kelas**.
   * Muat ulang halaman mahasiswa: notifikasi tugas baru langsung muncul di lonceng.

---

### 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana Anda akan mengetahuinya di server produksi?

#### **Kenapa tidak menghasilkan error:**
Karena dari sudut pandang *HTTP lifecycle* aplikasi web, proses pembuatan tugas **telah selesai dan berhasil 100%**. Tanggung jawab controller hanyalah menyimpan record tugas ke database lalu memasukkan instruksi pekerjaan ke antrean (`INSERT INTO jobs`). Operasi insert ke tabel `jobs` tersebut berhasil tanpa masalah.

Aplikasi web bekerja secara asinkron (*decoupled*): web server tidak menunggu worker mengeksekusi job. Worker yang mati berada di luar proses HTTP request web, sehingga web server tidak pernah mengalami *exception* (tetap HTTP 200/302).

#### **Bagaimana mengetahuinya di server produksi:**
1. **Process Manager / Supervisor (Supervisord / Systemd):**
   Di server Linux produksi, worker dikelola oleh *daemon process runner* seperti `Supervisor` atau `Systemd`. Jika proses worker mati mendadak, supervisor akan otomatis membangkitkannya kembali dan mencatat insiden ke berkas log sistem.
2. **Monitoring Antrean (Queue Metrics & Health Check):**
   * Menggunakan alat pemantau seperti **Laravel Pulse**, **Laravel Horizon** (jika Redis), atau **Laravel Telescope**.
   * Memasang alert jika nilai **Queue Wait Time** melonjak atau jumlah baris di tabel `jobs` menumpuk tanpa pernah berkurang (`SELECT COUNT(*) FROM jobs`).
3. **Pemeriksaan Tabel Job Gagal:**
   Menjalankan perintah audit bawaan Laravel:
   ```bash
   php artisan queue:failed
   ```
4. **Log Alerting & APM:**
   Menggunakan layanan seperti *Sentry*, *Bugsnag*, atau *Uptime Kuma* yang membaca berkas `storage/logs/laravel.log` dan mengirim pemberitahuan instan (via Telegram/Discord/Slack/Email) jika ada peringatan dari fungsi `failed()` di listener.

---

### 3. Apa beda `QUEUE_CONNECTION=sync` dan `database`? Kapan masing-masing dipakai?

| Poin Pembeda | `QUEUE_CONNECTION=sync` | `QUEUE_CONNECTION=database` |
| :--- | :--- | :--- |
| **Mekanisme Eksekusi** | **Sinkron (Langsung):** Pekerjaan dieksekusi saat itu juga di dalam proses request HTTP yang sama. | **Asinkron (Di Belakang Layar):** Pekerjaan disimpan ke tabel `jobs` dan dikerjakan oleh worker terpisah di background. |
| **Kebutuhan Worker** | **Tidak perlu** menjalankan `php artisan queue:work`. | **Wajib** ada worker yang aktif (`queue:work` atau `queue:listen`). |
| **Respon Browser** | **Lambat.** Browser dosen berputar (*loading*) menunggu seluruh proses pengiriman notifikasi/email selesai. | **Sangat Cepat.** Browser langsung menerima respon sukses seketika; notifikasi diproses di latar belakang. |
| **Ketahanan (*Fault Tolerance*)** | **Rentan.** Jika terjadi kegagalan jaringan saat mengirim notifikasi, halaman web dosen bisa ikut *crash/timeout*. | **Tahan Banting.** Job memiliki mekanisme coba ulang (*retry*) otomatis (`$tries = 3`, `backoff`) tanpa mengganggu dosen. |

#### **Kapan masing-masing dipakai:**
* **Gunakan `sync` saat:**
  * Pengujian otomatis (*Automated Testing* / PHPUnit) agar pengujian berjalan instan dalam satu rangkaian memori.
  * Saat *development* awal di laptop lokal jika Anda tidak ingin repot membuka terminal terpisah untuk menjalankan worker.
* **Gunakan `database` (atau `redis`) saat:**
  * Lingkungan produksi (*Production*) dan *Staging*.
  * Setiap proses yang melibatkan banyak penerima (seperti notifikasi massal ke puluhan mahasiswa di kelas), pengiriman email via SMTP, pembuatan laporan PDF/Excel, atau integrasi API pihak ketiga.

---

### 4. Tunjukkan Listener yang Anda tulis. Apa akibatnya kalau `ShouldQueue` dihapus?

#### **Listener pada Proyek:** [SendAssignmentPublishedNotification.php](file:///d:/Laragon/www/KampusLms/kampuslms-B-kelompok-07/app/Listeners/SendAssignmentPublishedNotification.php)

```php
namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAssignmentPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(AssignmentPublished $event): void
    {
        // Eager-load mahasiswa di kelas terkait
        $assignment = $event->assignment->loadMissing('course.students');
        $students = $assignment->course?->students;

        if (! $students || $students->isEmpty()) {
            return;
        }

        // Pengiriman notifikasi massal
        Notification::send($students, new NewAssignmentNotification($assignment));
    }
}
```

#### **Akibatnya jika `ShouldQueue` dihapus:**
1. **Listener Berubah Menjadi Sinkron (*Blocking*):** Method `handle()` akan langsung dieksekusi saat dosen menekan tombol simpan sebelum server membalas response ke browser.
2. **Penurunan Performa (*Bottleneck*):** Dosen harus menunggu proses query dan insert puluhan baris notifikasi selesai. Waktu respon web membengkak dari puluhan milidetik menjadi beberapa detik.
3. **Risiko HTTP Timeout:** Jika kelas berisi ratusan mahasiswa, eksekusi bisa melewati batas waktu server (biasanya `max_execution_time = 30s`) dan menimbulkan error *504 Gateway Timeout*.
4. **Kehilangan Fitur Ketahanan:** Properti `$tries`, `$backoff`, dan penanganan error `failed()` tidak akan berfungsi lagi.

---

### 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?

Sebagaimana diterapkan pada [GradeSubmittedNotification.php](file:///d:/Laragon/www/KampusLms/kampuslms-B-kelompok-07/app/Notifications/GradeSubmittedNotification.php#L15-L18), nilai angka mentah sengaja **tidak dimasukkan** ke kolom `data` notifikasi karena:

1. **Privasi dan Kerahasiaan Nilai (*Student Privacy*):**
   Nilai adalah data akademik privat. Notifikasi sering muncul di bilah pratinjau browser, pop-up layar (*push notification*), atau email yang berisiko terintip orang lain (*shoulder surfing*) di ruang publik.
2. **Mencegah Masalah Data Basi (*Stale Data / Single Source of Truth*):**
   Jika dosen merevisi nilai atau memperbarui umpan balik di kemudian hari, nilai yang tersimpan di payload notifikasi lama akan menjadi usang (*stale*) dan berbeda dengan nilai asli di database. Nilai yang valid harus selalu diambil langsung dari tabel `grades`.
3. **Peran Notifikasi Hanya Sebagai Pemberitahuan (*Trigger / Teaser*):**
   Notifikasi bertujuan mengajak mahasiswa masuk (*call to action*) ke halaman resmi detail tugas (`submissions.show`) yang dilindungi otorisasi Policy (`Gate::authorize`), bukan sebagai tempat menampilkan lembar evaluasi lengkap.

---

### 6. Tunjukkan apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca.

Pencegahannya terletak pada **pembatasan relasi (*scoped query*)** di [NotificationController.php](file:///d:/Laragon/www/KampusLms/kampuslms-B-kelompok-07/app/Http/Controllers/NotificationController.php#L31-L38):

```php
public function markAsRead(Request $request, string $id)
{
    // Scoped query: hanya mencari notifikasi yang berelasi dengan user yang sedang login
    $notification = $request->user()
        ->notifications()
        ->findOrFail($id);

    $notification->markAsRead();
    ...
}
```

#### **Mekanisme Keamanannya (Mencegah Serangan IDOR):**
* Kode di atas **tidak** menggunakan model statis `Notification::findOrFail($id)`, melainkan `$request->user()->notifications()->findOrFail($id)`.
* Di balik layar, Laravel menghasilkan query SQL dengan filter ganda:
  ```sql
  SELECT * FROM `notifications` 
  WHERE `id` = :id 
    AND `notifiable_id` = :auth_user_id 
    AND `notifiable_type` = 'App\\Models\\User' 
  LIMIT 1;
  ```
* **Hasilnya:** Jika Mahasiswa A mencoba menebak atau mengirimkan UUID notifikasi milik Mahasiswa B, database tidak akan menemukan baris tersebut karena `notifiable_id`-nya berbeda. Laravel otomatis melempar `ModelNotFoundException` yang menghasilkan respon **404 Not Found**. Mahasiswa lain mustahil memanipulasi notifikasi yang bukan haknya.

---

### 7. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

#### **Bagian yang Dibuat dengan Bantuan AI:**
Pembuatan logika pengiriman pada method `handle()` di [SendAssignmentPublishedNotification.php](file:///d:/Laragon/www/KampusLms/kampuslms-B-kelompok-07/app/Listeners/SendAssignmentPublishedNotification.php).

#### **Kode Awal yang Dihasilkan AI:**
```php
// Versi awal saran AI (memiliki kelemahan performa & cakupan):
public function handle(AssignmentPublished $event): void
{
    $students = User::where('role', 'mahasiswa')->get(); // Mengambil seluruh mahasiswa satu kampus!
    foreach ($students as $student) {
        $student->notify(new NewAssignmentNotification($event->assignment)); // Looping individual
    }
}
```

#### **Apa yang Diubah:**
1. **Mengubah Target Penerima:**
   Dari `User::where('role', 'mahasiswa')` diubah menjadi `$assignment->course->students`.
2. **Menambahkan Eager Loading:**
   Menambahkan `$assignment->loadMissing('course.students')`.
3. **Mengganti Looping Menjadi Pengiriman Massal:**
   Dari `foreach (...) { $student->notify(...) }` diubah menjadi `Notification::send($students, ...)`.
4. **Menambahkan Ketahanan Antrean:**
   Menambahkan `$tries = 3;`, `backoff()`, dan method `failed()`.

#### **Kenapa Diubah:**
* **Ketepatan Sasaran:** AI awalnya mengirim notifikasi ke *seluruh mahasiswa kampus*, padahal tugas hanya relevan bagi mahasiswa yang terdaftar di kelas (*enrolled*) tersebut.
* **Mencegah Masalah N+1 Query:** Eager loading `loadMissing()` memastikan relasi kelas dan siswa dimuat dalam satu query efisien.
* **Efisiensi Database:** `Notification::send()` memanfaatkan *mass-insert batch*, jauh lebih hemat sumber daya CPU dan koneksi database daripada memanggil `notify()` satu per satu di dalam perulangan *foreach*.
* **Keandalan:** Menambahkan *retry* dan *backoff* agar sistem tahan terhadap kendala *glitch* jaringan atau beban sementara basis data.