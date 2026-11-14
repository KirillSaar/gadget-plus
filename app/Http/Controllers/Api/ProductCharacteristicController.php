<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProductCharacteristicRequest;
use App\Http\Requests\SyncProductCharacteristicsRequest;
use App\Http\Resources\CharacteristicResource;
use App\Models\Characteristic;
use App\Models\Product;
use App\Services\ProductCharacteristicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductCharacteristicController extends Controller
{
    public function __construct(private readonly ProductCharacteristicService $service)
    {
    }

    public function index(Product $product): AnonymousResourceCollection
    {
        return CharacteristicResource::collection($this->service->list($product));
    }

    /** PUT: заменить весь набор характеристик товара (sync). */
    public function sync(SyncProductCharacteristicsRequest $request, Product $product): AnonymousResourceCollection
    {
        return CharacteristicResource::collection(
            $this->service->sync($product, $request->validated('characteristics'))
        );
    }

    /** POST: добавить или обновить одну характеристику (attach). */
    public function attach(AttachProductCharacteristicRequest $request, Product $product): JsonResponse
    {
        $items = $this->service->attach(
            $product,
            $request->validated('characteristic_id'),
            $request->validated('value')
        );

        return CharacteristicResource::collection($items)->response()->setStatusCode(201);
    }

    /** DELETE: убрать характеристику у товара (detach). */
    public function detach(Product $product, Characteristic $characteristic): Response
    {
        $this->service->detach($product, $characteristic);

        return response()->noContent();
    }
}
