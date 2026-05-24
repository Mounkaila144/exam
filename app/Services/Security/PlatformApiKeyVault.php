<?php

namespace App\Services\Security;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Crypt;

class PlatformApiKeyVault
{
    public const KEY_CLAUDE_API = 'claude.api_key';
    public const KEY_CLAUDE_MODEL = 'claude.model';

    public function setKey(string $key, int $actorId): void
    {
        $this->setSetting(self::KEY_CLAUDE_API, $key, $actorId);
    }

    public function getDecryptedKey(): ?string
    {
        return $this->getSetting(self::KEY_CLAUDE_API);
    }

    public function setModel(string $model, int $actorId): void
    {
        $this->setSetting(self::KEY_CLAUDE_MODEL, $model, $actorId, encrypt: false);
    }

    public function getModel(): string
    {
        return $this->getSetting(self::KEY_CLAUDE_MODEL, encrypted: false)
            ?? config('claude.default_model');
    }

    public function hasKey(): bool
    {
        return PlatformSetting::where('key', self::KEY_CLAUDE_API)
            ->whereNotNull('encrypted_value')
            ->exists();
    }

    private function setSetting(string $key, string $value, int $actorId, bool $encrypt = true): void
    {
        PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'encrypted_value' => $encrypt ? Crypt::encryptString($value) : $value,
                'updated_by' => $actorId,
            ]
        );
    }

    private function getSetting(string $key, bool $encrypted = true): ?string
    {
        $setting = PlatformSetting::where('key', $key)->first();

        if (! $setting || ! $setting->encrypted_value) {
            return null;
        }

        return $encrypted
            ? Crypt::decryptString($setting->encrypted_value)
            : $setting->encrypted_value;
    }
}
