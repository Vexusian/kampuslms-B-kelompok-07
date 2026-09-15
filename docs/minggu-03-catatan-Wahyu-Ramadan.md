## Wahyu Ramadan
## 10241074
--- 
### Catatan minggu 3 

1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.</br>


<img src = "images/image_3-ERD.jpeg">

2. Untuk setiap foreign key, tentukan perilaku onDelete-nya dan tuliskan alasannya.</br>


| No | Entitas (Induk) | Entitas (Anak) | Perilaku OnDelete | Alasan |
|----|-----------------|----------------|------------------|--------|
| 1 | User  | Notifications | cascade On Delete | Jika user dihapus maka user tidak perlu lagi mendapatkan notifikasi |
| 2 | User  | Courses  | restrict On Delete | Selama dosen memiliki mata kuliah diampu maka dosen tidak boleh dihapus |
| 3 | User  | Grades | restrict On Delete | Akun user (dosen) harus tetap ada agar siapa yang menilai bisa tetap terlihat, selagi atribut graded by masih ada karena atribut tersebut mereferensi ke user |
| 4 | Users  | Course User | cascade On Delete| Jika user mahasiswa dihapus maka, Course User tidak masalah terhapus |
| 5 | Users  | Material | cascade On Delete | Jika user dosen dihapus maka materi ikut terhapus |
| 6 | Users  | Submissions | cascade On Delete | Jika user mahasiswa dihapus maka, submissions ikut terhapus |
| 7 | Users  | Assignment | cascade On Delete | Jika user mahasiswa dihapus maka, assignment ikut terhapus |
| 8 | Courses | Assignment | cascade On Delete | Jika mata kuliah dihapus maka Tugas yang diberikan juga ikut terhapus |
| 9 | Courses  | Corse_user | cascade On Delete | Jika mata kuliah dihapus maka course_user akan ikut terhapus, karena mata kuliah itu tidak bisa lagi diambil user |
| 10 | Courses | Material | cascade On Delete | Jika mata kuliah dihapus maka material juga ikut dihapus karena sudah tidak dipakai |
| 11 | Assignment | Submission | cascade On Delete | Jika assignment dihapus maka submission akan ikut dihapus, karena dari pengumpulan tugas akan tidak berguna jika penugasannya tidak ada |
| 12 | Submission | Grades | cascade On Delete | Jika submission dihapus maka grades sudah tidak perlu lagi dan perlu dihapus|
</br>

### 3. kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?


jika seorang dosen dihapus maka berdasarkan rancangan database tidak mengizinkan untuk dihapusnya user dosen jika memiliki keterkaitan dengan tabel courses karena memiliki perilaku `On-Delete` yakni `restrict OnDelete`, Namun jika dengan terpaksa dilakukan force delete, maka courses sebagai entitas anak akan menjadi data yang tidak memiliki data induk.


### 4. kenapa grades.submission_id bersifat unique, bukan sekadar index biasa?

`grades.submission_id` harus dibuat unik agar satu submission yang dikumpulkan hanya boleh mendapatkan 1 nilai, jika tidak dibuat demikian maka memungkinkan untuk satu submission bisa dimasukan beberapa nilai maka diharuskan agar memiliki sifat unik. 