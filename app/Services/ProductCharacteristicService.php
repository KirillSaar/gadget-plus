<?php

namespace App\Services;

use App\Models\Characteristic;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/** Работа со связью «многие ко многим» товар — характеристика (таблица characteristic_product). */
class ProductCharacteristicService
{
    public function list(Product $product): Collection
    {
        return $product->characteristics()->orderBy('characteristics.id')->get();
    }

    /** sync: заменяет весь набор характеристик товара переданным. */
    public function sync(Product $product, array $items): Collection
    {
        $data = [];

        foreach ($items as $item) {
            $data[$item['id']] = ['value' => $item['value']];
        }

        $product->characteristics()->sync($data);

        return $this->list($product);
    }

    /** attach: добавляет характеристику или обновляет её значение, остальные не трогает. */
    public function attach(Product $product, int $characteristicId, string $value): Collection
    {
        $product->characteristics()->syncWithoutDetaching([
            $characteristicId => ['value' => $value],
        ]);

        return $this->list($product);
    }

    /** detach: убирает одну характеристику у товара. */
    public function detach(Product $product, Characteristic $characteristic): void
    {
        abort_unless(
            $product->characteristics()->where('characteristics.id', $characteristic->id)->exists(),
            404,
            'У товара нет такой характеристики.'
        );

        $product->characteristics()->detach($characteristic->id);
    }
}
