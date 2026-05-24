<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate {--force : Overwrite existing keys}';

    protected $description = 'Generate a VAPID key pair for Web Push and write it into the .env file.';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env file not found. Run `cp .env.example .env` first.');

            return self::FAILURE;
        }

        $env = file_get_contents($envPath);

        $hasKeys = preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $env)
            && preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $env);

        if ($hasKeys && ! $this->option('force')) {
            $this->error('VAPID keys already exist. Pass --force to overwrite.');

            return self::FAILURE;
        }

        $keys = VAPID::createVapidKeys();

        $env = $this->replaceEnvVar($env, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
        $env = $this->replaceEnvVar($env, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

        file_put_contents($envPath, $env);

        $this->info('VAPID keys generated and stored in .env.');
        $this->line('Public key:  '.$keys['publicKey']);
        $this->line('Private key: (stored in .env)');

        return self::SUCCESS;
    }

    private function replaceEnvVar(string $env, string $key, string $value): string
    {
        $line = $key.'='.$value;

        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $env)) {
            return preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $env);
        }

        return rtrim($env, "\n")."\n".$line."\n";
    }
}
