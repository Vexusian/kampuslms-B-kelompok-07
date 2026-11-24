<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\GradeSubmitted;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Minggu14FeatureTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 1. Autentikasi
    |--------------------------------------------------------------------------
    | - login berhasil
    | - login gagal
    | - akses tanpa login
    */
    public function test_autentikasi_login_berhasil(): void
    {
        $user = User::factory()->create([
            'email' => 'user.auth@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user.auth@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_autentikasi_login_gagal(): void
    {
        $user = User::factory()->create([
            'email' => 'user.auth2@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user.auth2@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_autentikasi_akses_tanpa_login_ditolak(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responseCourses = $this->get('/courses');
        $responseCourses->assertRedirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | 2. CRUD Mata Kuliah
    |--------------------------------------------------------------------------
    | - sukses oleh admin
    | - ditolak untuk dosen/mahasiswa
    */
    public function test_crud_mata_kuliah_sukses_oleh_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'TEST101',
            'name' => 'Mata Kuliah Uji Coba Admin',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'code' => 'TEST101',
            'name' => 'Mata Kuliah Uji Coba Admin',
        ]);
    }

    public function test_crud_mata_kuliah_ditolak_untuk_dosen_dan_mahasiswa(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        // Dosen dilarang create mata kuliah
        $responseDosen = $this->actingAs($dosen)->post('/admin/courses', [
            'code' => 'TESTDOSEN',
            'name' => 'MK Dosen',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $responseDosen->assertStatus(403);

        // Mahasiswa dilarang create mata kuliah
        $responseStudent = $this->actingAs($student)->post('/admin/courses', [
            'code' => 'TESTMHS',
            'name' => 'MK Mahasiswa',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $responseStudent->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Enrollment
    |--------------------------------------------------------------------------
    | - mahasiswa hanya melihat mata kuliahnya
    */
    public function test_enrollment_mahasiswa_hanya_melihat_mata_kuliahnya(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $courseEnrolled = Course::factory()->create(['name' => 'Mata Kuliah Terdaftar']);
        $courseOther = Course::factory()->create(['name' => 'Mata Kuliah Lain']);

        $student->courses()->attach($courseEnrolled->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($student)->get('/courses');
        $response->assertStatus(200);
        $response->assertSee('Mata Kuliah Terdaftar');
        $response->assertDontSee('Mata Kuliah Lain');
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Unggah Materi
    |--------------------------------------------------------------------------
    | - sukses oleh dosen pengampu
    | - ditolak untuk dosen lain
    */
    public function test_unggah_materi_sukses_oleh_dosen_pengampu(): void
    {
        Storage::fake('local');
        $dosenPengampu = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosenPengampu->id]);
        $file = UploadedFile::fake()->create('modul14.pdf', 100, 'application/pdf');

        $response = $this->actingAs($dosenPengampu)->post("/dosen/courses/{$course->id}/materials", [
            'title' => 'Pengenalan Modul 14',
            'description' => 'Isi ringkasan materi kuliah.',
            'type' => 'file',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'title' => 'Pengenalan Modul 14',
        ]);
    }

    public function test_unggah_materi_ditolak_untuk_dosen_lain(): void
    {
        $dosenPengampu = User::factory()->dosen()->create();
        $dosenLain = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosenPengampu->id]);

        $response = $this->actingAs($dosenLain)->post("/dosen/courses/{$course->id}/materials", [
            'title' => 'Materi Ilegal Dosen Lain',
            'content' => 'Harus ditolak.',
        ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Pengumpulan Tugas
    |--------------------------------------------------------------------------
    | - sukses
    | - ditolak setelah deadline bila allow_late false
    | - ditolak untuk yang tidak terdaftar
    */
    public function test_pengumpulan_tugas_sukses(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => now()->addDays(3),
            'allow_late' => true,
        ]);

        $response = $this->actingAs($student)->post("/mahasiswa/assignments/{$assignment->id}/submissions", [
            'content' => 'Jawaban tugas saya yang tepat waktu',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_pengumpulan_tugas_ditolak_setelah_deadline_bila_allow_late_false(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);

        $response = $this->actingAs($student)->post("/mahasiswa/assignments/{$assignment->id}/submissions", [
            'content' => 'Jawaban terlambat',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_pengumpulan_tugas_ditolak_untuk_mahasiswa_tidak_terdaftar(): void
    {
        $dosen = User::factory()->dosen()->create();
        $unregisteredStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => now()->addDays(3),
            'allow_late' => true,
        ]);

        $response = $this->actingAs($unregisteredStudent)->post("/mahasiswa/assignments/{$assignment->id}/submissions", [
            'content' => 'Jawaban dari mahasiswa tidak terdaftar',
        ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Unduhan
    |--------------------------------------------------------------------------
    | - ditolak untuk bukan pemilik / bukan anggota
    */
    public function test_unduhan_materi_ditolak_untuk_bukan_anggota(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->dosen()->create();
        $unregisteredStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $filePath = "materials/{$course->id}/slide.pdf";
        Storage::disk('local')->put($filePath, 'konten file privat');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'type' => 'file',
            'file_path' => $filePath,
            'original_name' => 'slide.pdf',
        ]);

        $response = $this->actingAs($unregisteredStudent)->get("/materials/{$material->id}/download");
        $response->assertStatus(403);
    }

    public function test_unduhan_submission_ditolak_untuk_bukan_pemilik(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->dosen()->create();
        $studentOwner = User::factory()->mahasiswa()->create();
        $studentOther = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach([$studentOwner->id, $studentOther->id], ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $filePath = "submissions/{$assignment->id}/jawaban.pdf";
        Storage::disk('local')->put($filePath, 'konten tugas privat');

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $studentOwner->id,
            'file_path' => $filePath,
            'original_filename' => 'jawaban.pdf',
            'submitted_at' => now(),
        ]);

        // Mahasiswa lain mencoba mengunduh submission milik studentOwner -> Ditolak (403)
        $response = $this->actingAs($studentOther)->get("/submissions/{$submission->id}/download");
        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Penilaian
    |--------------------------------------------------------------------------
    | - sukses oleh dosen pengampu
    | - ditolak untuk dosen lain
    */
    public function test_penilaian_sukses_oleh_dosen_pengampu(): void
    {
        $dosenPengampu = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosenPengampu->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Jawaban mahasiswa',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($dosenPengampu)->put("/submissions/{$submission->id}/grade", [
            'score' => 90,
            'feedback' => 'Hasil pengerjaan sangat baik',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 90,
        ]);
    }

    public function test_penilaian_ditolak_untuk_dosen_lain(): void
    {
        $dosenPengampu = User::factory()->dosen()->create();
        $dosenLain = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosenPengampu->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Jawaban mahasiswa',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($dosenLain)->put("/submissions/{$submission->id}/grade", [
            'score' => 80,
            'feedback' => 'Komentar tidak sah',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Notifikasi
    |--------------------------------------------------------------------------
    | - terkirim saat tugas dibuat
    | - terkirim saat nilai diberikan
    */
    public function test_notifikasi_terkirim_saat_tugas_dibuat(): void
    {
        Event::fake([AssignmentPublished::class]);

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $this->actingAs($dosen)->post("/dosen/courses/{$course->id}/assignments", [
            'title' => 'Tugas Baru Notifikasi',
            'description' => 'Kerjakan modul 14',
            'due_at' => now()->addDays(5)->toDateTimeString(),
        ]);

        Event::assertDispatched(AssignmentPublished::class, function ($event) {
            return $event->assignment->title === 'Tugas Baru Notifikasi';
        });
    }

    public function test_notifikasi_terkirim_saat_nilai_diberikan(): void
    {
        Event::fake([GradeSubmitted::class]);

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Jawaban mahasiswa',
            'submitted_at' => now(),
        ]);

        $this->actingAs($dosen)->put("/submissions/{$submission->id}/grade", [
            'score' => 88,
            'feedback' => 'Bagus',
        ]);

        Event::assertDispatched(GradeSubmitted::class, function ($event) use ($submission) {
            return $event->grade->submission_id === $submission->id && $event->grade->score == 88;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Fitur Tambahan Teruji 
    |--------------------------------------------------------------------------
    */
    public function test_autentikasi_logout_berhasil_dan_session_dibersihkan(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_autentikasi_login_throttled_setelah_5_kali_percobaan_gagal(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'throttle14@kampuslms.test',
                'password' => 'wrongpass',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'throttle14@kampuslms.test',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(429);
    }

    public function test_crud_mata_kuliah_dosen_bisa_update_kelas_sendiri_tetapi_ditolak_untuk_kelas_dosen_lain(): void
    {
        $dosenA = User::factory()->dosen()->create();
        $dosenB = User::factory()->dosen()->create();

        $courseA = Course::factory()->create(['lecturer_id' => $dosenA->id, 'code' => 'KUL14A']);
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id, 'code' => 'KUL14B']);

        // Dosen A update Course A miliknya -> Berhasil
        $responseA = $this->actingAs($dosenA)->put("/courses/{$courseA->id}", [
            'code' => 'KUL14A',
            'name' => 'Nama Kursus Diperbarui',
            'sks' => 3,
            'lecturer_id' => $dosenA->id,
            'status' => 'active',
        ]);
        $responseA->assertRedirect("/courses/{$courseA->id}");
        $this->assertDatabaseHas('courses', ['id' => $courseA->id, 'name' => 'Nama Kursus Diperbarui']);

        // Dosen A mencoba update Course B milik dosen lain -> Ditolak (403 Forbidden)
        $responseB = $this->actingAs($dosenA)->put("/courses/{$courseB->id}", [
            'code' => 'KUL14B',
            'name' => 'Pembajakan Mata Kuliah',
            'sks' => 3,
            'lecturer_id' => $dosenA->id,
            'status' => 'active',
        ]);
        $responseB->assertStatus(403);
    }

    public function test_enrollment_dosen_pengampu_bisa_daftarkan_mahasiswa_ke_mata_kuliah(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->post("/courses/{$course->id}/enroll", [
            'student_id' => $student->id,
        ]);

        $response->assertRedirect("/courses/{$course->id}");
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_pengumpulan_tugas_tidak_bisa_dikumpulkan_ulang_jika_sudah_final(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        // Submit pertama kali
        $this->actingAs($student)->post("/mahasiswa/assignments/{$assignment->id}/submissions", [
            'content' => 'Jawaban Pertama Final',
        ]);

        // Percobaan submit kedua kali ditolak / tidak memperbarui jawaban
        $this->actingAs($student)->post("/mahasiswa/assignments/{$assignment->id}/submissions", [
            'content' => 'Mencoba Mengubah Jawaban',
        ]);

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Mencoba Mengubah Jawaban',
        ]);
    }
}
