<?php

namespace Tests\Feature\Security;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\Security\PlatformApiKeyVault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ApiKeyVaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_key_round_trips_through_crypt(): void
    {
        $admin = User::factory()->admin()->create();
        $vault = app(PlatformApiKeyVault::class);

        $vault->setKey('sk-ant-test-1234567890', $admin->id);

        $row = PlatformSetting::where('key', PlatformApiKeyVault::KEY_CLAUDE_API)->first();
        $this->assertNotNull($row);
        $this->assertNotSame('sk-ant-test-1234567890', $row->encrypted_value, 'Key must be stored encrypted, not in plain text');
        $this->assertSame('sk-ant-test-1234567890', Crypt::decryptString($row->encrypted_value));

        $this->assertSame('sk-ant-test-1234567890', $vault->getDecryptedKey());
        $this->assertTrue($vault->hasKey());
    }

    public function test_get_returns_null_when_no_key(): void
    {
        $vault = app(PlatformApiKeyVault::class);
        $this->assertNull($vault->getDecryptedKey());
        $this->assertFalse($vault->hasKey());
    }
}
