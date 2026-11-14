<?php

namespace Tests\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** Вспомогательные методы для создания тестовых данных (через фабрики). */
trait CreatesShopData
{
    protected function makeUser(string $role = 'customer', array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => $role], $attributes));
    }

    /** Создаёт пользователя нужной роли и авторизует его через Sanctum. */
    protected function signIn(string $role = 'customer'): User
    {
        $user = $this->makeUser($role);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->for(Category::factory())
            ->for(Brand::factory())
            ->create(array_merge(['price' => 1000, 'stock' => 10, 'is_active' => true], $attributes));
    }

    /** @param array<int, array{0: Product, 1: int}> $lines */
    protected function makeOrder(User $user, string $status = 'new', array $lines = []): Order
    {
        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'status' => $status,
            'total' => 0,
            'address' => 'г. Астана, ул. Тестовая, 1',
            'phone' => '+7 701 123 45 67',
        ])->save();

        $total = 0;
        foreach ($lines as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
            $total += $product->price * $quantity;
        }

        $order->forceFill(['total' => $total])->save();

        return $order;
    }

    protected function addToCart(User $user, Product $product, int $quantity = 1): void
    {
        $user->cartProducts()->attach($product->id, ['quantity' => $quantity]);
    }

    protected function orderPayload(): array
    {
        return ['address' => 'г. Астана, ул. Тестовая, 1', 'phone' => '+7 701 123 45 67'];
    }
}
