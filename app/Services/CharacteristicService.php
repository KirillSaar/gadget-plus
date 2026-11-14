<?php

namespace App\Services;

use App\Models\Characteristic;
use Illuminate\Database\Eloquent\Collection;

class CharacteristicService
{
    public function all(): Collection
    {
        return Characteristic::orderBy('name')->get();
    }

    public function create(array $data): Characteristic
    {
        return Characteristic::create($data);
    }

    public function update(Characteristic $characteristic, array $data): Characteristic
    {
        $characteristic->update($data);

        return $characteristic;
    }

    /** Значения у товаров удаляются каскадно (внешний ключ cascadeOnDelete). */
    public function delete(Characteristic $characteristic): void
    {
        $characteristic->delete();
    }
}
