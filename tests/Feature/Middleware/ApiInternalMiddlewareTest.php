<?php

namespace Tests\Feature\Middleware;

use App\Models\Agent;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInternalMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'test-internal-api-token-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.internal_api.token', $this->validToken);
    }

    public function test_valid_token_passes_middleware(): void
    {
        $agent = Agent::factory()->create();

        // Send a request with a valid token but missing required fields.
        // If middleware passes, the controller's validation will return 422.
        // A 401 would mean the middleware blocked us; 422 means it passed through.
        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => "Bearer {$this->validToken}"]
        );

        // 422 = validation error from the controller, proving middleware was passed
        $response->assertStatus(422);
    }

    public function test_invalid_token_returns_401(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => 'Bearer wrong-token']
        );

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized. Invalid internal API token.',
        ]);
    }

    public function test_missing_token_returns_401(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            []
        );

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized. Invalid internal API token.',
        ]);
    }

    public function test_default_token_is_change_me_in_production(): void
    {
        // Reset config to default (simulating no env override)
        config()->set('services.internal_api.token', null);

        $agent = Agent::factory()->create();

        // Even with null config, a null bearer token should still fail
        // because null !== null is false in PHP... but bearerToken() returns
        // null when no header is present, so !$token catches it first
        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => 'Bearer ']
        );

        $response->assertStatus(401);
    }
}
