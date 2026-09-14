<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SI25####'),
            'name' => fake()->randomElement([
                'Basis Data',
                'Pemrograman Web',
                'Analisis dan Perancangan Sistem',
                'Statistika untuk Sistem Informasi',
                'Perencanaan Strategi SI/TI',
                'Manajemen Proyek Sistem Informasi',
                'Arsitektur Enterprise',
                'Business Intelligence',
            ]),
            'description' => fake()->sentence(12),
            'sks' => fake()->randomElement([2, 3, 4]),
            'lecturer_id' => null,
            'status' => 'active',
        ];
    }
}