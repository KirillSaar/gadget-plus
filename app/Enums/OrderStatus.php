<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Paid => 'Оплачен',
            self::Shipped => 'Отправлен',
            self::Delivered => 'Доставлен',
            self::Cancelled => 'Отменён',
        };
    }

    /**
     * Допустимые переходы статусов:
     * новый -> оплачен -> отправлен -> доставлен;
     * отменить можно только новый или оплаченный заказ.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
