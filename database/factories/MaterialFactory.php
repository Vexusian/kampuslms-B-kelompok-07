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
            'title' => fake()->sentence(3),
            'type' => 'file',
            'content' => fake()->paragraphs(3, true),
        ];
    }

    public function link(?string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'link',
            'external_url' => $url ?? fake()->url(),
            'file_path' => null,
            'original_name' => null,
            'file_size' => null,
            'mime_type' => null,
        ]);
    }

    public function file(string $path = 'materials/dummy.pdf', string $originalName = 'dummy.pdf'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'file_path' => $path,
            'original_name' => $originalName,
            'file_size' => 1024 * 500,
            'mime_type' => 'application/pdf',
            'external_url' => null,
        ]);
    }
}
