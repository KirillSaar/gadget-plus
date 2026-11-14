<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacteristicRequest;
use App\Http\Requests\UpdateCharacteristicRequest;
use App\Http\Resources\CharacteristicResource;
use App\Models\Characteristic;
use App\Services\CharacteristicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CharacteristicController extends Controller
{
    public function __construct(private readonly CharacteristicService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return CharacteristicResource::collection($this->service->all());
    }

    public function show(Characteristic $characteristic): CharacteristicResource
    {
        return new CharacteristicResource($characteristic);
    }

    public function store(StoreCharacteristicRequest $request): JsonResponse
    {
        $characteristic = $this->service->create($request->validated());

        return (new CharacteristicResource($characteristic))->response()->setStatusCode(201);
    }

    public function update(UpdateCharacteristicRequest $request, Characteristic $characteristic): CharacteristicResource
    {
        return new CharacteristicResource($this->service->update($characteristic, $request->validated()));
    }

    public function destroy(Characteristic $characteristic): Response
    {
        $this->service->delete($characteristic);

        return response()->noContent();
    }
}
