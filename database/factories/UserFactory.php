<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
            'nim_nip' => fake()->unique()->numerify('10########'),
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'nim_nip' => null,
        ]);
    }

    public function dosen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'dosen',
            'nim_nip' => fake()->unique()->numerify('19########'),
        ]);
    }

    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mahasiswa',
            'nim_nip' => fake()->unique()->numerify('10########'),
        ]);
    }
}