<?php

namespace Database\Factories;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNotification>
 */
class AdminNotificationFactory extends Factory
{
    protected $model = AdminNotification::class;

    public function definition(): array
    {
        return [
            'type' => 'test_event',
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'link' => null,
            'feature' => null,
            'read_at' => null,
        ];
    }
}
