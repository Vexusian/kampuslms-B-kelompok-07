#!/usr/bin/env bash

# ==============================================================================
# Skrip Deployment Otomatis KampusLMS (Laravel 12)
# Mengacu pada Modul Minggu 12 (Milestone M4 / Tugas 4)
# ==============================================================================
# Ketentuan Skrip:
# 1. Berhenti segera pada kesalahan pertama (set -e).
# 2. Mode maintenance aktif selama proses deployment.
# 3. Pembaruan dependensi backend (Composer) & frontend (Vite).
# 4. Migrasi basis data terisolasi tanpa db:seed (--force).
# 5. Pembersihan dan pembuatan ulang seluruh cache optimasi Laravel.
# 6. Restart queue worker (wajib agar kode baru terbaca oleh daemon).
# 7. Setiap baris memiliki komentar penjelasan risiko jika dihilangkan.
# 8. Verifikasi kesehatan HTTP aplikasi sebelum keluar dari mode maintenance.
# ==============================================================================

# Menghentikan eksekusi skrip segera jika ada salah satu perintah yang gagal/error.
# AKIBAT JIKA DIHILANGKAN: Skrip akan terus lanjut mengeksekusi baris berikutnya meskipun
# langkah krusial (seperti git pull atau composer install) gagal, menyebabkan aplikasi beroperasi dalam kondisi rusak/inconsistent.
set -e

echo "🚀 [1/9] Memulai alur deployment KampusLMS..."

# Mengaktifkan mode maintenance (down) dengan secret bypass agar tim deploy tetap bisa menguji jika diperlukan.
# AKIBAT JIKA DIHILANGKAN: Pengguna umum bisa mengakses aplikasi saat migrasi database atau pembaruan berkas
# sedang berlangsung, yang berisiko memicu error 500, data korup, atau tampilan berantakan di sisi pengguna.
echo "🔒 Mengaktifkan mode pemeliharaan (maintenance mode)..."
php artisan down --render="errors::503" --secret="kampuslms-bypass-key" || true

echo "📥 [2/9] Menarik perubahan kode terbaru dari repositori remote..."
# Mengambil commit terbaru dari branch utama repositori git.
# AKIBAT JIKA DIHILANGKAN: Server akan tetap menjalankan versi kode lama meskipun skrip deployment dijalankan.
git pull origin main

echo "📦 [3/9] Memperbarui dependensi Composer (PHP vendor)..."
# Memasang dependensi PHP khusus produksi tanpa dependensi dev (--no-dev) serta mengoptimalkan autoloader.
# AKIBAT JIKA DIHILANGKAN: Class atau pustaka baru yang ditambahkan pada commit terbaru tidak akan ditemukan
# dan memicu fatal error 'Class not found'.
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "🎨 [4/9] Mengompilasi aset frontend dengan Vite..."
# Memasang paket Node.js persis sesuai lockfile dan mem-build aset produksi yang terkompresi.
# AKIBAT JIKA DIHILANGKAN: Modul JS/CSS baru tidak terkompilasi, menyebabkan tampilan web hancur atau script frontend tidak berfungsi.
npm ci
npm run build

echo "🗄️ [5/9] Menjalankan migrasi basis data..."
# Menjalankan migrasi skema tabel secara otomatis dengan flag --force (wajib di production).
# AKIBAT JIKA DIHILANGKAN: Kolom atau tabel baru yang dibutuhkan fitur baru tidak tercipta di database,
# memicu SQL query error saat fitur diakses. PERINGATAN: Jangan pernah menjalankan db:seed pada deployment ulang!
php artisan migrate --force

echo "⚡ [6/9] Membersihkan dan menyusun ulang seluruh cache optimasi..."
# Membersihkan seluruh cache lama (config, route, view, event) lalu menyusun ulang cache baru.
# AKIBAT JIKA DIHILANGKAN: Laravel membaca cache konfigurasi atau route lama yang usang,
# dan performa aplikasi menjadi lambat karena harus mem-parsing ulang file di setiap request masuk.
php artisan optimize:clear
php artisan optimize

echo "🔄 [7/9] Me-restart Queue Worker..."
# Menginstruksikan seluruh worker antrean yang berjalan di background untuk berhenti dan me-restart proses.
# ALASAN WAJIB & AKIBAT JIKA DIHILANGKAN: Laravel queue worker (seperti Supervisor) bekerja sebagai daemon
# jangka panjang yang memuat kode aplikasi ke dalam memori RAM sekali saat pertama start. Jika tidak di-restart,
# worker akan terus menjalankan logika kode lama dari RAM dan tidak akan pernah mengenali perubahan kode baru!
php artisan queue:restart

echo "🩺 [8/9] Memverifikasi kesehatan aplikasi sebelum menonaktifkan mode maintenance..."
# Melakukan health check internal via CLI untuk memastikan framework dan database terhubung tanpa error 500.
# AKIBAT JIKA DIHILANGKAN: Aplikasi bisa langsung dibuka kembali ke publik padahal sedang mengalami crash/fatal error.
php artisan about > /dev/null 2>&1 || {
    echo "❌ FATAL: Verifikasi aplikasi gagal! Server tetap berada dalam mode maintenance untuk keamanan."
    exit 1
}

echo "🌐 [9/9] Mengembalikan aplikasi ke status online (keluar dari mode maintenance)..."
# Menonaktifkan mode maintenance sehingga pengguna publik dapat kembali mengakses web secara normal.
# AKIBAT JIKA DIHILANGKAN: Pengguna akan terus melihat halaman '503 Service Unavailable' meskipun proses deploy telah usai.
php artisan up

echo "✅ [SELESAI] Deployment KampusLMS berhasil diselesaikan tanpa kesalahan! Aplikasi live dan siap melayani pengguna."
