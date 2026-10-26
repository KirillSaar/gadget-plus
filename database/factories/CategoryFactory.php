<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Смартфоны', 'Ноутбуки', 'Наушники', 'Планшеты', 'Аксессуары',
            'Мониторы', 'Умные часы', 'Телевизоры',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
