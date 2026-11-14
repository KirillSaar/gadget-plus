<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class StatsService
{
    /** Статусы, которые считаются выручкой */
    private const REVENUE_STATUSES = ['paid', 'shipped', 'delivered'];

    private function ordersInPeriod(?string $from, ?string $to): Builder
    {
        return Order::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }

    public function overview(array $params = []): array
    {
        $from = $params['date_from'] ?? null;
        $to = $params['date_to'] ?? null;
        $limit = (int) ($params['limit'] ?? 5);

        // 1. Заказы по статусам: count + sum + groupBy (selectRaw)
        $rows = $this->ordersInPeriod($from, $to)
            ->selectRaw('status, COUNT(*) AS orders_count, SUM(total) AS total_sum')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($r) => $r->status instanceof OrderStatus ? $r->status->value : (string) $r->status);

        $byStatus = collect(OrderStatus::cases())->map(function (OrderStatus $s) use ($rows) {
            $row = $rows->get($s->value);

            return [
                'status' => $s->value,
                'label' => $s->label(),
                'orders_count' => (int) ($row->orders_count ?? 0),
                'total_sum' => round((float) ($row->total_sum ?? 0), 2),
            ];
        })->values();

        // 2. Выручка и средний чек (sum, avg, count) по оплаченным/отправленным/доставленным
        $revenueQuery = fn () => $this->ordersInPeriod($from, $to)->whereIn('status', self::REVENUE_STATUSES);

        // 3. Популярные товары: withSum по позициям заказов (без новых и отменённых)
        $inPeriod = function ($q) use ($from, $to) {
            $q->whereHas('order', function ($o) use ($from, $to) {
                $o->whereIn('status', self::REVENUE_STATUSES)
                    ->when($from, fn ($x) => $x->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($x) => $x->whereDate('created_at', '<=', $to));
            });
        };

        $popular = Product::withSum(['orderItems as sold_quantity' => $inPeriod], 'quantity')
            ->having('sold_quantity', '>', 0)
            ->orderByDesc('sold_quantity')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sold_quantity' => (int) $p->sold_quantity,
            ])->values();

        // 4. Количество товаров по категориям: withCount
        $categories = Category::withCount('products')
            ->orderByDesc('products_count')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'products_count' => $c->products_count,
            ])->values();

        return [
            'period' => ['date_from' => $from, 'date_to' => $to],
            'orders_by_status' => $byStatus,
            'revenue' => round((float) $revenueQuery()->sum('total'), 2),
            'paid_orders_count' => $revenueQuery()->count(),
            'average_order_value' => round((float) $revenueQuery()->avg('total'), 2),
            'popular_products' => $popular,
            'products_by_category' => $categories,
        ];
    }
}
