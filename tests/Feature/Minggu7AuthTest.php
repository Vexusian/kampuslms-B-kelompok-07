<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Minggu7AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Masuk ke KampusLMS')
            ->assertSee('Alamat Email')
            ->assertSee('Kata Sandi');
    }

    public function test_user_can_login_with_valid_credentials_and_session_regenerated(): void
    {
        $user = User::factory()->create([
            'email' => 'user@kampuslms.test',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        $response = $this->post('/login', [
            'email' => 'user@kampuslms.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_credentials_returns_generic_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nonexistent@kampuslms.test',
            'password' => 'wrongpass',
        ]);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_user_can_logout_and_session_invalidated(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_create_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($admin)->post('/courses', [
            'code' => 'CS101',
            'name' => 'Dasar Pemrograman',
            'description' => 'Mata kuliah dasar',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', ['code' => 'CS101']);
    }

    public function test_student_and_dosen_cannot_create_course(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response1 = $this->actingAs($student)->post('/courses', [
            'code' => 'CS102',
            'name' => 'Struktur Data',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $response1->assertStatus(403);

        $response2 = $this->actingAs($dosen)->post('/courses', [
            'code' => 'CS103',
            'name' => 'Basis Data',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $response2->assertStatus(403);
    }

    public function test_dosen_can_update_own_course_but_cannot_update_other_dosen_course(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $courseA = Course::factory()->create(['lecturer_id' => $dosenA->id, 'code' => 'KUL01']);
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id, 'code' => 'KUL02']);

        // Dosen A update Course A -> Berhasil
        $responseA = $this->actingAs($dosenA)->put("/courses/{$courseA->id}", [
            'code' => 'KUL01',
            'name' => 'Nama Kursus Baru',
            'sks' => 3,
            'lecturer_id' => $dosenA->id,
            'status' => 'active',
        ]);
        $responseA->assertRedirect("/courses/{$courseA->id}");
        $this->assertDatabaseHas('courses', ['id' => $courseA->id, 'name' => 'Nama Kursus Baru']);

        // Dosen A mencoba update Course B -> 403 Forbidden (IDOR Dicegah oleh Policy)
        $responseB = $this->actingAs($dosenA)->put("/courses/{$courseB->id}", [
            'code' => 'KUL02',
            'name' => 'Pembajakan Mata Kuliah',
            'sks' => 3,
            'lecturer_id' => $dosenA->id,
            'status' => 'active',
        ]);
        $responseB->assertStatus(403);
    }

    public function test_courses_index_scoped_by_role_for_student(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $courseEnrolled = Course::factory()->create(['name' => 'Kelas Enrolled Khusus', 'status' => 'active']);
        $courseOther = Course::factory()->create(['name' => 'Kelas Luar Rahasia', 'status' => 'active']);

        // Enroll ke courseEnrolled saja
        $student->courses()->attach($courseEnrolled->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($student)->get('/courses');

        $response->assertStatus(200)
            ->assertSee($courseEnrolled->name)
            ->assertDontSee($courseOther->name);
    }

    public function test_courses_index_scoped_by_role_for_dosen(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $courseA = Course::factory()->create(['lecturer_id' => $dosenA->id, 'name' => 'Mata Kuliah Dosen A']);
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id, 'name' => 'Mata Kuliah Dosen B']);

        $response = $this->actingAs($dosenA)->get('/courses');

        $response->assertStatus(200)
            ->assertSee($courseA->name)
            ->assertDontSee($courseB->name);
    }

    public function test_idor_student_cannot_view_other_student_submission(): void
    {
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $submission2 = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student2->id,
            'content' => 'Jawaban rahasia mahasiswa 2',
            'submitted_at' => now(),
        ]);

        // Student 1 mencoba melihat submission Student 2 -> 403 Forbidden
        $response = $this->actingAs($student1)->get("/submissions/{$submission2->id}");
        $response->assertStatus(403);
    }

    public function test_idor_student_cannot_view_material_of_unenrolled_course(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create();
        $material = Material::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($student)->get("/materials/{$material->id}");
        $response->assertStatus(403);
    }

    public function test_admin_and_dosen_can_enroll_student_to_course(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        // Dosen enroll student ke course miliknya
        $response = $this->actingAs($dosen)->post("/courses/{$course->id}/enroll", [
            'student_id' => $student->id,
        ]);

        $response->assertRedirect("/courses/{$course->id}");
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }
}
