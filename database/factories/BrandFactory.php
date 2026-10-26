<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Samsung', 'Apple', 'ASUS', 'Lenovo', 'Sony', 'Xiaomi', 'Huawei', 'LG',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
