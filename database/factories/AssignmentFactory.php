<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'course_id' => null,
            'created_by' => null,
            'title' => fake()->sentence(4),
            'instructions' => fake()->paragraph(),
            'due_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ];
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'due_at' => now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'due_at' => now()->addDays(fake()->numberBetween(1, 14)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}