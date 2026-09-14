<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $admin = User::factory()
            ->admin()
            ->create([
                'name' => 'Administrator Kampus',
                'email' => 'admin@kampuslms.test',
                'password' => 'password',
            ]);

        $dosen = User::factory()
            ->dosen()
            ->count(3)
            ->create();

        $mahasiswa = User::factory()
            ->mahasiswa()
            ->count(30)
            ->create();

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */

        $courses = Course::factory()
            ->count(5)
            ->create([
                'lecturer_id' => $dosen->random()->id,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Enroll students
        |--------------------------------------------------------------------------
        |
        | Setiap mata kuliah memiliki minimal 15 mahasiswa.
        |
        */

        foreach ($courses as $course) {
            $courseStudents = $mahasiswa
                ->shuffle()
                ->take(15);

            $course->students()->attach(
                $courseStudents->pluck('id')->mapWithKeys(
                    fn ($studentId) => [
                        $studentId => [
                            'enrolled_at' => now()->subDays(
                                fake()->numberBetween(1, 30)
                            ),
                        ],
                    ]
                )->all()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Assignments
        |--------------------------------------------------------------------------
        |
        | Setiap course memiliki:
        | - 1 tugas yang sudah lewat deadline
        | - 1 tugas aktif
        | - 1 tugas draft
        |
        */

        foreach ($courses as $course) {
            $course->assignments()->createMany([
                [
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 1 - Analisis Materi',
                    'instructions' => 'Kerjakan analisis berdasarkan materi pertemuan sebelumnya.',
                    'due_at' => now()->subDays(7),
                    'max_score' => 100,
                    'allow_late' => true,
                    'status' => 'published',
                ],
                [
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 2 - Studi Kasus',
                    'instructions' => 'Kerjakan studi kasus yang telah diberikan.',
                    'due_at' => now()->addDays(7),
                    'max_score' => 100,
                    'allow_late' => true,
                    'status' => 'published',
                ],
                [
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 3 - Proyek',
                    'instructions' => 'Persiapkan rancangan proyek sesuai topik mata kuliah.',
                    'due_at' => now()->addDays(14),
                    'max_score' => 100,
                    'allow_late' => false,
                    'status' => 'draft',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Submissions
        |--------------------------------------------------------------------------
        |
        | Submission hanya dibuat untuk mahasiswa yang memang
        | terdaftar pada course dari assignment tersebut.
        |
        */

        $submissionCount = 0;
        $targetSubmissions = 120;

        foreach ($courses as $course) {
            $students = $course->students()->get();

            foreach ($course->assignments as $assignment) {

                if ($assignment->status === 'draft') {
                    continue;
                }

                foreach ($students as $student) {

                    if ($submissionCount >= $targetSubmissions) {
                        break 3;
                    }

                    $isLate = fake()->boolean(20);

                    $submittedAt = $isLate
                        ? $assignment->due_at->copy()->addDays(
                            fake()->numberBetween(1, 3)
                        )
                        : $assignment->due_at->copy()->subDays(
                            fake()->numberBetween(1, 5)
                        );

                    Submission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $student->id,
                        'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
                        'original_name' => 'jawaban-' . fake()->uuid() . '.pdf',
                        'file_size' => fake()->numberBetween(10000, 5000000),
                        'note' => fake()->optional()->sentence(),
                        'submitted_at' => $submittedAt,
                        'is_late' => $isLate,
                    ]);

                    $submissionCount++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Grades
        |--------------------------------------------------------------------------
        |
        | Sekitar 60% submission diberi nilai.
        |
        */

        $submissions = Submission::all();
        $gradedSubmissions = $submissions->shuffle()
            ->take((int) round($submissions->count() * 0.60));

        foreach ($gradedSubmissions as $submission) {
            $assignment = $submission->assignment;

            Grade::create([
                'submission_id' => $submission->id,
                'graded_by' => $assignment->created_by,
                'score' => fake()->randomFloat(2, 60, 100),
                'feedback' => fake()->sentence(),
                'graded_at' => $submission->submitted_at->copy()->addDays(
                    fake()->numberBetween(1, 3)
                ),
            ]);
        }
    }
}