<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    public function test_admin_gets_statistics(): void
    {
        $customer = $this->makeUser();
        $product = $this->makeProduct(['price' => 1000]);
        $this->makeOrder($customer, 'paid', [[$product, 2]]);
        $this->makeOrder($customer, 'new', [[$product, 1]]);

        $this->signIn('admin');

        $this->getJson('/api/v1/stats/overview')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'orders_by_status', 'revenue', 'paid_orders_count',
                'average_order_value', 'popular_products', 'products_by_category',
            ]])
            ->assertJsonPath('data.revenue', 2000)
            ->assertJsonPath('data.paid_orders_count', 1)
            ->assertJsonPath('data.popular_products.0.id', $product->id)
            ->assertJsonPath('data.popular_products.0.sold_quantity', 2);
    }

    public function test_customer_cannot_view_statistics(): void
    {
        $this->signIn('customer');

        $this->getJson('/api/v1/stats/overview')->assertForbidden();
    }

    public function test_statistics_period_validation(): void
    {
        $this->signIn('admin');

        $this->getJson('/api/v1/stats/overview?date_from=2026-12-31&date_to=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }
}
