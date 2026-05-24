<?php

namespace App\Domain\User;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::DISABLED => 'Désactivé',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::DISABLED => 'bg-red-100 text-red-800',
        };
    }
}
