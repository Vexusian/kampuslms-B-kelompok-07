# Dokumentasi REST API — KampusLMS (v1)

Dokumentasi resmi REST API KampusLMS berbasis Laravel 12 dan Laravel Sanctum sesuai kontrak spesifikasi proyek Bagian 5.

- **Base URL:** `http://localhost:8000/api/v1` (atau `http://kampuslms.test/api/v1`)
- **Format Data:** JSON (`Content-Type: application/json`, `Accept: application/json`)
- **Autentikasi:** HTTP Authorization Bearer Token (`Authorization: Bearer <token>`)
- **Rate Limiting:**
  - Login: 5 request / menit (`throttle:5,1`)
  - Endpoint umum: 60 request / menit (`throttle:60,1`)

---

## Akun Demo Pengujian

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@kampuslms.test` | `password` |
| Dosen | `dosen@kampuslms.test` | `password` |
| Mahasiswa | `mahasiswa@kampuslms.test` | `password` |

---

## Format Standar Response

### Sukses (Koleksi / Paginated)
```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost:8000/api/v1/courses?page=1",
    "last": "http://localhost:8000/api/v1/courses?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/courses?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 65
  }
}
```

### Sukses (Tunggal)
```json
{
  "data": { ... }
}
```

### Validasi Gagal — HTTP 422
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "score": [
      "The score field is required."
    ]
  }
}
```

### Tidak Berhak — HTTP 403
```json
{
  "message": "Anda tidak memiliki akses ke sumber daya ini."
}
```

### Belum Terautentikasi — HTTP 401
```json
{
  "message": "Unauthenticated."
}
```

---

## Ringkasan Endpoint

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| POST | `/auth/login` | Publik | Otentikasi & pembuatan Sanctum token |
| POST | `/auth/logout` | Auth | Revoke token aktif |
| GET | `/me` | Auth | Profil user saat ini |
| GET | `/courses` | Auth | Scoped: dosen (diajar), mahasiswa (diikuti), admin (semua) |
| GET | `/courses/{id}` | Auth + Scope | Detail mata kuliah + eager count |
| GET | `/courses/{id}/materials` | Auth + Scope | Daftar materi mata kuliah |
| GET | `/courses/{id}/assignments` | Auth + Scope | Daftar tugas mata kuliah (`?status=`, `?page=`) |
| POST | `/assignments` | Dosen | Membuat tugas baru (MK sendiri) |
| PUT/PATCH | `/assignments/{id}` | Dosen (Pemilik) | Mengubah data tugas |
| DELETE | `/assignments/{id}` | Dosen (Pemilik) | Menghapus tugas |
| GET | `/assignments/{id}/submissions` | Dosen (Pemilik) | Daftar submission mahasiswa |
| POST | `/assignments/{id}/submissions` | Mahasiswa (Terdaftar) | Pengumpulan tugas mahasiswa (multipart file/content) |
| PUT | `/submissions/{id}/grade` | Dosen (Pemilik) | Upsert penilaian tugas (201 jika baru, 200 jika update) |
| GET | `/notifications` | Auth | Daftar notifikasi user |
| POST | `/notifications/{id}/read` | Auth | Menandai notifikasi telah dibaca |

---

## Detail Endpoint

### 1. Login
- **Method:** `POST`
- **URI:** `/api/v1/auth/login`
- **Akses:** Publik (Rate limit: 5/menit)
- **Request Body:**
  ```json
  {
    "email": "dosen@kampuslms.test",
    "password": "password",
    "device_name": "curl-client"
  }
  ```
- **Contoh Request:**
  ```bash
  curl -X POST http://localhost:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"dosen@kampuslms.test","password":"password"}'
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "token": "1|qXyZ...",
    "user": {
      "id": 2,
      "name": "Dosen Demo",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198001012005011001",
      "created_at": "2026-09-12T00:00:00.000000Z"
    }
  }
  ```

### 2. Logout
- **Method:** `POST`
- **URI:** `/api/v1/auth/logout`
- **Akses:** Auth Bearer Token
- **Contoh Request:**
  ```bash
  curl -X POST http://localhost:8000/api/v1/auth/logout \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "message": "Berhasil logout."
  }
  ```

