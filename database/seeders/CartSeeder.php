<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /** Корзины: у каждого покупателя 1–3 случайных товара. */
    public function run(): void
    {
        $products = Product::all();

        User::where('role', UserRole::Customer->value)->get()->each(function (User $user) use ($products) {
            foreach ($products->random(fake()->numberBetween(1, 3)) as $product) {
                $user->cartProducts()->attach($product->id, ['quantity' => fake()->numberBetween(1, 3)]);
            }
        });
    }
}
