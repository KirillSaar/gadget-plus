<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products)
    {
    }

    public function index(Request $request)
    {
        $items = $this->products->paginate(
            $request->only(['category_id', 'brand_id']),
            min($request->integer('per_page', 10), 50)
        );

        return ProductResource::collection($items);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'brand', 'characteristics']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource(
            $this->products->update($product, $request->validated())
        );
    }

    public function destroy(Product $product): Response|JsonResponse
    {
        try {
            $this->products->delete($product);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Нельзя удалить товар: он используется в заказах.',
            ], 409);
        }

        return response()->noContent();
    }
}