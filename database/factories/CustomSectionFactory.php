<?php

namespace Database\Factories;

use App\Models\CustomSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomSection>
 */
class CustomSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraphs(2, true),
            'image_path' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
