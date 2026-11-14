<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index(Request $request)
    {
        $items = $this->cart->items($request->user());

        return CartItemResource::collection($items)
            ->additional(['total' => $this->cart->total($items)]);
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->cart->add($user, $request->validated('product_id'), $request->validated('quantity'));

        $items = $this->cart->items($user);

        return CartItemResource::collection($items)
            ->additional(['total' => $this->cart->total($items)])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Product $product): Response
    {
        $this->cart->remove($request->user(), $product);

        return response()->noContent();
    }

    public function clear(Request $request): Response
    {
        $this->cart->clear($request->user());

        return response()->noContent();
    }
}
