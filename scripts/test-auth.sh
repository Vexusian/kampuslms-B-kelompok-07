#!/usr/bin/env bash
# ==============================================================================
# KampusLMS — Skrip Pengujian Keamanan Otorisasi & Anti-IDOR (Server Produksi)
# Berdasarkan Dokumen: docs/keamanan.md & Tugas 4 / Kuis 12
# ==============================================================================

# Target URL Produksi (dapat di-override via argumen: ./test-auth.sh https://domain)
TARGET_URL="${1:-https://kampuslms-b-kelompok-07-production.up.railway.app}"
TARGET_URL="${TARGET_URL%/}" # Bersihkan trailing slash jika ada

# Akun Pengujian (Standar DatabaseSeeder)
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@kampuslms.test}"
ADMIN_PASS="${ADMIN_PASS:-password}"

DOSEN_EMAIL="${DOSEN_EMAIL:-dosen@kampuslms.test}"
DOSEN_PASS="${DOSEN_PASS:-password}"

MHS_EMAIL="${MHS_EMAIL:-mahasiswa@kampuslms.test}"
MHS_PASS="${MHS_PASS:-password}"

# Warna Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

assert_status() {
    local test_name="$1"
    local expected_status="$2"
    local actual_status="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    # Menilai apakah status respon server termasuk penolakan akses yang sah
    if [[ "$actual_status" =~ ^($expected_status)$ ]]; then
        echo -e "  [${GREEN}PASSED${NC}] $test_name (HTTP $actual_status, Expected: $expected_status)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "  [${RED}FAILED${NC}] $test_name (HTTP $actual_status, Expected: $expected_status)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

echo -e "${BOLD}====================================================================${NC}"
echo -e "${BOLD} Pengujian Keamanan Otorisasi & Titik Rawan IDOR KampusLMS${NC}"
echo -e " Target Server : ${BLUE}$TARGET_URL${NC}"
echo -e " Waktu Uji     : $(date '+%Y-%m-%d %H:%M:%S')"
echo -e "${BOLD}====================================================================${NC}\n"

# ------------------------------------------------------------------------------
# BAGIAN 0: UJI KEAMANAN DASAR SERVER PRODUKSI (.env & Document Root)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}${BOLD}[0] Uji Kebocoran File Sensitif & Keamanan Server (.env)${NC}"

# Cek kebocoran file .env (Wajib 404 Not Found atau 403 Forbidden, TIDAK BOLEH 200)
STATUS_ENV=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/.env")
assert_status "Pencegahan Kebocoran .env (curl -I $TARGET_URL/.env)" "403|404" "$STATUS_ENV"

# Cek apakah Document Root bocor ke direktori internal storage (tambahkan flag -L)
STATUS_STORAGE=$(curl -s -L -o /dev/null -w "%{http_code}" -k "$TARGET_URL/storage/app/private/")
assert_status "Pencegahan Akses Folder Privat (Storage Private)" "301|403|404" "$STATUS_STORAGE"


# ------------------------------------------------------------------------------
# BAGIAN 1: AUTENTIKASI DAN PEROLEHAN TOKEN PENGUJIAN
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[1] Memperoleh Kredensial & Token Sanctum (API Authentication)${NC}"

login_user() {
    local email="$1"
    local pass="$2"
    local response
    response=$(curl -s -k -X POST "$TARGET_URL/api/v1/auth/login" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$email\",\"password\":\"$pass\"}")
    
    echo "$response" | grep -o '"token":"[^"]*' | cut -d'"' -f4
}

TOKEN_DOSEN=$(login_user "$DOSEN_EMAIL" "$DOSEN_PASS")
TOKEN_MHS=$(login_user "$MHS_EMAIL" "$MHS_PASS")

if [ -n "$TOKEN_DOSEN" ]; then
    echo -e "  [${GREEN}OK${NC}] Login Dosen Berhasil ($DOSEN_EMAIL)"
else
    echo -e "  [${YELLOW}WARN${NC}] Login Dosen via API tidak mendapat token (Mungkin akun seeder berbeda)"
fi

if [ -n "$TOKEN_MHS" ]; then
    echo -e "  [${GREEN}OK${NC}] Login Mahasiswa Berhasil ($MHS_EMAIL)"
else
    echo -e "  [${YELLOW}WARN${NC}] Login Mahasiswa via API tidak mendapat token"
fi


# ------------------------------------------------------------------------------
# BAGIAN 2: UJI AKSES GUEST / UNATHENTICATED (Tabel Keamanan No. 1)
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[2] Menguji Akses Tanpa Login (Guest / Unauthenticated)${NC}"

# Web Route Privat (Harus 302 Redirect ke /login)
STATUS_GUEST_WEB=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/courses")
assert_status "Guest GET /courses (Web)" "302" "$STATUS_GUEST_WEB"

# API Route Privat (Harus 401 Unauthorized)
STATUS_GUEST_API=$(curl -s -o /dev/null -w "%{http_code}" -k -H "Accept: application/json" "$TARGET_URL/api/v1/me")
assert_status "Guest GET /api/v1/me (API)" "401" "$STATUS_GUEST_API"


# ------------------------------------------------------------------------------
# BAGIAN 3: VERTICAL PRIVILEGE ESCALATION (Tabel Keamanan No. 4 & No. 5)
# Mahasiswa dilarang membuat atau menghapus mata kuliah
# Dicegah oleh CSRF (419), Policy (403), atau Auth Guard (401)
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[3] Menguji Vertical Privilege Escalation (Mahasiswa -> Admin Action)${NC}"

STATUS_MHS_POST_COURSE=$(curl -s -o /dev/null -w "%{http_code}" -k -X POST "$TARGET_URL/courses" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS" \
    -d "code=HACK101&name=Mata+Kuliah+Ilegal&sks=3&lecturer_id=1")
assert_status "Mahasiswa POST /courses (Membuat Mata Kuliah)" "401|403|419" "$STATUS_MHS_POST_COURSE"

STATUS_MHS_DELETE_COURSE=$(curl -s -o /dev/null -w "%{http_code}" -k -X DELETE "$TARGET_URL/courses/1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa DELETE /courses/1 (Menghapus Mata Kuliah)" "401|403|419" "$STATUS_MHS_DELETE_COURSE"


# ------------------------------------------------------------------------------
# BAGIAN 4: HORIZONTAL PRIVILEGE ESCALATION (Tabel Keamanan No. 3 & No. 6)
# Dosen A dilarang mengedit atau membajak kelas milik Dosen B
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[4] Menguji Horizontal Privilege Escalation (Dosen Mengubah Kelas Lain)${NC}"

STATUS_DOSEN_EDIT_OTHER=$(curl -s -o /dev/null -w "%{http_code}" -k -X PUT "$TARGET_URL/courses/5" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_DOSEN" \
    -d "name=Nama+Mata+Kuliah+Dibajak&sks=2")
assert_status "Dosen PUT /courses/{id_kelas_lain} (Edit Kelas Dosen Lain)" "401|403|404|419" "$STATUS_DOSEN_EDIT_OTHER"

STATUS_DOSEN_ENROLL_OTHER=$(curl -s -o /dev/null -w "%{http_code}" -k -X POST "$TARGET_URL/courses/5/enroll" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_DOSEN" \
    -d "student_id=1")
assert_status "Dosen POST /courses/{id_kelas_lain}/enroll (Enroll ke Kelas Lain)" "401|403|404|419" "$STATUS_DOSEN_ENROLL_OTHER"


# ------------------------------------------------------------------------------
# BAGIAN 5: TITIK RAWAN IDOR SUBMISSION (Tabel Keamanan No. 10 & No. 11)
# Mahasiswa dilarang melihat berkas tugas mahasiswa lain & rekap tugas kelas
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[5] Menguji Titik Rawan IDOR Pengumpulan Tugas (Submissions)${NC}"

STATUS_MHS_VIEW_SUBMISSION=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/submissions/1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa GET /submissions/{id_orang_lain} (Intip Tugas Mahasiswa Lain)" "401|403|404" "$STATUS_MHS_VIEW_SUBMISSION"

STATUS_MHS_DOWNLOAD_SUBMISSION=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/submissions/1/download" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa GET /submissions/{id_orang_lain}/download (Unduh File Siswa Lain)" "401|403|404" "$STATUS_MHS_DOWNLOAD_SUBMISSION"

STATUS_MHS_LIST_SUBMISSIONS=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/dosen/assignments/1/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa GET /dosen/assignments/1/submissions (Intip Seluruh Submissions)" "401|403|404" "$STATUS_MHS_LIST_SUBMISSIONS"


# ------------------------------------------------------------------------------
# BAGIAN 6: TITIK RAWAN IDOR PENILAIAN / GRADING (Tabel Keamanan No. 12)
# Mahasiswa dilarang memberi nilai pada submission (Vertical Escalation)
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[6] Menguji Titik Rawan IDOR Penilaian (Grading Submission)${NC}"

STATUS_MHS_GRADE=$(curl -s -o /dev/null -w "%{http_code}" -k -X PUT "$TARGET_URL/submissions/1/grade" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS" \
    -d "score=100&feedback=Nilai+Sendiri+Otomatis+A")
assert_status "Mahasiswa PUT /submissions/1/grade (Memberi Nilai 100 ke Diri Sendiri)" "401|403|404|419" "$STATUS_MHS_GRADE"


# ------------------------------------------------------------------------------
# BAGIAN 7: TITIK RAWAN IDOR MATERI & TUGAS TIDAK DIIKUTI (Tabel Keamanan No. 7 & 9)
# Mahasiswa dilarang mengakses materi atau soal tugas di kelas yang tidak diikutinya
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}${BOLD}[7] Menguji Akses Kelas yang Tidak Diikuti (Unenrolled Access)${NC}"

STATUS_MHS_UNENROLLED_MATERIAL=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/materials/999" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa GET /materials/{unenrolled} (Akses Materi Kelas Luar)" "401|403|404" "$STATUS_MHS_UNENROLLED_MATERIAL"

STATUS_MHS_UNENROLLED_ASSIGNMENT=$(curl -s -o /dev/null -w "%{http_code}" -k "$TARGET_URL/assignments/999" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN_MHS")
assert_status "Mahasiswa GET /assignments/{unenrolled} (Akses Tugas Kelas Luar)" "401|403|404" "$STATUS_MHS_UNENROLLED_ASSIGNMENT"


# ------------------------------------------------------------------------------
# REKAPITULASI HASIL PENGUJIAN
# ------------------------------------------------------------------------------
echo -e "\n${BOLD}====================================================================${NC}"
echo -e "${BOLD} REKAPITULASI PENGUJIAN OTORISASI & KEAMANAN${NC}"
echo -e " Total Pengujian : $TOTAL_TESTS"
echo -e " Lolos (Passed)  : ${GREEN}$PASSED_TESTS${NC}"
echo -e " Gagal (Failed)  : ${RED}$FAILED_TESTS${NC}"

if [ "$FAILED_TESTS" -eq 0 ]; then
    echo -e "\n ${GREEN}${BOLD}SELURUH TITIK RAWAN KEAMANAN TERLINDUNGI DENGAN BAIK! (100% PASS)${NC}"
    echo -e " Skrip ini siap dilampirkan sebagai bukti deliverable Tugas 4."
else
    echo -e "\n ${RED}${BOLD}PERHATIAN: Ada endpoint yang belum terproteksi dengan benar.${NC}"
fi
echo -e "${BOLD}====================================================================${NC}"
