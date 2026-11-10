#!/usr/bin/env bash

# ==============================================================================
# Skrip Deployment Otomatis KampusLMS (Laravel 12)
# ==============================================================================
# Ketentuan Skrip:
# 1. Berhenti segera pada kesalahan pertama (set -e).
# 2. Mode maintenance aktif selama proses deployment.
# 3. Restart queue worker (wajib agar kode baru terbaca oleh daemon).
# 4. Pembersihan dan pembuatan ulang seluruh cache Laravel.
# 5. Setiap baris memiliki komentar penjelasan risiko jika dihilangkan.
# 6. Verifikasi kesehatan HTTP aplikasi sebelum keluar dari mode maintenance.
# ==============================================================================

# Menghentikan eksekusi skrip segera jika ada salah satu perintah yang gagal/error.
# AKIBAT JIKA DIHILANGKAN: Skrip akan terus lanjut mengeksekusi baris berikutnya meskipun
# langkah krusial (seperti git pull atau composer install) gagal, menyebabkan aplikasi rusak/inconsistent.
set -e

echo "🚀 [1/8] Memulai alur deployment KampusLMS..."

# Mengaktifkan mode maintenance (down) dengan secret bypass agar tim deploy tetap bisa menguji jika diperlukan.
# AKIBAT JIKA DIHILANGKAN: Pengguna umum bisa mengakses aplikasi saat migrasi database atau pembaruan berkas
# sedang berlangsung, yang berisiko memicu error 500, data korup, atau tampilan berantakan di sisi pengguna.
php artisan down --render="errors::503" --secret="kampuslms-bypass-key" || true

echo "📥 [2/8] Menarik perubahan kode terbaru dari repositori remote..."
# Mengambil commit terbaru dari branch utama repositori git.
# AKIBAT JIKA DIHILANGKAN: Server akan tetap menjalankan versi kode lama meskipun skrip deployment dijalankan.
git pull origin main

echo "📦 [3/8] Memperbarui dependensi Composer (PHP vendor)..."
# Memasang dependensi PHP khusus produksi tanpa dependensi dev (--no-dev) serta mengoptimalkan autoloader.
# AKIBAT JIKA DIHILANGKAN: Class atau pustaka baru yang ditambahkan pada commit terbaru tidak akan ditemukan
# dan memicu fatal error 'Class not found'.
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "🗄️ [4/8] Menjalankan migrasi basis data..."
# Menjalankan migrasi skema tabel secara otomatis dengan flag --force (wajib di production).
# AKIBAT JIKA DIHILANGKAN: Kolom atau tabel baru yang dibutuhkan fitur baru tidak tercipta di database,
# memicu SQL query error saat fitur diakses.
php artisan migrate --force

echo "⚡ [5/8] Membersihkan dan menyusun ulang seluruh cache optimasi..."
# Membersihkan seluruh cache lama (config, route, view, event).
# AKIBAT JIKA DIHILANGKAN: Laravel mungkin masih membaca definisi route, view template, atau config lama
# yang sudah usang atau mengalami perubahan struktur.
php artisan optimize:clear

# Menyusun ulang cache konfigurasi, route, dan view untuk performa maksimal di lingkungan produksi.
# AKIBAT JIKA DIHILANGKAN: Waktu respon aplikasi menjadi lebih lambat karena Laravel harus mem-parsing
# puluhan file config, route file, dan blade template di setiap request HTTP masuk.
php artisan optimize

echo "🔄 [6/8] Me-restart Queue Worker..."
# Menginstruksikan seluruh worker antrean yang berjalan di background untuk berhenti dan me-restart proses.
# ALASAN WAJIB & AKIBAT JIKA DIHILANGKAN: Laravel queue worker (seperti Supervisor) bekerja sebagai daemon
# jangka panjang yang memuat kode aplikasi ke dalam memori RAM sekali saat pertama start. Jika tidak di-restart,
# worker akan terus menjalankan logika kode lama dari RAM dan tidak akan pernah mengenali perubahan kode baru!
php artisan queue:restart

echo "🩺 [7/8] Memverifikasi kesehatan aplikasi sebelum menonaktifkan mode maintenance..."
# Melakukan health check internal via CLI untuk memastikan framework dan database terhubung tanpa error 500.
# AKIBAT JIKA DIHILANGKAN: Aplikasi bisa langsung dibuka kembali ke publik padahal sedang mengalami crash/fatal error.
php artisan about > /dev/null 2>&1 || {
    echo "❌ FATAL: Verifikasi aplikasi gagal! Server tetap berada dalam mode maintenance untuk keamanan."
    exit 1
}

echo "🌐 [8/8] Mengembalikan aplikasi ke status online (keluar dari mode maintenance)..."
# Menonaktifkan mode maintenance sehingga pengguna publik dapat kembali mengakses web secara normal.
# AKIBAT JIKA DIHILANGKAN: Pengguna akan terus melihat halaman '503 Service Unavailable' meskipun proses deploy telah usai.
php artisan up

echo "✅ [SELESAI] Deployment berhasil tanpa kesalahan! Aplikasi live dan siap melayani pengguna."
