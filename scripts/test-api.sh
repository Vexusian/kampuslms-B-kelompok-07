#!/usr/bin/env bash
# ==============================================================================
# Skrip Pengujian Otorisasi REST API KampusLMS (v1)
# Menguji 4 Kondisi Otorisasi:
# 1. Tanpa token sama sekali                -> Harus 401 Unauthorized
# 2. Token mahasiswa mengakses endpoint dosen -> Harus 403 Forbidden
# 3. Token dosen A mengakses data milik dosen B -> Harus 403 Forbidden
# 4. Token yang benar dengan hak yang benar    -> Harus 200 OK / 201 Created
# ==============================================================================

BASE_URL="${API_URL:-http://localhost:8000/api/v1}"

echo "========================================================"
echo " Pengujian REST API KampusLMS: $BASE_URL"
echo "========================================================"

# --- Langkah 0: Otentikasi dan Pengambilan Token ---
echo -e "\n[0] Mengambil token akun demo..."

# Login Dosen A (dosen@kampuslms.test)
TOKEN_DOSEN_A=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"dosen@kampuslms.test","password":"password"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# Login Mahasiswa (mahasiswa@kampuslms.test)
TOKEN_MHS=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@kampuslms.test","password":"password"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)

echo "Token Dosen A : ${TOKEN_DOSEN_A:0:15}..."
echo "Token Mahasiswa: ${TOKEN_MHS:0:15}..."

# --- KONDISI 1: Tanpa Token Sama Sekali (Harus 401) ---
echo -e "\n--------------------------------------------------------"
echo "[Kondisi 1] Tanpa Token Sama Sekali -> Ekspektasi HTTP 401"
echo "--------------------------------------------------------"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/me" \
  -H "Accept: application/json")
echo "GET /me (Tanpa Token)                      -> Status: $HTTP_CODE (Harus 401)"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/courses" \
  -H "Accept: application/json")
echo "GET /courses (Tanpa Token)                 -> Status: $HTTP_CODE (Harus 401)"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/assignments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test"}')
echo "POST /assignments (Tanpa Token)            -> Status: $HTTP_CODE (Harus 401)"

# --- KONDISI 2: Token Mahasiswa Mengakses Endpoint Dosen (Harus 403) ---
echo -e "\n--------------------------------------------------------"
echo "[Kondisi 2] Token Mahasiswa Akses Endpoint Dosen -> Ekspektasi HTTP 403"
echo "--------------------------------------------------------"

# Mahasiswa mencoba membuat tugas baru
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/assignments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS" \
  -d '{"course_id":1,"title":"Tugas Ilegal","instructions":"Test","due_at":"2026-12-31 23:59:59"}')
echo "POST /assignments (Oleh Mahasiswa)         -> Status: $HTTP_CODE (Harus 403)"

# Mahasiswa mencoba melihat submissions pengumpulan mahasiswa lain
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/assignments/1/submissions" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS")
echo "GET /assignments/1/submissions (Mahasiswa) -> Status: $HTTP_CODE (Harus 403)"

# Mahasiswa mencoba memberi nilai tugas
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$BASE_URL/submissions/1/grade" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS" \
  -d '{"score":100,"feedback":"Nilai sendiri"}')
echo "PUT /submissions/1/grade (Mahasiswa)       -> Status: $HTTP_CODE (Harus 403)"

# --- KONDISI 3: Token Dosen A Mengakses Data Milik Dosen B (Harus 403) ---
echo -e "\n--------------------------------------------------------"
echo "[Kondisi 3] Dosen A Mengakses Resource Dosen B -> Ekspektasi HTTP 403"
echo "--------------------------------------------------------"

# Dosen A mencoba membuat tugas di mata kuliah dosen lain (misal Course ID 5)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/assignments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -d '{"course_id":5,"title":"Tugas Pembajakan","instructions":"Mata kuliah dosen lain","due_at":"2026-12-31 23:59:59"}')
echo "POST /assignments di Course Dosen Lain     -> Status: $HTTP_CODE (Harus 403)"

# Dosen A mencoba menilai submission di mata kuliah dosen lain (misal Submission ID 99)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$BASE_URL/submissions/99/grade" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -d '{"score":80,"feedback":"Dinilai dosen lain"}')
echo "PUT /submissions/99/grade (Dosen Beda)      -> Status: $HTTP_CODE (Harus 403 / 404)"

# --- KONDISI 4: Token Benar dengan Hak yang Benar (Harus 200/201) ---
echo -e "\n--------------------------------------------------------"
echo "[Kondisi 4] Token Benar dengan Hak yang Sesuai -> Ekspektasi HTTP 200 / 201"
echo "--------------------------------------------------------"

# Dosen A mengakses profil sendiri
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A")
echo "GET /me (Dosen A)                          -> Status: $HTTP_CODE (Harus 200)"

# Mahasiswa mengakses daftar mata kuliah yang diikutinya
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/courses" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_MHS")
echo "GET /courses (Mahasiswa)                   -> Status: $HTTP_CODE (Harus 200)"

# Dosen A membuat tugas di mata kuliah miliknya (Course ID 1)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/assignments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -d '{"course_id":1,"title":"Tugas Praktikum REST API","instructions":"Implementasi lengkap API Resource","due_at":"2026-11-01 23:59:59","max_score":100}')
echo "POST /assignments (Dosen Pemilik)          -> Status: $HTTP_CODE (Harus 201)"

# Dosen A memberi nilai submission miliknya (Submission ID 1)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$BASE_URL/submissions/1/grade" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_DOSEN_A" \
  -d '{"score":92,"feedback":"Validasi dan resource sangat baik"}')
echo "PUT /submissions/1/grade (Dosen Pemilik)   -> Status: $HTTP_CODE (Harus 200/201)"

echo -e "\n========================================================"
echo " Pengujian Otorisasi Selesai."
echo "========================================================"
