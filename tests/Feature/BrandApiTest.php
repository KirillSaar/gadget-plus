<?php

namespace Tests\Feature;

use App\Models\{Brand, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandApiTest extends TestCase
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

    public function test_guest_can_list_brands(): void
    {
        Brand::factory()->count(3)->create();

        $this->getJson('/api/v1/brands')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_brand(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/v1/brands', ['name' => 'Xiaomi', 'slug' => 'xiaomi'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Xiaomi');

        $this->assertDatabaseHas('brands', ['slug' => 'xiaomi']);
    }

    public function test_customer_cannot_create_brand(): void
    {
        $this->actAsCustomer();

        $this->postJson('/api/v1/brands', ['name' => 'Xiaomi', 'slug' => 'xiaomi'])
            ->assertForbidden();
    }

    public function test_guest_cannot_create_brand(): void
    {
        $this->postJson('/api/v1/brands', ['name' => 'Xiaomi', 'slug' => 'xiaomi'])
            ->assertUnauthorized();
    }

    public function test_brand_name_must_be_unique(): void
    {
        $this->actAsAdmin();
        Brand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);

        $this->postJson('/api/v1/brands', ['name' => 'Sony', 'slug' => 'sony-2'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_brand(): void
    {
        $this->actAsAdmin();
        $brand = Brand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);

        $this->patchJson("/api/v1/brands/{$brand->id}", ['name' => 'Sony Group'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sony Group');
    }

    public function test_cannot_delete_brand_with_products(): void
    {
        $this->actAsAdmin();
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/brands/{$product->brand_id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('brands', ['id' => $product->brand_id]);
    }

    public function test_admin_can_delete_empty_brand(): void
    {
        $this->actAsAdmin();
        $brand = Brand::factory()->create();

        $this->deleteJson("/api/v1/brands/{$brand->id}")->assertNoContent();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_missing_brand_returns_404(): void
    {
        $this->getJson('/api/v1/brands/99999')->assertNotFound();
    }
}
