<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DataExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'name' => 'Budi Sudarsono',
            'email' => 'budi@kampus.ac.id',
            'nim_nip' => '2026001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Budi Sudarsono', $response->streamedContent());
        $this->assertStringContainsString('budi@kampus.ac.id', $response->streamedContent());
    }

    public function test_admin_can_download_student_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.students.template'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Nama', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('NIM', $content);
    }

    public function test_non_admin_cannot_access_student_import_or_export(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)->get(route('admin.students.export'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students.template'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.students.import'))->assertForbidden();
    }

    public function test_admin_can_import_students_and_records_import_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = "Nama,Email,NIM,Password\n".
                      "Doni Prasetyo,doni@kampus.ac.id,20261010,secret123\n".
                      "Rina Novita,rina@kampus.ac.id,20261011,secret123\n";

        $file = UploadedFile::fake()->createWithContent('mahasiswa_baru.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'doni@kampus.ac.id',
            'nim_nip' => '20261010',
            'role' => 'mahasiswa',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'rina@kampus.ac.id',
            'nim_nip' => '20261011',
            'role' => 'mahasiswa',
        ]);

        $this->assertDatabaseHas('import_logs', [
            'user_id' => $admin->id,
            'type' => 'students',
            'filename' => 'mahasiswa_baru.csv',
            'total_rows' => 2,
            'success_count' => 2,
            'error_count' => 0,
            'status' => 'completed',
        ]);
    }

    public function test_lecturer_can_export_and_download_template_for_course_grades(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => 'Tugas Algoritma']);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Alif Ramadhan', 'email' => 'alif@kampus.ac.id']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $responseExport = $this->actingAs($dosen)->get(route('courses.grades.export', $course));
        $responseExport->assertOk();
        $this->assertStringContainsString('Tugas Algoritma', $responseExport->streamedContent());
        $this->assertStringContainsString('Alif Ramadhan', $responseExport->streamedContent());

        $responseTemplate = $this->actingAs($dosen)->get(route('courses.grades.template', $course));
        $responseTemplate->assertOk();
        $this->assertStringContainsString('Tugas Algoritma', $responseTemplate->streamedContent());
    }

    public function test_unauthorized_user_cannot_export_or_import_course_grades(): void
    {
        $dosen1 = User::factory()->create(['role' => 'dosen']);
        $dosen2 = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen1->id]);

        $this->actingAs($dosen2)->get(route('courses.grades.export', $course))->assertForbidden();
        $this->actingAs($dosen2)->get(route('courses.grades.template', $course))->assertForbidden();

        $file = UploadedFile::fake()->createWithContent('nilai.csv', "ID Tugas,Email Mahasiswa,Nilai\n");
        $this->actingAs($dosen2)->post(route('courses.grades.import', $course), ['file' => $file])->assertForbidden();
    }

    public function test_course_lecturer_can_import_grades_and_records_import_log(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'mahasiswa', 'email' => 'candra@kampus.ac.id', 'nim_nip' => '20263001']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $csvContent = "ID Tugas,Judul Tugas,NIM,Nama Mahasiswa,Email Mahasiswa,Nilai,Feedback\n".
                      "{$assignment->id},{$assignment->title},20263001,Candra,candra@kampus.ac.id,88,Penyelesaian rapi\n";

        $file = UploadedFile::fake()->createWithContent('nilai_kelas.csv', $csvContent);

        $response = $this->actingAs($dosen)->post(route('courses.grades.import', $course), [
            'file' => $file,
        ]);

        $response->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();
        $this->assertNotNull($submission);

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'graded_by' => $dosen->id,
            'score' => 88,
            'feedback' => 'Penyelesaian rapi',
        ]);

        $this->assertDatabaseHas('import_logs', [
            'user_id' => $dosen->id,
            'type' => 'grades',
            'filename' => 'nilai_kelas.csv',
            'total_rows' => 1,
            'success_count' => 1,
            'error_count' => 0,
            'status' => 'completed',
        ]);
    }

    public function test_import_students_does_not_overwrite_admin_or_dosen_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@kampus.ac.id', 'name' => 'Admin Utama']);
        $dosen = User::factory()->create(['role' => 'dosen', 'email' => 'dosen@kampus.ac.id', 'name' => 'Dosen Pengampu']);

        $csvContent = "Nama,Email,NIM,Password\n".
                      "Hack Admin,admin@kampus.ac.id,20269991,hacked123\n".
                      "Hack Dosen,dosen@kampus.ac.id,20269992,hacked123\n".
                      "Mahasiswa Sah,mhs@kampus.ac.id,20269993,secret123\n";

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Utama',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $dosen->id,
            'name' => 'Dosen Pengampu',
            'role' => 'dosen',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'mhs@kampus.ac.id',
            'role' => 'mahasiswa',
        ]);

        $this->assertDatabaseHas('import_logs', [
            'type' => 'students',
            'total_rows' => 3,
            'success_count' => 1,
            'error_count' => 2,
            'status' => 'partial',
        ]);
    }

    public function test_admin_can_export_and_download_template_in_xlsx_format(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'name' => 'Dewi Lestari',
            'email' => 'dewi@kampus.ac.id',
        ]);

        $exportResp = $this->actingAs($admin)->get(route('admin.students.export', ['format' => 'xlsx']));
        $exportResp->assertOk();
        $exportResp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $templateResp = $this->actingAs($admin)->get(route('admin.students.template', ['format' => 'xlsx']));
        $templateResp->assertOk();
        $templateResp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_grades_rejected_when_course_archived(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
            'status' => 'archived',
        ]);

        $file = UploadedFile::fake()->createWithContent('nilai.csv', "ID Tugas,NIM,Nilai\n");
        $response = $this->actingAs($dosen)->post(route('courses.grades.import', $course), ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Mata kuliah telah diarsipkan. Pengubahan nilai dikunci.');
    }

    public function test_import_grades_validates_max_score_and_enrollment(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'max_score' => 100]);

        // Siswa 1 terdaftar, tapi nilainya 150 (melebihi max_score)
        $student1 = User::factory()->create(['role' => 'mahasiswa', 'nim_nip' => '20268001', 'email' => 's1@kampus.ac.id']);
        $course->students()->attach($student1->id, ['enrolled_at' => now()]);

        // Siswa 2 tidak terdaftar di kelas
        $student2 = User::factory()->create(['role' => 'mahasiswa', 'nim_nip' => '20268002', 'email' => 's2@kampus.ac.id']);

        $csvContent = "ID Tugas,NIM,Nilai\n".
                      "{$assignment->id},20268001,150\n".
                      "{$assignment->id},20268002,80\n";

        $file = UploadedFile::fake()->createWithContent('nilai_invalid.csv', $csvContent);
        $response = $this->actingAs($dosen)->post(route('courses.grades.import', $course), ['file' => $file]);

        $response->assertRedirect();

        $this->assertDatabaseHas('import_logs', [
            'type' => 'grades',
            'total_rows' => 2,
            'success_count' => 0,
            'error_count' => 2,
            'status' => 'failed',
        ]);
    }

    public function test_export_grades_includes_final_score_and_sanitizes_formula_injection(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'title' => '=SUM(A1:A10)', 'max_score' => 100]);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => '+HackerName', 'nim_nip' => '20269999']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
        $submission->grade()->create([
            'graded_by' => $dosen->id,
            'score' => 90,
            'feedback' => '@malicious',
            'graded_at' => now(),
        ]);

        $response = $this->actingAs($dosen)->get(route('courses.grades.export', ['course' => $course, 'format' => 'csv']));
        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Nilai Akhir MK', $content);
        $this->assertStringContainsString('Huruf Mutu', $content);
        // Memastikan formula injection ternetralisir dengan awalan petik tunggal
        $this->assertStringContainsString("'+HackerName", $content);
        $this->assertStringContainsString("'=SUM(A1:A10)", $content);
        $this->assertStringContainsString("'@malicious", $content);
    }
}
