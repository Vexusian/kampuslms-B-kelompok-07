# Catatan Minggu 9

## Nama : Tresia Uyang 
## NIM : 10241072

### READ — Telusuri satu berkas 


1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?
2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.
3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?
4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?
5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda **tidak boleh** ada di sana?

### BREAK — Delapan kerusakan 

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Pindahkan berkas submission ke `storage/app/public`, akses URL-nya tanpa login | **Tugas seluruh kelas terbuka untuk publik** |
| 2 | Hapus `Gate::authorize()` dari endpoint download, akses submission orang lain | IDOR versi berkas |
| 3 | Simpan berkas dengan `storeAs()` memakai nama asli, lalu dua akun mahasiswa berbeda mengunggah `tugas.pdf` dengan isi berbeda ke tugas yang sama | **Berkas mahasiswa pertama tertimpa** |
| 4 | Unggah berkas PHP yang diganti namanya jadi `.pdf` dengan validasi `extensions`, lalu dengan `mimes` | Beda memeriksa ekstensi vs isi |
| 5 | Ubah `max:5120` menjadi `max:5`, unggah berkas 1 MB | Satuannya kilobyte |
| 6 | Set `upload_max_filesize=1M` di php.ini, unggah berkas 3 MB dengan validasi `max:5120` | **Batas server mengalahkan validasi** |
| 7 | Hapus `updateOrCreate`, ganti `create`, kumpulkan tugas dua kali | Unique constraint minggu 3 menyelamatkan Anda |
| 8 | Hapus satu submission dari database lewat SQL langsung, cek folder storage | Berkas yatim tertinggal |

Nomor 1 wajib benar-benar dicoba. Membuka tugas teman sekelas dari jendela penyamaran, tanpa login, adalah pengalaman yang menjelaskan seluruh minggu ini dalam sepuluh detik.

### FIX — Repo cacat 
Branch `w09` pada repo `kampuslms-broken` berisi **7 masalah**: submission disimpan di disk publik, endpoint download tanpa `Gate::authorize()`, `storeAs()` memakai nama asli, validasi hanya memeriksa ekstensi, `max` salah satuan, tidak ada penghapusan berkas fisik, dan komponen Livewire yang menyembunyikan tombol tanpa otorisasi di server.

Perbaiki, kirim PR. Sertakan bukti: URL yang tadinya bisa dibuka tanpa login, dan hasilnya setelah perbaikan.

### BUILD — Alur berkas KampusLMS

1. **Unggah materi** oleh dosen: PDF/PPTX/DOCX, maksimal 20 MB, disk privat, atau alternatif berupa tautan eksternal.
2. **Unduh materi** oleh mahasiswa yang terdaftar di mata kuliah tersebut — lewat controller + Policy.
3. **Pengumpulan tugas** oleh mahasiswa: `updateOrCreate`, `is_late` dihitung server, tolak jika `allow_late` bernilai false dan sudah lewat deadline.
4. **Unduh submission**: hanya pemiliknya, dosen pengampu, dan admin.
5. **Daftar pengumpulan** untuk dosen: siapa sudah/belum mengumpulkan, ditandai terlambat.
6. **Penghapusan berkas fisik** saat record dihapus, termasuk penanganan kasus cascade.
7. Tampilkan `original_name` dan ukuran berkas dalam format yang terbaca manusia.

---

## 9.4 Checkpoint Minggu 9

- [ ] Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya.   
> submission tidak boleh disimpan di `stroage/app/public` karena user yang tidak memiliki akses terhadap file tersebut akan bisa meilihat file submission tanpa harus login 

- [ ] Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?
- [ ] Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?
- [ ] `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?
- [ ] Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.
- [ ] Kenapa `is_late` dihitung di server, bukan dikirim dari browser?
