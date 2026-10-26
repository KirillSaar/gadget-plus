<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** Тестовые учётные записи: пароль у всех — password */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Админ Анна',
            'email' => 'admin@shop.kz',
        ]);
        User::factory()->manager()->create([
            'name' => 'Менеджер Марат',
            'email' => 'manager@shop.kz',
        ]);
        User::factory()->create([
            'name' => 'Покупатель Иван',
            'email' => 'customer@shop.kz',
        ]);

        User::factory(7)->create();
    }
}
