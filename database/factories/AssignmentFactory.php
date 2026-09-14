<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_at' => now()->addDays(fake()->numberBetween(1, 14)),
        ];
    }
}