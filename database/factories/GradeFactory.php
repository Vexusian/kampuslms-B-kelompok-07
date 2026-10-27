<?php

namespace Database\Factories;

use App\Models\User;
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
            'graded_by' => User::factory(),
            'score' => fake()->numberBetween(60, 100),
            'feedback' => fake()->optional()->sentence(),
            'graded_at' => now(),
        ];
    }
}