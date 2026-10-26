<?php

namespace Database\Seeders;

use App\Models\Characteristic;
use Illuminate\Database\Seeder;

class CharacteristicSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Объём памяти', 'ГБ'],
            ['Диагональ экрана', 'дюйм'],
            ['Оперативная память', 'ГБ'],
            ['Цвет', null],
            ['Время работы', 'ч'],
        ];

        foreach ($items as [$name, $unit]) {
            Characteristic::create(['name' => $name, 'unit' => $unit]);
        }
    }
}
