<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BrandService
{
    public function all(): Collection
    {
        return Brand::orderBy('name')->get();
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw new ConflictHttpException('Нельзя удалить бренд, у которого есть товары.');
        }

        $brand->delete();
    }
}
