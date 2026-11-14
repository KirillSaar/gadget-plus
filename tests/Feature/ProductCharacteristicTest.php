<?php

namespace Tests\Feature;

use App\Models\{Characteristic, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCharacteristicTest extends TestCase
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

    public function test_guest_can_see_product_characteristics_with_values(): void
    {
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create(['name' => 'Объём памяти', 'unit' => 'ГБ']);
        $product->characteristics()->attach($characteristic->id, ['value' => '256']);

        $this->getJson("/api/v1/products/{$product->id}/characteristics")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.value', '256');
    }

    public function test_attach_adds_characteristic_to_product(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();

        $this->postJson("/api/v1/products/{$product->id}/characteristics", [
            'characteristic_id' => $characteristic->id,
            'value' => '512',
        ])->assertCreated();

        $this->assertDatabaseHas('characteristic_product', [
            'product_id' => $product->id,
            'characteristic_id' => $characteristic->id,
            'value' => '512',
        ]);
    }

    public function test_attach_updates_value_of_existing_characteristic(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();
        $product->characteristics()->attach($characteristic->id, ['value' => '128']);

        $this->postJson("/api/v1/products/{$product->id}/characteristics", [
            'characteristic_id' => $characteristic->id,
            'value' => '256',
        ])->assertCreated();

        $this->assertDatabaseCount('characteristic_product', 1);
        $this->assertDatabaseHas('characteristic_product', ['value' => '256']);
    }

    public function test_sync_replaces_all_characteristics(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        [$old, $keep, $new] = Characteristic::factory()->count(3)->create();
        $product->characteristics()->attach($old->id, ['value' => 'старое']);
        $product->characteristics()->attach($keep->id, ['value' => '1']);

        $this->putJson("/api/v1/products/{$product->id}/characteristics", [
            'characteristics' => [
                ['id' => $keep->id, 'value' => '2'],
                ['id' => $new->id, 'value' => '3'],
            ],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->assertDatabaseMissing('characteristic_product', ['characteristic_id' => $old->id]);
        $this->assertDatabaseHas('characteristic_product', ['characteristic_id' => $keep->id, 'value' => '2']);
        $this->assertDatabaseHas('characteristic_product', ['characteristic_id' => $new->id, 'value' => '3']);
    }

    public function test_sync_with_empty_list_removes_everything(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();
        $product->characteristics()->attach($characteristic->id, ['value' => '1']);

        $this->putJson("/api/v1/products/{$product->id}/characteristics", ['characteristics' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('characteristic_product', 0);
    }

    public function test_detach_removes_one_characteristic(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();
        $product->characteristics()->attach($characteristic->id, ['value' => '1']);

        $this->deleteJson("/api/v1/products/{$product->id}/characteristics/{$characteristic->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('characteristic_product', 0);
    }

    public function test_detach_of_missing_link_returns_404(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}/characteristics/{$characteristic->id}")
            ->assertNotFound();
    }

    public function test_attach_validates_input(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();

        $this->postJson("/api/v1/products/{$product->id}/characteristics", [
            'characteristic_id' => 99999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['characteristic_id', 'value']);
    }

    public function test_sync_rejects_duplicate_characteristics(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();
        $characteristic = Characteristic::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}/characteristics", [
            'characteristics' => [
                ['id' => $characteristic->id, 'value' => '1'],
                ['id' => $characteristic->id, 'value' => '2'],
            ],
        ])->assertUnprocessable();
    }

    public function test_customer_cannot_change_product_characteristics(): void
    {
        $this->actAsCustomer();
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}/characteristics", ['characteristics' => []])
            ->assertForbidden();
    }
}
