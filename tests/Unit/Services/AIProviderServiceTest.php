<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\ApiConfiguration;
use App\Services\AIProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Tests\TestCase;

class AIProviderServiceTest extends TestCase
{
    use RefreshDatabase;

    private AIProviderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AIProviderService();
    }

    /**
     * Helper to create an ApiConfiguration with a properly encrypted API key.
     */
    private function createApiConfig(string $provider, array $overrides = []): ApiConfiguration
    {
        $config = new ApiConfiguration(array_merge([
            'provider' => $provider,
            'api_key_encrypted' => Crypt::encryptString('test-api-key-' . $provider),
            'model_name' => null,
            'is_active' => true,
            'rate_limit_per_minute' => 60,
        ], $overrides));

        $config->save();

        return $config;
    }

    public function test_is_provider_available_returns_boolean(): void
    {
        // Without any API configuration, providers should not be available
        $result = $this->service->isProviderAvailable('anthropic');

        $this->assertIsBool($result);
    }

    public function test_is_provider_available_returns_true_when_configured(): void
    {
        $this->createApiConfig('anthropic', [
            'model_name' => 'claude-sonnet-4-20250514',
        ]);

        $result = $this->service->isProviderAvailable('anthropic');

        $this->assertTrue($result);
    }

    public function test_is_provider_available_returns_false_when_inactive(): void
    {
        $this->createApiConfig('mistral', [
            'model_name' => 'mistral-large',
            'is_active' => false,
            'rate_limit_per_minute' => 30,
        ]);

        $result = $this->service->isProviderAvailable('mistral');

        $this->assertFalse($result);
    }

    public function test_is_provider_available_returns_false_when_not_configured(): void
    {
        $result = $this->service->isProviderAvailable('google');

        $this->assertFalse($result);
    }

    public function test_get_provider_status_returns_array(): void
    {
        $this->createApiConfig('anthropic', [
            'model_name' => 'claude-sonnet-4-20250514',
        ]);

        Agent::factory()->create([
            'model_provider' => 'anthropic',
            'status' => 'active',
        ]);

        $status = $this->service->getProviderStatus();

        $this->assertIsArray($status);

        // Should have entries for all 6 providers
        $expectedProviders = ['openai', 'anthropic', 'google', 'meta', 'mistral', 'local'];
        foreach ($expectedProviders as $provider) {
            $this->assertArrayHasKey($provider, $status);
            $this->assertArrayHasKey('available', $status[$provider]);
            $this->assertArrayHasKey('model', $status[$provider]);
            $this->assertArrayHasKey('rate_limit', $status[$provider]);
            $this->assertArrayHasKey('agents_using', $status[$provider]);
            $this->assertIsBool($status[$provider]['available']);
            $this->assertIsInt($status[$provider]['rate_limit']);
            $this->assertIsInt($status[$provider]['agents_using']);
        }
    }

    public function test_get_provider_status_counts_agents_correctly(): void
    {
        $this->createApiConfig('anthropic');

        // Create 3 active agents using anthropic
        Agent::factory()->count(3)->create([
            'model_provider' => 'anthropic',
            'status' => 'active',
        ]);

        // Create 1 inactive agent using anthropic (should not be counted)
        Agent::factory()->inactive()->create([
            'model_provider' => 'anthropic',
        ]);

        // Create 2 active agents using openai
        Agent::factory()->count(2)->create([
            'model_provider' => 'openai',
            'status' => 'active',
        ]);

        $status = $this->service->getProviderStatus();

        $this->assertEquals(3, $status['anthropic']['agents_using']);
        $this->assertEquals(2, $status['openai']['agents_using']);
        $this->assertEquals(0, $status['google']['agents_using']);
    }

    public function test_send_prompt_throws_on_unsupported_provider(): void
    {
        // For providers that need an API key but don't have one configured,
        // the service should throw a RuntimeException.
        $this->expectException(RuntimeException::class);

        // Anthropic provider with no API configuration set up
        $agent = Agent::factory()->create([
            'model_provider' => 'anthropic',
            'model_name' => 'claude-sonnet-4-20250514',
            'status' => 'active',
        ]);

        $this->service->sendPrompt($agent, 'System prompt', 'User prompt');
    }

    public function test_clear_api_key_cache(): void
    {
        // This should run without errors
        $this->service->clearApiKeyCache('anthropic');
        $this->service->clearApiKeyCache('openai');
        $this->service->clearApiKeyCache('nonexistent');

        // If no exception was thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_is_provider_available_for_openai_depends_on_config(): void
    {
        // OpenAI availability depends on config('openai.api_key')
        config(['openai.api_key' => null]);

        $result = $this->service->isProviderAvailable('openai');

        $this->assertFalse($result);

        // Set it
        config(['openai.api_key' => 'sk-test-key-123']);

        $result = $this->service->isProviderAvailable('openai');

        $this->assertTrue($result);
    }

    public function test_get_provider_status_shows_default_model_when_not_configured(): void
    {
        $status = $this->service->getProviderStatus();

        // Without any ApiConfiguration, each provider should show the default model
        $this->assertEquals('gpt-4o', $status['openai']['model']);
        $this->assertEquals('claude-sonnet-4-20250514', $status['anthropic']['model']);
        $this->assertEquals('gemini-1.5-pro', $status['google']['model']);
        $this->assertEquals('mistral-large-latest', $status['mistral']['model']);
    }

    public function test_get_provider_status_shows_configured_model(): void
    {
        $this->createApiConfig('anthropic', [
            'model_name' => 'claude-opus-4-0-20250514',
            'rate_limit_per_minute' => 30,
        ]);

        $status = $this->service->getProviderStatus();

        $this->assertEquals('claude-opus-4-0-20250514', $status['anthropic']['model']);
        $this->assertEquals(30, $status['anthropic']['rate_limit']);
    }
}
