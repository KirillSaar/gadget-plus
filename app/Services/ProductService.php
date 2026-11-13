<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /** Белый список полей сортировки */
    public const SORTABLE = ['id', 'name', 'price', 'created_at'];

    public function paginate(array $params = []): LengthAwarePaginator
    {
        $sort = in_array($params['sort'] ?? null, self::SORTABLE, true) ? $params['sort'] : 'id';
        $order = ($params['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = (int) ($params['per_page'] ?? 10);

        return Product::with(['category', 'brand'])
            ->filter($params)
            ->search($params['search'] ?? null)
            ->orderBy($sort, $order)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
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