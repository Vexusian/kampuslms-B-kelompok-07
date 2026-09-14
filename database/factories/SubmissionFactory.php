<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => null,
            'user_id' => null,
            'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
            'content' => fake()->optional()->paragraph(),
            'submitted_at' => now(),
        ];
    }
}