<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer->value)->get();
        $products = Product::where('is_active', true)->get();

        for ($i = 0; $i < 10; $i++) {
            $order = Order::factory()->create(['user_id' => $customers->random()->id]);
            $total = 0;

            foreach ($products->random(fake()->numberBetween(1, 3)) as $product) {
                $quantity = fake()->numberBetween(1, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price, // цена фиксируется на момент покупки
                ]);

                $total += $quantity * $product->price;
            }

            $order->update(['total' => $total]);
        }
    }
}
