<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun demo (3 akun)
        $admin = User::factory()->create([
            'name' => 'Admin Kampus',
            'email' => 'admin@kampus.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $dosen = User::factory()->count(3)->create([
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->count(30)->create([
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
        ]);

        // 2. Mata kuliah (5)
        $courses = Course::factory()
            ->count(5)
            ->sequence(fn ($seq) => ['lecturer_id' => $dosen[$seq->index % 3]->id])
            ->create();

        // 3. Daftarkan mahasiswa ke mata kuliah (acak)
        foreach ($courses as $course) {
            $course->students()->attach(
                $mahasiswa->random(rand(5, 15))->pluck('id')
            );
        }

        // 4. Materi, tugas, submission, grade
        foreach ($courses as $course) {
            Material::factory()->count(3)->create(['course_id' => $course->id]);

            $assignments = Assignment::factory()->count(4)->create([
                'course_id' => $course->id,
            ]);

            foreach ($assignments as $assignment) {
                $studentsInCourse = $course->students;

                foreach ($studentsInCourse as $student) {
                    $isLate = fake()->boolean(20);
                    $submittedAt = $isLate
                        ? $assignment->due_at->copy()->addDays(rand(1, 5))
                        : $assignment->due_at->copy()->subDays(rand(0, 3));

                    $submission = Submission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $student->id,
                        'content' => fake()->paragraphs(3, true),
                        'submitted_at' => $submittedAt,
                    ]);

                    Grade::create([
                        'submission_id' => $submission->id,
                        'score' => fake()->numberBetween(60, 100),
                        'feedback' => fake()->sentence(),
                    ]);
                }
            }
        }
    }
}
