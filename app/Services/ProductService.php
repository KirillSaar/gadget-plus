<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Product::with(['category', 'brand'])
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        $product = Product::create($data);

        return $product->load(['category', 'brand']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->load(['category', 'brand']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}