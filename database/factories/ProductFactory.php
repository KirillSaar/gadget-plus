<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Смартфон', 'Ноутбук', 'Наушники', 'Планшет', 'Монитор'])
            . ' ' . fake()->bothify('??-###');

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 999999),
            'description' => fake()->realText(150),
            'price' => fake()->numberBetween(9990, 599990),
            'stock' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }
}
