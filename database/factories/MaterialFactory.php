<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3), // Menghasilkan kalimat acak, misal: "Pengantar Basis Data"
            'content' => fake()->paragraphs(3, true), // Menghasilkan 3 paragraf teks
        ];
    }
}
