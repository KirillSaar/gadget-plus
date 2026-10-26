<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Characteristic;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // [категория, бренд, название, цена в тенге, остаток, [характеристика => значение]]
        $items = [
            ['Смартфоны', 'Samsung', 'Смартфон Galaxy S24', 399990, 15, ['Объём памяти' => '256', 'Диагональ экрана' => '6.2', 'Цвет' => 'Чёрный']],
            ['Смартфоны', 'Apple', 'Смартфон iPhone 15', 449990, 10, ['Объём памяти' => '128', 'Диагональ экрана' => '6.1', 'Цвет' => 'Синий']],
            ['Ноутбуки', 'ASUS', 'Ноутбук ASUS Vivobook 15', 329990, 7, ['Оперативная память' => '16', 'Диагональ экрана' => '15.6', 'Объём памяти' => '512']],
            ['Ноутбуки', 'Lenovo', 'Ноутбук Lenovo IdeaPad 5', 289990, 5, ['Оперативная память' => '16', 'Диагональ экрана' => '14', 'Объём памяти' => '512']],
            ['Наушники', 'Sony', 'Наушники Sony WH-1000XM5', 149990, 20, ['Время работы' => '30', 'Цвет' => 'Серебристый']],
            ['Наушники', 'Samsung', 'Наушники Galaxy Buds2', 49990, 30, ['Время работы' => '20', 'Цвет' => 'Белый']],
            ['Планшеты', 'Apple', 'Планшет iPad 10', 229990, 8, ['Объём памяти' => '64', 'Диагональ экрана' => '10.9', 'Цвет' => 'Серебристый']],
            ['Планшеты', 'Samsung', 'Планшет Galaxy Tab S9', 269990, 0, ['Объём памяти' => '128', 'Диагональ экрана' => '11', 'Цвет' => 'Графит']],
            ['Аксессуары', 'Samsung', 'Зарядное устройство Samsung 45 Вт', 12990, 40, ['Цвет' => 'Чёрный']],
            ['Аксессуары', 'Apple', 'Чехол для iPhone 15', 14990, 25, ['Цвет' => 'Синий']],
        ];

        $characteristics = Characteristic::pluck('id', 'name');

        foreach ($items as [$category, $brand, $name, $price, $stock, $values]) {
            $product = Product::create([
                'category_id' => Category::where('name', $category)->firstOrFail()->id,
                'brand_id' => Brand::where('name', $brand)->firstOrFail()->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => fake()->realText(160),
                'price' => $price,
                'stock' => $stock,
                'is_active' => true,
            ]);

            foreach ($values as $characteristic => $value) {
                $product->characteristics()->attach($characteristics[$characteristic], ['value' => $value]);
            }
        }
    }
}
