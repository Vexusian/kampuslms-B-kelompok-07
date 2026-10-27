<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom untuk mendukung pengunggahan berkas tugas:
     * - original_filename : nama asli berkas yang diunggah mahasiswa
     * - is_late           : flag keterlambatan, dihitung di server
     *
     * Kolom file_path sudah ada di tabel, tetapi sebelumnya nullable.
     * Migrasi ini tidak mengubah nullability file_path agar backward-compatible
     * dengan data lama (jika ada). Validasi "wajib ada" ditangani di Controller.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('file_path');
            $table->boolean('is_late')->default(false)->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['original_filename', 'is_late']);
        });
    }
};
