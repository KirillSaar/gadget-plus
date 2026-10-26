<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Samsung', 'Apple', 'ASUS', 'Lenovo', 'Sony'] as $name) {
            Brand::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
    }
}
