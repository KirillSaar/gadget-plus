<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /** Белый список полей сортировки */
    public const SORTABLE = ['id', 'total', 'created_at'];

    private function isStaff(User $user): bool
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        return in_array($role, ['admin', 'manager'], true);
    }

    private function statusOf(Order $order): OrderStatus
    {
        return $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::from((string) $order->status);
    }

    /** Список заказов: покупатель видит только свои, admin и manager все. */
    public function paginate(User $user, array $params = []): LengthAwarePaginator
    {
        $sort = in_array($params['sort'] ?? null, self::SORTABLE, true) ? $params['sort'] : 'id';
        $direction = ($params['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Order::with('user')
            ->withCount('items')
            ->when(! $this->isStaff($user), fn ($q) => $q->where('user_id', $user->id))
            ->when($params['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($params['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($params['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate((int) ($params['per_page'] ?? 10))
            ->withQueryString();
    }

    /**
     * Ключевой сценарий: оформление заказа из корзины.
     * Всё выполняется в одной транзакции: при любой ошибке изменения откатываются.
     */
    public function checkout(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = DB::table('product_user')->where('user_id', $user->id)->get();

            // Правило 1: нельзя оформить заказ с пустой корзиной
            if ($cart->isEmpty()) {
                throw new BusinessException('Корзина пуста: нельзя оформить заказ');
            }

            // Блокируем строки товаров, чтобы параллельные заказы не списали один и тот же остаток
            $products = Product::whereIn('id', $cart->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = new Order();
            $order->forceFill([
                'user_id' => $user->id,
                'status' => OrderStatus::New->value,
                'total' => 0,
                'address' => $data['address'],
                'phone' => $data['phone'],
            ])->save();

            $total = 0;

            foreach ($cart as $row) {
                $product = $products->get($row->product_id);

                // Правило 2: нельзя заказать неактивный (снятый с продажи) товар
                if (! $product || ! $product->is_active) {
                    throw new BusinessException('Товар недоступен для заказа');
                }

                // Правило 3: нельзя заказать товара больше, чем есть на складе
                if ($row->quantity > $product->stock) {
                    throw new BusinessException(
                        "Недостаточно товара «{$product->name}» на складе: "
                        ."доступно {$product->stock}, в корзине {$row->quantity}"
                    );
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $row->quantity,
                    'price' => $product->price, // цена фиксируется на момент заказа
                ]);

                $product->decrement('stock', $row->quantity);
                $total += $product->price * $row->quantity;
            }

            $order->forceFill(['total' => round($total, 2)])->save();

            // Корзина очищается после успешного оформления
            DB::table('product_user')->where('user_id', $user->id)->delete();

            return $order->load(['user', 'items.product']);
        });
    }

    /** Отмена заказа с возвратом товара на склад. */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $current = $this->statusOf($order);

            // Правило 4: отменить можно только новый или оплаченный заказ
            if (! $current->canTransitionTo(OrderStatus::Cancelled)) {
                throw new BusinessException("Нельзя отменить заказ в статусе «{$current->label()}»");
            }

            foreach ($order->items as $item) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            $order->forceFill(['status' => OrderStatus::Cancelled->value])->save();

            return $order->load(['user', 'items.product']);
        });
    }

    /** Смена статуса (admin, manager) строго по допустимым переходам. */
    public function changeStatus(Order $order, OrderStatus $to): Order
    {
        if ($to === OrderStatus::Cancelled) {
            return $this->cancel($order);
        }

        return DB::transaction(function () use ($order, $to) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $current = $this->statusOf($order);

            // Правило 5: статус меняется только в допустимом порядке
            if (! $current->canTransitionTo($to)) {
                throw new BusinessException(
                    "Нельзя изменить статус заказа с «{$current->label()}» на «{$to->label()}»"
                );
            }

            $order->forceFill(['status' => $to->value])->save();

            return $order->load(['user', 'items.product']);
        });
    }
}
