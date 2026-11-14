<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс строки корзины: товар с pivot-полем quantity. */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quantity = (int) $this->pivot->quantity;

        return [
            'product_id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'quantity' => $quantity,
            'subtotal' => round($this->price * $quantity, 2),
        ];
    }
}
