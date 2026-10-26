<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'total' => 0, // итог пересчитывается после добавления позиций
            'address' => fake()->randomElement(['Астана', 'Алматы', 'Шымкент'])
                . ', ул. ' . fake()->lastName() . ', д. ' . fake()->numberBetween(1, 120),
            'phone' => '+7701' . fake()->numerify('#######'),
        ];
    }
}
