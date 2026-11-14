<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    private function roleOf(User $user): ?string
    {
        return $user->role instanceof \BackedEnum ? $user->role->value : $user->role;
    }

    private function isStaff(User $user): bool
    {
        return in_array($this->roleOf($user), ['admin', 'manager'], true);
    }

    private function isAdmin(User $user): bool
    {
        return $this->roleOf($user) === 'admin';
    }

    public function viewAny(User $user): bool
    {
        return true; // в списке пользователь видит только свои заказы (фильтр в сервисе)
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isStaff($user) || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isAdmin($user) || $order->user_id === $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->isAdmin($user) || $order->user_id === $user->id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }
}
