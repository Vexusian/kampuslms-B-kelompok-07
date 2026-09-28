<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Minggu5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_cannot_access_protected_role_routes(): void
    {
        $response = $this->get('/admin/courses');
        $response->assertStatus(302)->assertRedirect(); // auth middleware redirects to login
    }

    public function test_role_middleware_blocks_unauthorized_role(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen)->get('/admin/courses');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/courses');
        $response->assertStatus(200);
    }

    public function test_scope_bindings_prevents_access_to_mismatched_nested_resource(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $courseA = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $courseB = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $assignmentOfB = Assignment::factory()->create([
            'course_id' => $courseB->id,
            'created_by' => $dosen->id,
        ]);

        // Mengakses assignment B lewat Course A -> Harus 404 karena scopeBindings
        $response = $this->actingAs($dosen)->get("/dosen/courses/{$courseA->id}/assignments/{$assignmentOfB->id}");
        $response->assertStatus(404);
    }

    public function test_idor_protection_blocks_student_from_viewing_other_student_submission(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        $mhsA = User::factory()->create(['role' => 'mahasiswa']);
        $mhsB = User::factory()->create(['role' => 'mahasiswa']);

        $submissionB = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mhsB->id,
        ]);

        // Mahasiswa A mencoba mengakses submission milik Mahasiswa B -> Harus 403
        $response = $this->actingAs($mhsA)->get("/mahasiswa/submissions/{$submissionB->id}");
        $response->assertStatus(403);
    }

    public function test_custom_403_page_rendered(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen)->get('/admin/courses');
        $response->assertStatus(403);
        $response->assertSee('Akses Tidak Diizinkan');
        $response->assertSee('HTTP 403 Forbidden');
    }
}
