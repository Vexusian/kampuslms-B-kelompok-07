<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Users
        |--------------------------------------------------------------------------
        */

        // 1 admin
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Kampus',
            'email' => 'admin@kampuslms.test',
            'password' => 'password',
        ]);

        // 3 dosen, termasuk akun demo
        $dosenDemo = User::factory()->dosen()->create([
            'name' => 'Dosen Demo',
            'email' => 'dosen@kampuslms.test',
            'password' => 'password',
        ]);

        $dosenLain = User::factory()->count(2)->create([
            'role' => 'dosen',
        ]);

        $dosen = collect([$dosenDemo])->merge($dosenLain);

        // 30 mahasiswa, termasuk akun demo
        $mahasiswaDemo = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@kampuslms.test',
            'password' => 'password',
        ]);

        $mahasiswaLain = User::factory()->count(29)->create([
            'role' => 'mahasiswa',
        ]);

        $mahasiswa = collect([$mahasiswaDemo])->merge($mahasiswaLain);

        /*
        |--------------------------------------------------------------------------
        | 2. Courses
        |--------------------------------------------------------------------------
        */

        $courses = Course::factory()
            ->count(5)
            ->sequence(
                fn ($sequence) => [
                    'lecturer_id' => $dosen[$sequence->index % $dosen->count()]->id,
                ]
            )
            ->create();

        /*
        |--------------------------------------------------------------------------
        | 3. Enrollment
        |--------------------------------------------------------------------------
        */

        foreach ($courses as $course) {
            $students = $mahasiswa->random(15);

            $course->students()->attach(
                $students->mapWithKeys(fn ($student) => [
                    $student->id => [
                        'enrolled_at' => now(),
                    ],
                ])->toArray()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Materials, Assignments, Submissions, Grades
        |--------------------------------------------------------------------------
        */

        foreach ($courses as $course) {

            // 3 materi per course
            Material::factory()
                ->count(3)
                ->create([
                    'course_id' => $course->id,
                ]);

            /*
             * 3 assignment:
             * 1. sudah lewat deadline
             * 2. aktif
             * 3. draft
             */

            $pastAssignment = Assignment::factory()
                ->past()
                ->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);

            $activeAssignment = Assignment::factory()
                ->active()
                ->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);

            $draftAssignment = Assignment::factory()
                ->draft()
                ->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);

            $assignments = collect([
                $pastAssignment,
                $activeAssignment,
                $draftAssignment,
            ]);

            /*
             * Buat submission untuk setiap mahasiswa
             * pada setiap assignment.
             */

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

                    // Sekitar 60% submission diberi nilai
                    if (fake()->boolean(60)) {
                        Grade::create([
                            'submission_id' => $submission->id,
                            'graded_by' => $course->lecturer_id,
                            'score' => fake()->numberBetween(60, 100),
                            'feedback' => fake()->sentence(),
                            'graded_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}