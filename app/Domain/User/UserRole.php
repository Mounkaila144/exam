<?php

namespace App\Domain\User;

enum UserRole: string
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::TEACHER => 'Professeur',
        };
    }
}
