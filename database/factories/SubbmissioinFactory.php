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
            'original_name' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'note' => fake()->optional()->sentence(),
            'submitted_at' => now(),
            'is_late' => false,
        ];
    }
}