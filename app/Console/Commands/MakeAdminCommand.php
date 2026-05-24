<?php

namespace App\Console\Commands;

use App\Domain\User\UserRole;
use App\Domain\User\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeAdminCommand extends Command
{
    protected $signature = 'admin:make
        {--email= : Email of the admin}
        {--name= : Full name of the admin}
        {--random-password : Generate a random password and show it once}
        {--force : Allow creating an admin even if one already exists}';

    protected $description = 'Create the platform administrator account.';

    public function handle(): int
    {
        if (User::admins()->exists() && ! $this->option('force')) {
            $this->error('An admin already exists. Pass --force to create another one.');

            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask('Email');
        $name = $this->option('name') ?: $this->ask('Name');

        if (User::where('email', $email)->exists()) {
            $this->error("User {$email} already exists.");

            return self::FAILURE;
        }

        if ($this->option('random-password')) {
            $password = Str::random(20);
            $this->warn('Generated password (shown once): '.$password);
        } else {
            $password = $this->secret('Password');
            $confirm = $this->secret('Confirm password');
            if ($password !== $confirm) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
