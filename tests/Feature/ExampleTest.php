<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_page_returns_a_successful_response(): void
    {
        $this->withoutVite();
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('DASHBOARD');
        $response->assertSee('Total Mata Kuliah');
    }

    public function test_dashboard_renders_course_data_properly(): void
    {
        $this->withoutVite();

        $dosen = \App\Models\User::factory()->create([
            'name' => 'Dr. Budi Santoso',
            'role' => 'dosen',
        ]);

        $course = \App\Models\Course::factory()->create([
            'code' => 'IF101',
            'name' => 'Algoritma dan Pemrograman',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($dosen)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('DASHBOARD');
        $response->assertSee('IF101');
        $response->assertSee('Algoritma dan Pemrograman');
    }
}
