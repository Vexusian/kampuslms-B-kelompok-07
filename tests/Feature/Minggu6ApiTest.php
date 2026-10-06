<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Minggu6ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_returns_token_and_user_resource(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen@kampuslms.test',
            'password' => 'password',
            'role' => 'dosen',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'dosen@kampuslms.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'nim_nip', 'created_at'],
            ])
            ->assertJsonPath('user.email', 'dosen@kampuslms.test');
    }

    public function test_login_with_wrong_password_prevents_user_enumeration(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@kampuslms.test',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
    }

    public function test_unauthenticated_request_to_protected_endpoint_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_me_profile(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/me');

        $response->assertStatus(200)
            ->assertViewIs('dashboard');
    }

    public function test_courses_index_scoped_by_role(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $courseA = Course::factory()->create(['lecturer_id' => $dosenA->id]);
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);

        Sanctum::actingAs($dosenA);

        $response = $this->get('/api/v1/courses');

        $response->assertStatus(200)
            ->assertViewIs('courses.index')
            ->assertViewHas('courses');

        $courseIds = $response->viewData('courses')->pluck('id');
        $this->assertTrue($courseIds->contains($courseA->id));
        $this->assertFalse($courseIds->contains($courseB->id));
    }

    public function test_student_forbidden_from_creating_assignment(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Sanctum::actingAs($mahasiswa);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas Baru',
            'instructions' => 'Kerjakan tugas ini.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke sumber daya ini.');
    }

    public function test_dosen_can_create_assignment_for_own_course(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Sanctum::actingAs($dosen);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas 1 Pemrograman Web',
            'instructions' => 'Buat REST API.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Tugas 1 Pemrograman Web');
    }

    public function test_dosen_cannot_create_assignment_for_other_dosen_course(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);

        Sanctum::actingAs($dosenA);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id' => $courseB->id,
            'title' => 'Tugas Pembajakan',
            'instructions' => 'Tidak boleh',
            'due_at' => now()->addDays(7)->toDateTimeString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_dosen_can_upsert_grade_with_201_then_200(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $mhs->id,
            'content' => 'Jawaban mahasiswa.',
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($dosen);

        // First grading: 201 Created
        $response1 = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 85,
            'feedback' => 'Bagus sekali!',
        ]);

        $response1->assertStatus(201)
            ->assertJsonPath('data.score', 85)
            ->assertJsonPath('data.feedback', 'Bagus sekali!');

        // Second grading (update): 200 OK
        $response2 = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 95,
            'feedback' => 'Direvisi menjadi lebih baik.',
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.score', 95);
    }

    public function test_dosen_can_delete_assignment_returning_204(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        Sanctum::actingAs($dosen);

        $response = $this->deleteJson("/api/v1/assignments/{$assignment->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        // tidak memakai Sanctum::actingAs()
        $response = $this->getJson('/api/v1/me');   // endpoint yang dilindungi

        $response->assertStatus(401)
                ->assertJsonPath('message', 'Unauthenticated.');
    }
}