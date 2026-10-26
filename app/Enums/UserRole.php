<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Покупатель',
            self::Manager => 'Менеджер',
            self::Admin => 'Администратор',
        };
    }
}
