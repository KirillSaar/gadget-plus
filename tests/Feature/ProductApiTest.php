<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    private function payload(array $override = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'name' => 'Тестовый смартфон',
            'slug' => 'test-smartphone',
            'description' => 'Описание товара',
            'price' => 49990,
            'stock' => 15,
            'is_active' => true,
        ], $override);
    }

    // ---------- список, фильтры, поиск, сортировка, пагинация ----------

    public function test_guest_can_list_products(): void
    {
        $this->makeProduct();
        $this->makeProduct();
        $this->makeProduct();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_filter_by_category(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct();

        $this->getJson('/api/v1/products?category_id='.$category->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_by_price_range(): void
    {
        $this->makeProduct(['price' => 500]);
        $middle = $this->makeProduct(['price' => 1500]);
        $this->makeProduct(['price' => 9000]);

        $this->getJson('/api/v1/products?price_min=1000&price_max=2000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $middle->id);
    }

    public function test_search_by_name(): void
    {
        $found = $this->makeProduct(['name' => 'Смартфон iPhone 15']);
        $this->makeProduct(['name' => 'Ноутбук Lenovo']);

        $this->getJson('/api/v1/products?search=iPhone')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $found->id);
    }

    public function test_sort_by_price_desc(): void
    {
        $this->makeProduct(['price' => 100]);
        $expensive = $this->makeProduct(['price' => 300]);
        $this->makeProduct(['price' => 200]);

        $this->getJson('/api/v1/products?sort=price&order=desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $expensive->id);
    }

    public function test_pagination_per_page(): void
    {
        foreach (range(1, 5) as $i) {
            $this->makeProduct();
        }

        $this->getJson('/api/v1/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_invalid_list_parameters_return_422(): void
    {
        $this->getJson('/api/v1/products?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);

        $this->getJson('/api/v1/products?per_page=1000')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    // ---------- просмотр ----------

    public function test_show_product(): void
    {
        $product = $this->makeProduct();

        $this->getJson('/api/v1/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_show_missing_product_returns_404(): void
    {
        $this->getJson('/api/v1/products/9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Товар не найден');
    }

    // ---------- создание, права, валидация ----------

    public function test_admin_can_create_product(): void
    {
        $this->signIn('admin');

        $this->postJson('/api/v1/products', $this->payload())
            ->assertCreated();

        $this->assertDatabaseHas('products', ['name' => 'Тестовый смартфон', 'stock' => 15]);
    }

    public function test_create_product_validation_errors(): void
    {
        $this->signIn('admin');

        $this->postJson('/api/v1/products', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ошибка валидации')
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_guest_cannot_create_product(): void
    {
        $this->postJson('/api/v1/products', $this->payload())->assertUnauthorized();
    }

    public function test_customer_cannot_create_product(): void
    {
        $this->signIn('customer');

        $this->postJson('/api/v1/products', $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ запрещён');

        $this->assertDatabaseMissing('products', ['name' => 'Тестовый смартфон']);
    }

    // ---------- изменение и удаление ----------

    public function test_manager_can_update_product(): void
    {
        $this->signIn('manager');
        $product = $this->makeProduct();

        $this->putJson('/api/v1/products/'.$product->id, $this->payload([
            'name' => 'Новое название',
            'slug' => 'novoe-nazvanie',
            'price' => 12345,
        ]))->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Новое название']);
    }

    public function test_only_admin_can_delete_product(): void
    {
        $product = $this->makeProduct();

        $this->signIn('manager');
        $this->deleteJson('/api/v1/products/'.$product->id)->assertForbidden();

        $this->signIn('admin');
        $this->deleteJson('/api/v1/products/'.$product->id)->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_cannot_delete_product_used_in_orders(): void
    {
        $product = $this->makeProduct();
        $this->makeOrder($this->makeUser(), 'new', [[$product, 1]]);

        $this->signIn('admin');

        $this->deleteJson('/api/v1/products/'.$product->id)
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
