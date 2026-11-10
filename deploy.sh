#!/usr/bin/env bash

# ==============================================================================
# Skrip Deployment Otomatis KampusLMS (Laravel 12)
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

