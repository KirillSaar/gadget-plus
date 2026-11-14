<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    // ---------- корзина ----------

    public function test_user_can_add_product_to_cart(): void
    {
        $user = $this->signIn();
        $product = $this->makeProduct(['stock' => 10]);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated();

        $this->assertDatabaseHas('product_user', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_add_more_than_stock_to_cart(): void
    {
        $user = $this->signIn();
        $product = $this->makeProduct(['stock' => 3]);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(409);

        $this->assertDatabaseMissing('product_user', ['user_id' => $user->id]);
    }

    public function test_remove_product_not_in_cart_returns_404(): void
    {
        $this->signIn();
        $product = $this->makeProduct();

        $this->deleteJson('/api/v1/cart/items/'.$product->id)->assertNotFound();
    }

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/v1/cart')->assertUnauthorized();
    }

    // ---------- оформление заказа ----------

    public function test_checkout_creates_order_and_decreases_stock(): void
    {
        $user = $this->signIn();
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);
        $this->addToCart($user, $product, 2);

        $this->postJson('/api/v1/orders', $this->orderPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'new']);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseMissing('product_user', ['user_id' => $user->id]);
    }

    public function test_checkout_with_empty_cart_returns_409(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/orders', $this->orderPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Корзина пуста: нельзя оформить заказ');
    }

    public function test_checkout_validation_errors(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address', 'phone']);
    }

    public function test_checkout_fails_when_stock_dropped_and_rolls_back(): void
    {
        $user = $this->signIn();
        $first = $this->makeProduct(['stock' => 5]);
        $second = $this->makeProduct(['stock' => 10]);
        $this->addToCart($user, $first, 1);
        $this->addToCart($user, $second, 5);

        // после добавления в корзину остаток второго товара уменьшился
        Product::whereKey($second->id)->update(['stock' => 1]);

        $this->postJson('/api/v1/orders', $this->orderPayload())
            ->assertStatus(409);

        // транзакция откатилась: остаток первого товара не списан, заказа нет, корзина цела
        $this->assertSame(5, $first->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('product_user', ['user_id' => $user->id, 'product_id' => $first->id]);
    }

    public function test_checkout_fails_for_inactive_product(): void
    {
        $user = $this->signIn();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);
        Product::whereKey($product->id)->update(['is_active' => false]);

        $this->postJson('/api/v1/orders', $this->orderPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Товар недоступен для заказа');
    }

    // ---------- список и доступ к заказам ----------

    public function test_customer_sees_only_own_orders(): void
    {
        $me = $this->signIn();
        $this->makeOrder($me);
        $this->makeOrder($this->makeUser());
        $this->makeOrder($this->makeUser());

        $this->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_sees_all_orders(): void
    {
        $this->makeOrder($this->makeUser());
        $this->makeOrder($this->makeUser());
        $this->signIn('admin');

        $this->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_orders_filter_by_status_and_invalid_status(): void
    {
        $me = $this->signIn();
        $this->makeOrder($me, 'new');
        $this->makeOrder($me, 'paid');

        $this->getJson('/api/v1/orders?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');

        $this->getJson('/api/v1/orders?status=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_customer_cannot_view_foreign_order(): void
    {
        $this->signIn();
        $foreign = $this->makeOrder($this->makeUser());

        $this->getJson('/api/v1/orders/'.$foreign->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ запрещён');
    }

    public function test_owner_and_manager_can_view_order(): void
    {
        $owner = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->makeOrder($owner, 'new', [[$product, 2]]);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $this->getJson('/api/v1/orders/'.$order->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->signIn('manager');
        $this->getJson('/api/v1/orders/'.$order->id)->assertOk();
    }

    public function test_show_missing_order_returns_404(): void
    {
        $this->signIn();

        $this->getJson('/api/v1/orders/9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Заказ не найден');
    }

    // ---------- статусы и отмена ----------

    public function test_manager_changes_status_in_allowed_order(): void
    {
        $order = $this->makeOrder($this->makeUser(), 'new');
        $this->signIn('manager');

        $this->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_status_cannot_skip_steps(): void
    {
        $order = $this->makeOrder($this->makeUser(), 'new');
        $this->signIn('manager');

        $this->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Нельзя изменить статус заказа с «Новый» на «Доставлен»');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'new']);
    }

    public function test_customer_cannot_change_status(): void
    {
        $me = $this->signIn();
        $order = $this->makeOrder($me, 'new');

        $this->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertForbidden();
    }

    public function test_invalid_status_value_returns_422(): void
    {
        $order = $this->makeOrder($this->makeUser(), 'new');
        $this->signIn('manager');

        $this->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cancel_returns_stock(): void
    {
        $me = $this->signIn();
        $product = $this->makeProduct(['stock' => 8]);
        $order = $this->makeOrder($me, 'new', [[$product, 2]]);

        $this->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_cannot_cancel_delivered_order(): void
    {
        $me = $this->signIn();
        $order = $this->makeOrder($me, 'delivered');

        $this->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Нельзя отменить заказ в статусе «Доставлен»');
    }

    public function test_cannot_cancel_foreign_order(): void
    {
        $this->signIn();
        $foreign = $this->makeOrder($this->makeUser(), 'new');

        $this->postJson("/api/v1/orders/{$foreign->id}/cancel")->assertForbidden();
    }
}
