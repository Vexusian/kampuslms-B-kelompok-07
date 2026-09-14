<?php

namespace Database\Factories;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'assignment_id' => null,
            'user_id' => null,
            'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
            'content' => fake()->paragraph(),
            'submitted_at' => now(),
        ];
    }
}