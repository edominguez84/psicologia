<?php

namespace Database\Factories;

use App\Models\AppointmentSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentSlot>
 */
class AppointmentSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $end = (clone $start)->modify('+50 minutes');

        return [
            'starts_at' => $start,
            'ends_at' => $end,
            'is_active' => true,
        ];
    }
}