### 3. Profil Pengguna (`/me`)
- **Method:** `GET`
- **URI:** `/api/v1/me`
- **Akses:** Auth Bearer Token
- **Contoh Request:**
  ```bash
  curl http://localhost:8000/api/v1/me \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 2,
      "name": "Dosen Demo",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198001012005011001",
      "created_at": "2026-09-12T00:00:00.000000Z"
    }
  }
  ```

### 4. Daftar Mata Kuliah (`/courses`)
- **Method:** `GET`
- **URI:** `/api/v1/courses`
- **Akses:** Auth Bearer Token (Scoped per role)
- **Contoh Request:**
  ```bash
  curl http://localhost:8000/api/v1/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "code": "SI2514024",
        "name": "Pemrograman Web",
        "description": "Pengembangan web modern Laravel 12",
        "sks": 3,
        "status": "active",
        "lecturer": {
          "id": 2,
          "name": "Dosen Demo",
          "email": "dosen@kampuslms.test",
          "role": "dosen",
          "nim_nip": "198001012005011001",
          "created_at": "2026-09-12T00:00:00.000000Z"
        },
        "counts": {
          "materials": 3,
          "assignments": 3,
          "students": 15
        },
        "created_at": "2026-09-12T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

### 5. Detail Mata Kuliah (`/courses/{id}`)
- **Method:** `GET`
- **URI:** `/api/v1/courses/{id}`
- **Akses:** Auth + Scope (Hanya Dosen pengampu, Mahasiswa terdaftar, atau Admin)
- **Contoh Request:**
  ```bash
  curl http://localhost:8000/api/v1/courses/1 \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "code": "SI2514024",
      "name": "Pemrograman Web",
      "description": "Pengembangan web modern Laravel 12",
      "sks": 3,
      "status": "active",
      "lecturer": {
        "id": 2,
        "name": "Dosen Demo",
        "email": "dosen@kampuslms.test",
        "role": "dosen",
        "nim_nip": "198001012005011001",
        "created_at": "2026-09-12T00:00:00.000000Z"
      },
      "counts": {
        "materials": 3,
        "assignments": 3,
        "students": 15
      },
      "created_at": "2026-09-12T00:00:00.000000Z"
    }
  }
  ```

### 6. Daftar Materi Mata Kuliah (`/courses/{id}/materials`)
- **Method:** `GET`
- **URI:** `/api/v1/courses/{id}/materials`
- **Akses:** Auth + Scope
- **Contoh Request:**
  ```bash
  curl http://localhost:8000/api/v1/courses/1/materials \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "course_id": 1,
        "title": "Pengantar REST API",
        "content": "Pengenalan REST API dan arsitektur stateless",
        "created_at": "2026-09-12T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "total": 1
    }
  }
  ```

### 7. Daftar Tugas Mata Kuliah (`/courses/{id}/assignments`)
- **Method:** `GET`
- **URI:** `/api/v1/courses/{id}/assignments?status=published&page=1`
- **Akses:** Auth + Scope
- **Parameter Query:** `status` (opsional: draft, published), `page` (opsional)
- **Contoh Request:**
  ```bash
  curl "http://localhost:8000/api/v1/courses/1/assignments?status=published" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN>"
  ```

### 8. Buat Tugas Baru (`/assignments`)
- **Method:** `POST`
- **URI:** `/api/v1/assignments`
- **Akses:** Dosen (Pengampu mata kuliah yang dipilih)
- **Request Body:**
  ```json
  {
    "course_id": 1,
    "title": "Tugas 01 - API Sanctum",
    "instructions": "Implementasikan endpoint Sanctum dengan lengkap.",
    "due_at": "2026-10-15 23:59:59",
    "max_score": 100,
    "allow_late": true,
    "status": "published"
  }
  ```
- **Contoh Request:**
  ```bash
  curl -X POST http://localhost:8000/api/v1/assignments \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <TOKEN_DOSEN>" \
    -d '{
      "course_id": 1,
      "title": "Tugas 01 - API Sanctum",
      "instructions": "Implementasikan endpoint Sanctum dengan lengkap.",
      "due_at": "2026-10-15 23:59:59",
      "max_score": 100
    }'
  ```
- **Response Sukses (201 Created):**
  ```json
  {
    "data": {
      "id": 10,
      "course_id": 1,
      "created_by": 2,
      "title": "Tugas 01 - API Sanctum",
      "instructions": "Implementasikan endpoint Sanctum dengan lengkap.",
      "due_at": "2026-10-15T23:59:59.000000Z",
      "max_score": 100,
      "allow_late": true,
      "status": "published",
      "created_at": "2026-10-02T15:30:00.000000Z"
    }
  }
  ```

### 9. Ubah Tugas (`/assignments/{id}`)
- **Method:** `PUT` / `PATCH`
- **URI:** `/api/v1/assignments/{id}`
- **Akses:** Dosen (Pemilik tugas)
- **Response Sukses (200 OK):** Data tugas yang telah diperbarui dalam resource `AssignmentResource`.

### 10. Hapus Tugas (`/assignments/{id}`)
- **Method:** `DELETE`
- **URI:** `/api/v1/assignments/{id}`
- **Akses:** Dosen (Pemilik tugas)
- **Response Sukses (204 No Content):** Tanpa response body.

### 11. Daftar Submissions Tugas (`/assignments/{id}/submissions`)
- **Method:** `GET`
- **URI:** `/api/v1/assignments/{id}/submissions`
- **Akses:** Dosen (Pemilik tugas)
- **Contoh Request:**
  ```bash
  curl http://localhost:8000/api/v1/assignments/1/submissions \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN_DOSEN>"
  ```
- **Response Sukses (200 OK):** Koleksi paginasi submission mahasiswa beserta informasi nilai (`grade`).

### 12. Kumpulkan Tugas (`/assignments/{id}/submissions`)
- **Method:** `POST`
- **URI:** `/api/v1/assignments/{id}/submissions`
- **Akses:** Mahasiswa (Terdaftar di mata kuliah)
- **Content-Type:** `multipart/form-data`
- **Contoh Request:**
  ```bash
  curl -X POST http://localhost:8000/api/v1/assignments/1/submissions \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <TOKEN_MAHASISWA>" \
    -F "content=Berikut tautan repositori tugas saya: https://github.com/..." \
    -F "file=@/path/to/laporan.pdf"
  ```
- **Response Sukses (201 Created):**
  ```json
  {
    "data": {
      "id": 55,
      "assignment_id": 1,
      "user_id": 5,
      "content": "Berikut tautan repositori tugas saya...",
      "file_path": "submissions/randomname.pdf",
      "submitted_at": "2026-10-02T15:35:00.000000Z",
      "created_at": "2026-10-02T15:35:00.000000Z"
    }
  }
  ```

### 13. Beri / Perbarui Nilai Submission (`/submissions/{id}/grade`)
- **Method:** `PUT`
- **URI:** `/api/v1/submissions/{id}/grade`
- **Akses:** Dosen (Pemilik mata kuliah)
- **Request Body:**
  ```json
  {
    "score": 90,
    "feedback": "Pengerjaan rapi dan sesuai kontrak API."
  }
  ```
- **Karakteristik Upsert:**
  - Pembuatan pertama kali mengembalikan **201 Created**.
  - Pembaruan penilaian mengembalikan **200 OK**.
- **Contoh Request:**
  ```bash
  curl -X PUT http://localhost:8000/api/v1/submissions/1/grade \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <TOKEN_DOSEN>" \
    -d '{"score":90,"feedback":"Pengerjaan sangat baik."}'
  ```

### 14. Daftar Notifikasi Pengguna (`/notifications`)
- **Method:** `GET`
- **URI:** `/api/v1/notifications`
- **Akses:** Auth Bearer Token
- **Response Sukses (200 OK):** Koleksi notifikasi user dalam format paginasi.

### 15. Tandai Notifikasi Dibaca (`/notifications/{id}/read`)
- **Method:** `POST`
- **URI:** `/api/v1/notifications/{id}/read`
- **Akses:** Auth Bearer Token (Pemilik notifikasi)
- **Response Sukses (200 OK):**
  ```json
  {
    "message": "Notifikasi berhasil ditandai sebagai dibaca."
  }
  ```
