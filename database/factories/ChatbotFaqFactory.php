<?php

namespace Database\Factories;

use App\Models\ChatbotFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatbotFaq>
 */
class ChatbotFaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence().'?',
            'answer' => $this->faker->paragraph(),
            'position' => 0,
            'is_active' => true,
        ];
    }
}
