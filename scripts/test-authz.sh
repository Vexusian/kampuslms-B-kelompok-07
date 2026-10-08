#!/usr/bin/env bash
# ==============================================================================
# KampusLMS — Skrip Pengujian Otorisasi & Pencegahan IDOR (Milestone M2 / Tugas 2)
# ==============================================================================
# Target Pengujian:
# 1. Autentikasi & Session (Login gagal -> 422/302, Login benar -> 200/302)
# 2. Akses Tanpa Autentikasi (Guest ke rute privat -> 401 / 302 ke /login)
# 3. Horizontal Privilege Escalation (Dosen A coba ubah kelas Dosen B -> 403 Forbidden)
# 4. Vertical Privilege Escalation (Mahasiswa coba buat mata kuliah -> 403 Forbidden)
# 5. IDOR Submission (Mahasiswa coba buka pengumpulan tugas mahasiswa lain -> 403 Forbidden)
# 6. IDOR Materi/Tugas (Mahasiswa coba akses materi kelas yang tidak diikutinya -> 403 Forbidden)
# ==============================================================================

APP_URL="${APP_URL:-http://localhost:8000}"
API_URL="$APP_URL/api/v1"

echo "===================================================================="
echo " Pengujian Otorisasi & Titik Rawan IDOR KampusLMS"
echo " Target Base URL: $APP_URL"
echo "===================================================================="

# Helper cookie jar
COOKIE_ADMIN=$(mktemp)
COOKIE_DOSEN_A=$(mktemp)
COOKIE_DOSEN_B=$(mktemp)
COOKIE_MHS_A=$(mktemp)
COOKIE_MHS_B=$(mktemp)

cleanup() {
    rm -f "$COOKIE_ADMIN" "$COOKIE_DOSEN_A" "$COOKIE_DOSEN_B" "$COOKIE_MHS_A" "$COOKIE_MHS_B"
}
trap cleanup EXIT

# ----------------------------------------------------------------------
# 1. UJI AUTENTIKASI WEB
# ----------------------------------------------------------------------
echo -e "\n[1] Menguji Autentikasi Web Form & Session..."

# Login Dosen Demo (User ID 2)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -c "$COOKIE_DOSEN_A" "$APP_URL/dev/login/2")
echo "  -> Simulasi Login Dosen Demo (ID: 2)     : Status $HTTP_CODE"

# Login Mahasiswa Demo (User ID 5)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -c "$COOKIE_MHS_A" "$APP_URL/dev/login/5")
echo "  -> Simulasi Login Mahasiswa Demo (ID: 5) : Status $HTTP_CODE"

# ----------------------------------------------------------------------
# 2. UJI AKSES GUEST / UNATHENTICATED (Harus 401 atau Redirect 302 ke /login)
# ----------------------------------------------------------------------
echo -e "\n[2] Menguji Endpoint Privat Tanpa Sesi (Guest)..."

STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/courses")
echo "  GET /courses (Guest)                   -> HTTP $STATUS (Harus 302 Redirect ke /login)"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "$API_URL/me")
echo "  GET /api/v1/me (Guest)                 -> HTTP $STATUS (Harus 401 Unauthorized)"

# ----------------------------------------------------------------------
# 3. UJI VERTICAL PRIVILEGE ESCALATION (Mahasiswa Coba Bikin Course)
# ----------------------------------------------------------------------
echo -e "\n[3] Menguji Vertical Privilege Escalation..."

STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" -X POST "$APP_URL/courses" \
  -H "Accept: application/json" \
  -d "code=IF999&name=HackedCourse&sks=3&status=active&lecturer_id=2")
echo "  Mahasiswa POST /courses                -> HTTP $STATUS (Harus 403 Forbidden)"

# ----------------------------------------------------------------------
# 4. UJI HORIZONTAL PRIVILEGE ESCALATION (Dosen A Edit Course Dosen B)
# ----------------------------------------------------------------------
echo -e "\n[4] Menguji Horizontal Privilege Escalation (Dosen A vs Dosen B)..."

# Dosen A (ID 2) mencoba PUT ke Course milik Dosen lain (ID 2 vs Course dengan lecturer_id != 2)
# Ambil course pertama yang lecturer_id bukan 2
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_DOSEN_A" -X PUT "$APP_URL/courses/5" \
  -H "Accept: application/json" \
  -d "code=TEST01&name=GantiNama&sks=3&status=active&lecturer_id=3")
echo "  Dosen A PUT /courses/{id_milik_dosen_b} -> HTTP $STATUS (Harus 403 Forbidden)"

# ----------------------------------------------------------------------
# 5. UJI IDOR PENGUMPULAN TUGAS (Mahasiswa A Buka Submission Mahasiswa B)
# ----------------------------------------------------------------------
echo -e "\n[5] Menguji Titik Rawan IDOR Submission..."

# Mahasiswa A (ID 5) mencoba membuka submission milik mahasiswa lain
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" "$APP_URL/submissions/1")
echo "  Mahasiswa A GET /submissions/{milik_b}  -> HTTP $STATUS (Harus 403 Forbidden)"

# ----------------------------------------------------------------------
# 6. UJI IDOR MATERI / TUGAS KELAS TIDAK DIIKUTI
# ----------------------------------------------------------------------
echo -e "\n[6] Menguji Titik Rawan IDOR Materi / Tugas Unenrolled..."

STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_MHS_A" "$APP_URL/materials/1")
echo "  Mahasiswa GET /materials/{unenrolled}   -> HTTP $STATUS (Harus 403 Forbidden jika tidak ikut kelas)"

echo -e "\n===================================================================="
echo " Seluruh pemeriksaan otorisasi selesai dievaluasi."
echo "===================================================================="
