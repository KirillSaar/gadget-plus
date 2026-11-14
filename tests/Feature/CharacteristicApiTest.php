<?php

namespace Tests\Feature;

use App\Models\{Characteristic, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CharacteristicApiTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
    }

    private function actAsCustomer(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_guest_can_list_characteristics(): void
    {
        Characteristic::factory()->count(3)->create();

        $this->getJson('/api/v1/characteristics')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_characteristic(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/v1/characteristics', ['name' => 'Вес', 'unit' => 'г'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Вес');

        $this->assertDatabaseHas('characteristics', ['name' => 'Вес', 'unit' => 'г']);
    }

    public function test_customer_cannot_create_characteristic(): void
    {
        $this->actAsCustomer();

        $this->postJson('/api/v1/characteristics', ['name' => 'Вес'])->assertForbidden();
    }

    public function test_characteristic_requires_name(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/v1/characteristics', ['unit' => 'г'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_and_delete_characteristic(): void
    {
        $this->actAsAdmin();
        $characteristic = Characteristic::factory()->create(['name' => 'Вес', 'unit' => 'г']);

        $this->patchJson("/api/v1/characteristics/{$characteristic->id}", ['unit' => 'кг'])
            ->assertOk()
            ->assertJsonPath('data.unit', 'кг');

        $this->deleteJson("/api/v1/characteristics/{$characteristic->id}")->assertNoContent();

        $this->assertDatabaseMissing('characteristics', ['id' => $characteristic->id]);
    }

    public function test_deleting_characteristic_removes_values_from_products(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();
        $product->characteristics()->attach($characteristic->id, ['value' => '256']);

        $this->deleteJson("/api/v1/characteristics/{$characteristic->id}")->assertNoContent();

        $this->assertDatabaseMissing('characteristic_product', ['characteristic_id' => $characteristic->id]);
    }
}
