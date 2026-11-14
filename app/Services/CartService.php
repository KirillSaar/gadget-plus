<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class CartService
{
    public function items(User $user): Collection
    {
        return $user->cartProducts()
            ->withPivot('quantity')
            ->orderBy('products.id')
            ->get();
    }

    public function total(Collection $items): float
    {
        return round($items->sum(fn ($p) => $p->price * $p->pivot->quantity), 2);
    }

    public function add(User $user, int $productId, int $quantity): void
    {
        $product = Product::findOrFail($productId);

        if (! $product->is_active) {
            throw new BusinessException('Товар недоступен для заказа');
        }

        if ($quantity > $product->stock) {
            throw new BusinessException(
                "Нельзя добавить {$quantity} шт.: на складе доступно {$product->stock}"
            );
        }

        $user->cartProducts()->syncWithoutDetaching([
            $productId => ['quantity' => $quantity],
        ]);
    }

    public function remove(User $user, Product $product): void
    {
        $inCart = $user->cartProducts()->where('products.id', $product->id)->exists();

        if (! $inCart) {
            throw new BusinessException('Этого товара нет в корзине', 404);
        }

        $user->cartProducts()->detach($product->id);
    }

    public function clear(User $user): void
    {
        $user->cartProducts()->detach();
    }
}
