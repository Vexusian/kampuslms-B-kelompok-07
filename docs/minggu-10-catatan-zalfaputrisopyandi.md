## Catatan Minggu 10

**Nama:** Zalfa Putri Sopyandi  
**NIM:** 10241076  

---

### 1. Matikan worker. Buat satu tugas baru. Buka tabel `jobs` — apa isinya?
- Kondisi: Worker dalam keadaan mati (tidak ada `php artisan queue:work` yang berjalan). Dosen membuat tugas baru melalui `AssignmentController.php`, yang menembakkan event `AssignmentPublished`.
- Isi tabel jobs: Muncul 1 baris record baru di tabel jobs. Hal ini terjadi karena listener `SendAssignmentPublishedNotification` mengimplementasikan `ShouldQueue` dan koneksi antrean di `.env` adalah `QUEUE_CONNECTION=database`. Karena worker mati, tugas belum dikerjakan dan "tertahan" di antrean basis data.
- Rincian kolom pada baris tersebut:
    - `id`: Nomor urut job (misal: `1`).
    - `queue`: Berisi `'default'` (nama saluran antrean).
    - `payload`: Teks JSON panjang yang menyimpan instruksi pekerjaan yang akan dieksekusi.
    - `attempts`: Berisi nilai `0` (karena worker mati, belum pernah dicoba dieksekusi sama sekali).
    - `reserved_at`: Bernilai `NULL` (belum ada worker yang mengunci/mengambil tugas ini).
    - `available_at` & `created_at`: Berisi timestamp UNIX saat tugas baru tersebut dibuat.
---

### 2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?
Bisa, di dalam format JSON kolom `payload`, kita dapat mengenali keduanya:  
1. Nama Listener:
    - Tertera langsung pada atribut JSON `"displayName"`:
        ```json
        "displayName":
        "App\\Listeners\\SendAssignmentPublishedNotification"
        ```
    - Juga tercatat di dalam serialized PHP string pada properti `"command"` di dalam data payload.
    
2. ID Tugas (Assignment ID):
    - Di dalam string serialized `"command"`, event `AssignmentPublished` membawa model `Assignment`. Karena menggunakan trait `SerializesModels`, Laravel menyimpannya sebagai `ModelIdentifier`:
        ```text
        ...s:5:"class";s:20:"App\Models\Assignment";s:2:"id";i:1;...
        ```
    - Nilai `i:1` (atau angka ID tugas yang baru dibuat) menunjukkan ID dari tugas tersebut.

---

### 3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.  
**Keluaran di Terminal:** Saat worker dinyalakan, terminal akan menampilkan log pemrosesan secara bertahap:
```cmd
Processing jobs from the [default] queue.  

  2026-10-27 09:25:02 App\Listeners\SendAssignmentPublishedNotification  RUNNING
  2026-10-27 09:25:02 App\Listeners\SendAssignmentPublishedNotification  263.66ms DONE
  2026-10-27 09:25:02 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:02 App\Notifications\NewAssignmentNotification 
 44.16ms DONE
  2026-10-27 09:25:02 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:02 App\Notifications\NewAssignmentNotification 
 27.14ms DONE
  2026-10-27 09:25:02 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 20.65ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 22.79ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 21.78ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 22.23ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 23.61ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 21.61ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 20.30ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 23.15ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 21.22ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 23.50ms DONE
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
 RUNNING
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification 
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification  RUNNING       
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification  31.79ms DONE  
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification  RUNNING       
  2026-10-27 09:25:03 App\Notifications\NewAssignmentNotification  25.04ms DONE
```
*(Jika notifikasi individual mahasiswa juga diantrekan via `NewAssignmentNotification`, worker akan lanjut memproses `SendQueuedNotifications` untuk tiap mahasiswa).*

Pemeriksaan tabel `jobs` lagi:
- Tabel jobs sekarang menjadi **KOSONG (0 baris / Empty)**.
- **Mengapa kosong?** Karena Laravel secara otomatis menghapus `job` dari tabel jobs segera setelah proses dinyatakan berhasil (`Processed`), sehingga antrean tidak akan dieksekusi dua kali.
---

### 4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?  
Jumlah baris yang muncul adalah sebanyak mahasiswa yang terdaftar (enrolled) pada mata kuliah yang bersangkutan.  
Cocok dan tepat sasaran dengan jumlah mahasiswanya, namun hanya untuk mahasiswa di kelas tersebut, bukan seluruh mahasiswa yang ada di sistem kampus.

Sistem hanya mengambil relasi mahasiswa yang terdaftar di kelas (`course.students`). Jika di kelas tersebut ada 15 mahasiswa (seperti pengaturan seeder kelas), maka tepat 15 baris notifikasi baru yang akan masuk ke tabel `notifications`. Mahasiswa dari kelas lain tidak akan mendapatkan notifikasi tersebut.

---

### 5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?  
Data JSON pada `NewAssignmentNotification.php` sudah tepat dan menerapkan best practice (lightweight payload) karena hanya menyimpan metadata ringkas untuk navigasi dan tampilan lonceng (ID, judul tugas, nama matakuliah, deadline, dan teks ringkas).  
Data yang TIDAK Boleh Tersimpan (Kesalahan Umum yang Dihindari):
- Deskripsi/Instruksi Panjang: Soal lengkap, file, atau HTML (notifikasi hanya untuk pemberitahuan ringkas).
- Seluruh Objek Model Eloquent: Menyimpan seluruh atribut model yang membuat ukuran database membengkak.
- Data Sensitif Pengguna: Password, token autentikasi, atau data pribadi.
- Nilai Mentah / Umpan Balik Rahasia: Nilai angka mentah demi menjaga privasi mahasiswa (seperti pada `GradeSubmittedNotification.php`).