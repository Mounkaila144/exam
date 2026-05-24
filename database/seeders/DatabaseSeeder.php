<?php

namespace Database\Seeders;

use App\Domain\User\UserRole;
use App\Domain\User\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@examguard.local'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );
    }
}
