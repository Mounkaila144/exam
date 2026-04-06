<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@exam.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
            ]
        );
    }
}
