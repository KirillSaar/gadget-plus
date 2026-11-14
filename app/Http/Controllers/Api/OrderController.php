<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeOrderStatusRequest;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Resources\OrderApiResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(OrderIndexRequest $request)
    {
        Gate::authorize('viewAny', Order::class);

        return OrderApiResource::collection(
            $this->orders->paginate($request->user(), $request->validated())
        );
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        Gate::authorize('create', Order::class);

        $order = $this->orders->checkout($request->user(), $request->validated());

        return (new OrderApiResource($order))->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderApiResource
    {
        Gate::authorize('view', $order);

        return new OrderApiResource($order->load(['user', 'items.product']));
    }

    public function cancel(Order $order): OrderApiResource
    {
        Gate::authorize('cancel', $order);

        return new OrderApiResource($this->orders->cancel($order));
    }

    public function updateStatus(ChangeOrderStatusRequest $request, Order $order): OrderApiResource
    {
        $status = OrderStatus::from($request->validated('status'));

        return new OrderApiResource($this->orders->changeStatus($order, $status));
    }
}
