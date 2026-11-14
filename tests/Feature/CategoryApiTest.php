<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    public function test_guest_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_show_missing_category_returns_404(): void
    {
        $this->getJson('/api/v1/categories/9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Категория не найдена');
    }

    public function test_admin_can_create_category(): void
    {
        $this->signIn('admin');

        $this->postJson('/api/v1/categories', ['name' => 'Планшеты', 'slug' => 'planshety'])
            ->assertCreated();

        $this->assertDatabaseHas('categories', ['name' => 'Планшеты']);
    }

    public function test_duplicate_category_name_returns_422(): void
    {
        Category::factory()->create(['name' => 'Планшеты', 'slug' => 'planshety']);
        $this->signIn('admin');

        $this->postJson('/api/v1/categories', ['name' => 'Планшеты', 'slug' => 'planshety-2'])
            ->assertStatus(422);
    }

    public function test_manager_cannot_create_category(): void
    {
        $this->signIn('manager');

        $this->postJson('/api/v1/categories', ['name' => 'Планшеты', 'slug' => 'planshety'])
            ->assertForbidden();
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $product = $this->makeProduct();
        $this->signIn('admin');

        $this->deleteJson('/api/v1/categories/'.$product->category_id)
            ->assertStatus(409);

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }
}
