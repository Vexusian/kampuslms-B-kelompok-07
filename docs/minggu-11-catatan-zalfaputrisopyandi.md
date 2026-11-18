## Catatan Minggu 11

**Nama:** Zalfa Putri Sopyandi  
**NIM:** 10241076  

---

## READ 

### 1. Urutkan dari yang terburuk.

Urutan halaman dari performa **terburuk ke terbaik** berdasarkan data awal (*baseline*) sebelum optimisasi pada `docs/performa.md`:

| No | Halaman | Jumlah Query | Waktu (ms) | Memori |
|:---:|---|:---:|:---:|:---:|
| 1 | **Dashboard Dosen** | **16** | 1006 | 24 MB |
| 2 | **Dashboard Mahasiswa** | **16** | 984 | 24 MB |
| 3 | **Daftar Tugas (Dosen)** | **13** | 957 | 24 MB |
| 4 | **Rekap Nilai (Mahasiswa)** | **12** | 1015 | 24 MB |
| 5 | **Daftar Pengumpulan (Dosen)** | **12** | 959 | 25 MB |
| 6 | **Daftar Tugas (Mahasiswa)** | **11** | 934 | 24 MB |
| 7 | **Rekap Nilai (Dosen)** | **11** | 948 | 24 MB |
| 8 | **Detail Mata Kuliah (Dosen)** | **10** | **1017** | 25 MB |
| 9 | **Detail Mata Kuliah (Mahasiswa)** | **10** | 950 | 24 MB |
| 10 | **Daftar Mata Kuliah (Mahasiswa)** | **7** | 1008 | 25 MB |
| 11 | **Daftar Mata Kuliah (Dosen)** | **7** | 936 | 25 MB |

* **Query terbanyak (terburuk):** Dashboard Dosen & Mahasiswa (**16 query**), disusul Daftar Tugas Dosen (**13 query**).
* **Waktu terlama (terburuk):** Detail Mata Kuliah Dosen (**1017 ms**) dan Rekap Nilai Mahasiswa (**1015 ms**).

---

### 2. Untuk halaman terburuk, buka Debugbar tab Queries. Temukan query yang berulang dengan pola sama. Itu N+1 Anda.

Dari halaman terburuk, ditemukan dua pola masalah pada tab **Queries** Debugbar:

1. **N+1 Relasi pada Daftar Tugas Dosen (13 query):**
   Di dalam perulangan daftar tugas (`@foreach`), terjadi query berulang untuk mengambil data mata kuliah dan menghitung jumlah submission untuk setiap baris tugas:
   ```sql
   select * from `courses` where `id` = ? limit 1;
   select count(*) from `submissions` where `assignment_id` = ?;
   ```
   * **Solusi:** Menerapkan eager loading `with('course')` dan `withCount('submissions')` di controller, sehingga query terpangkas dari **13 menjadi 8 query**.

2. **Agregasi Berulang pada Dashboard (16 query):**
   Setiap kartu metrik statistik mengeksekusi query `COUNT(*)` dan `where exists` terpisah secara berulang setiap kali halaman dimuat:
   ```sql
   select count(*) from `courses` where `lecturer_id` = ?;
   select count(*) from `materials` where exists (...);
   select count(*) from `assignments` where exists (...);
   ```
   * **Solusi:** Menggunakan `Cache::remember()` selama 30 menit, sehingga query terpangkas dari **16 menjadi 11 query** dan waktu respon turun dari **984 ms ke 308 ms**.

---

### 3. Salin satu query terlambat, jalankan `EXPLAIN` di database. Catat kolom `type` dan `rows`.

Query pencarian pengguna berdasarkan filter `role` (misal untuk dropdown dosen/mahasiswa):
```sql
EXPLAIN SELECT * FROM users WHERE role = 'dosen';
```

* **Sebelum Optimisasi (Tanpa Index):**
  * **`type`:** **`ALL`** (*Full Table Scan* — database memindai seluruh tabel tanpa indeks).
  * **`rows`:** **`50`** (seluruh baris pada tabel dibaca).

* **Sesudah Optimisasi (Index `users_role_index` diterapkan):**
  * **`type`:** **`ref`** (*Index Scan* — database langsung mencari lewat B-Tree index).
  * **`rows`:** **`3`** (hanya membaca 3 baris data yang memiliki role `'dosen'`).

