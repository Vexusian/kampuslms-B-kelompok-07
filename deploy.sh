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
# Mengacu pada Modul Minggu 12 (Milestone M4 / Tugas 4)
# ==============================================================================

# 1. Hentikan eksekusi skrip jika terjadi kegagalan (exit non-zero) pada perintah apa pun.
# Alasan: Lebih baik deployment terhenti di tengah jalan daripada sistem beroperasi
# dalam kondisi setengah jadi (inconsistent state).
set -e

echo "🚀 Memulai proses deployment KampusLMS..."

# 2. Aktifkan Maintenance Mode.
# Alasan: Mencegah pengguna mengakses sistem saat berkas sedang ditimpa atau migrasi
# database sedang berjalan, sehingga mencegah data corrupt atau error 500 acak.
echo "🔒 Mengaktifkan mode pemeliharaan (maintenance mode)..."
php artisan down --render="errors::503" --secret="kampuslms-deploy-bypass" || true

# 3. Ambil pembaruan kode terbaru dari remote repository.
echo "📥 Menarik kode terbaru dari git..."
git pull origin main

# 4. Pasang dependensi PHP tingkat produksi.
# Alasan: --no-dev membuang library testing/faker untuk menghemat disk & memori,
# dan --optimize-autoloader mengubah PSR-4 menjadi classmap statis untuk performa maksimal.
echo "📦 Memasang dependensi Composer (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Pasang dependensi dan kompilasi bundle aset frontend.
# Alasan: npm ci menjamin versi package persis sesuai package-lock.json,
# dan npm run build mengompresi CSS/JS untuk kecepatan muat klien.
echo "🎨 Mengompilasi aset frontend dengan Vite..."
npm ci
npm run build

# 6. Jalankan migrasi basis data secara terisolasi.
# Alasan: Flag --force wajib digunakan di production karena Laravel membatasi
# migrasi otomatis. PERINGATAN: Jangan pernah menjalankan db:seed pada deployment ulang!
echo "🗄️ Menjalankan migrasi basis data..."
php artisan migrate --force

# 7. Buat cache konfigurasi, route, dan view untuk optimisasi produksi.
# Alasan: Menghilangkan pembacaan file I/O berulang kali di setiap request HTTP.
# PENTING: Pastikan tidak ada fungsi env() di luar folder config/ sebelum langkah ini.
echo "⚡ Mengoptimalkan cache sistem (config, route, view, event)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Restart Queue Worker (KRUSIAL!).
# Alasan: Proses daemon queue:work memuat kode aplikasi ke memori RAM saat mulai.
# Jika kode di-update tanpa queue:restart, worker akan terus menjalankan logika lama
# atau mengalami Fatal Error saat memanggil class yang strukturnya sudah berubah.
# Supervisor akan otomatis menyalakan ulang worker baru dengan kode terkini.
echo "🔄 Me-restart queue worker..."
php artisan queue:restart

# 9. Matikan Maintenance Mode (Situs kembali live).
echo "🔓 Mematikan mode pemeliharaan..."
php artisan up

echo "✅ Deployment KampusLMS berhasil diselesaikan tanpa kesalahan!"

