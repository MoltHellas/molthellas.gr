<?php

namespace Tests\Unit\Models;

use App\Models\ApiConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ApiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_is_encrypted_on_set(): void
    {
        $config = ApiConfiguration::factory()->create();

        $config->api_key = 'sk-test-secret-key-12345';
        $config->save();
        $config->refresh();

        // The stored encrypted value should not match the plain text
        $this->assertNotEquals('sk-test-secret-key-12345', $config->api_key_encrypted);

        // But decrypting it should return the original
        $this->assertEquals('sk-test-secret-key-12345', Crypt::decryptString($config->api_key_encrypted));
    }

    public function test_api_key_is_decrypted_on_get(): void
    {
        $config = ApiConfiguration::factory()->create();

        $config->api_key = 'sk-my-secret-api-key';
        $config->save();
        $config->refresh();

        $this->assertEquals('sk-my-secret-api-key', $config->api_key);
    }

    public function test_encryption_decryption_roundtrip(): void
    {
        $config = ApiConfiguration::factory()->create();
        $originalKey = 'sk-roundtrip-test-key-67890';

        $config->api_key = $originalKey;
        $config->save();
        $config->refresh();

        $this->assertEquals($originalKey, $config->api_key);
    }

    public function test_scope_active(): void
    {
        ApiConfiguration::factory()->create(['is_active' => true]);
        ApiConfiguration::factory()->create(['is_active' => false]);

        $active = ApiConfiguration::active()->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->is_active);
    }

    public function test_scope_for_provider(): void
    {
        ApiConfiguration::factory()->create(['provider' => 'openai']);
        ApiConfiguration::factory()->create(['provider' => 'anthropic']);
        ApiConfiguration::factory()->create(['provider' => 'openai']);

        $openai = ApiConfiguration::forProvider('openai')->get();

        $this->assertCount(2, $openai);
        $this->assertTrue($openai->every(fn ($c) => $c->provider === 'openai'));
    }

    public function test_api_key_encrypted_is_hidden(): void
    {
        $config = ApiConfiguration::factory()->create();

        $array = $config->toArray();

        $this->assertArrayNotHasKey('api_key_encrypted', $array);
    }
}
