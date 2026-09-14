<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => null,
            'graded_by' => null,
            'score' => fake()->randomFloat(2, 60, 100),
            'feedback' => fake()->optional()->sentence(),
            'graded_at' => now(),
        ];
    }
}