## Catatan Minggu 9

**Nama:** Zalfa Putri Sopyandi  
**NIM:** 10241076  

---

### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?
Berkas fisik tersimpan di disk privat (`storage/app/private`).  
* **File path:**  
  `storage\app\private\submissions\1\JrOMUPznrC1s0cLF1R0WLxUvLXlfKldYm2xZq4V6.pdf`
* *Contoh berkas materi kuliah: `storage\app\private\materials\1\CdnIzzlMxUbg3SUqrjRHlxvEQMwBIfQhqe8siVmc.pdf`*

---

### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.
* **Nilai `file_path` di database:**  
  `submissions/1/JrOMUPznrC1s0cLF1R0WLxUvLXlfKldYm2xZq4V6.pdf`
* **Lokasi fisik sebenarnya:**  
  `storage\app\private\submissions\1\JrOMUPznrC1s0cLF1R0WLxUvLXlfKldYm2xZq4V6.pdf`
* **Perbedaannya:**  
  Database hanya menyimpan **path relatif** terhadap disk penyimpanan. Laravel otomatis menggabungkan path root disk privat (`storage/app/private`) dengan path relatif tersebut saat berkas dipanggil melalui controller.

---

### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?
* **Hasil:** **Tidak berhasil (Error 404 Not Found).**
* **Kenapa:**  
  Folder `storage/app/private` tidak memiliki tautan publik (*symlink*) ke web server. Berkas berada di luar jangkauan browser dan **hanya bisa diakses melalui Controller** yang memvalidasi hak akses pengguna lewat Policy (`Gate::authorize`).

---

### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?

| Poin Pembeda | Disk `local` | Disk `public` |
| :--- | :--- | :--- |
| **Lokasi Folder** | `storage/app/private` | `storage/app/public` |
| **Akses Publik** | **Tertutup.** Tidak bisa diakses langsung via URL browser. | **Terbuka.** Bisa diakses via URL `http://domain/storage/...`. |
| **Mekanisme Akses** | Wajib lewat kode Controller + Policy Laravel. | Diambil langsung oleh web server tanpa lewat Laravel. |
| **Kegunaan** | Data rahasia/sensitif (tugas mahasiswa, materi kuliah kelas). | Aset umum (foto profil/avatar, logo aplikasi). |

---

### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda tidak boleh ada di sana?
* **Apa yang dibuat:**  
  Membuat pintasan direktori (*symbolic link*) dari folder `public/storage` yang mengarah ke `storage/app/public`.
* **Kenapa materi kuliah tidak boleh ada di sana:**  
  Karena web server akan langsung memberikan berkas tersebut ke siapa saja yang tahu URL-nya tanpa menjalankan pemeriksaan login (`auth`) maupun Policy (`MaterialPolicy`). Akibatnya, materi berhak cipta dan eksklusif kelas bisa bocor dan diunduh bebas oleh orang luar.
