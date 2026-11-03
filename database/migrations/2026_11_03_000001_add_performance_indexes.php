<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan indeks database untuk mempercepat query-query yang sering dieksekusi.
     */
    public function up(): void
    {
        // 1. Indeks pada tabel users: role
        // Query yang diuntungkan:
        // - User::where('role', 'dosen') (dropdown pilihan dosen pada form mata kuliah)
        // - User::where('role', 'mahasiswa') (dropdown enrollment mahasiswa ke kelas)
        // - Filter user berdasarkan role di panel admin
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });

        // 2. Indeks pada tabel courses: status
        // Query yang diuntungkan:
        // - Course::where('status', 'active') (katalog publik mata kuliah & filter aktif)
        // - Course::where('status', 'draft') (dashboard dosen & filter draft)
        // - Agregasi hitungan total mata kuliah aktif/draft di dashboard
        Schema::table('courses', function (Blueprint $table) {
            $table->index('status', 'courses_status_index');
        });

        // 3. Indeks pada tabel assignments: status dan created_by
        // Query yang diuntungkan:
        // - Assignment::where('status', 'published') (daftar tugas terbit mahasiswa)
        // - Assignment::where('created_by', $user->id) / Policy verifikasi dosen pembuat tugas
        Schema::table('assignments', function (Blueprint $table) {
            $table->index('status', 'assignments_status_index');
            $table->index('created_by', 'assignments_created_by_index');
        });

        // 4. Indeks pada tabel submissions: submitted_at dan is_late
        // Query yang diuntungkan:
        // - Submission::latest('submitted_at') / orderBy('submitted_at', 'desc') (daftar pengumpulan tugas dosen)
        // - Submission::where('is_late', true) (analisis dan pelaporan keterlambatan)
        Schema::table('submissions', function (Blueprint $table) {
            $table->index('submitted_at', 'submissions_submitted_at_index');
            $table->index('is_late', 'submissions_is_late_index');
        });

        // 5. Indeks pada tabel grades: graded_at
        // Query yang diuntungkan:
        // - Grade::latest('graded_at') (audit dan riwayat penilaian tugas terbaru)
        Schema::table('grades', function (Blueprint $table) {
            $table->index('graded_at', 'grades_graded_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_status_index');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_status_index');
            $table->dropIndex('assignments_created_by_index');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('submissions_submitted_at_index');
            $table->dropIndex('submissions_is_late_index');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_graded_at_index');
        });
    }
};

