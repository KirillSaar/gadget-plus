<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return BrandResource::collection($this->service->all());
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->service->create($request->validated());

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        return new BrandResource($this->service->update($brand, $request->validated()));
    }

    public function destroy(Brand $brand): Response
    {
        $this->service->delete($brand);

        return response()->noContent();
    }
}
