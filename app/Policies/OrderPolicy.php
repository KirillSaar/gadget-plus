<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    private function isStaff(User $user): bool
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        return in_array($role, ['admin', 'manager'], true);
    }

    private function isAdmin(User $user): bool
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        return $role === 'admin';
    }

    public function viewAny(User $user): bool
    {
        return true;
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